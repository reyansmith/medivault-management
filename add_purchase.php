<?php
session_start();
require_once("config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] !== "admin"){
    header("Location: ../mlogin.php");
    exit();
}

$message = "";
$error = "";

/* ========================= */
/* AUTO GENERATE PURCHASE ID */
/* ========================= */
$result = $conn->query("
    SELECT MAX(CAST(SUBSTRING(purchase_id,4) AS UNSIGNED)) AS max_id 
    FROM purchase
");

$row = $result->fetch_assoc();
$number = ($row['max_id'] !== NULL) ? $row['max_id'] + 1 : 1;
$purchase_id = "PUR" . str_pad($number, 3, "0", STR_PAD_LEFT);


/* ========================= */
/* FORM SUBMIT */
/* ========================= */
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $vendor_id = trim($_POST['vendor_id']);
    $purchase_date = $_POST['purchase_date'];

    if(empty($vendor_id) || empty($purchase_date)){
        $error = "Vendor and Date required.";
    } else {

        $total_amount = 0;

        /* Insert Purchase */
        $stmt = $conn->prepare("
            INSERT INTO purchase (purchase_id, vendor_id, purchase_date, total_amount)
            VALUES (?,?,?,0)
        ");
        $stmt->bind_param("sss", $purchase_id, $vendor_id, $purchase_date);
        $stmt->execute();
        $stmt->close();

        /* Generate purchase detail ID */
        $res2 = $conn->query("
            SELECT MAX(CAST(SUBSTRING(purchase_detail_id,3) AS UNSIGNED)) AS max_id 
            FROM purchase_details
        ");
        $row2 = $res2->fetch_assoc();
        $detail_number = ($row2['max_id'] !== NULL) ? $row2['max_id'] + 1 : 1;

        foreach($_POST['medicine_name'] as $i => $med_name){

            $description = $_POST['description'][$i];
            $admin_id = $_POST['admin_id'][$i];
            $quantity = (int)$_POST['quantity'][$i];
            $cost_price = (float)$_POST['cost_price'][$i];
            $batch_no = $_POST['batch_no'][$i];
            $expiry_date = $_POST['expiry_date'][$i];
            $selling_price = (float)$_POST['selling_price'][$i];

            if($quantity <= 0 || $cost_price <= 0 || $selling_price <= 0){
                $error = "Invalid quantity or price.";
                break;
            }

            /* ================= PRODUCT AUTO ID ================= */

            $check_product = $conn->prepare("SELECT medicine_id FROM product WHERE medicine_name=?");
            $check_product->bind_param("s", $med_name);
            $check_product->execute();
            $product_result = $check_product->get_result();

            if($product_result->num_rows > 0){
                $prod_row = $product_result->fetch_assoc();
                $medicine_id = $prod_row['medicine_id'];
            } else {

                $resP = $conn->query("
                    SELECT MAX(CAST(SUBSTRING(medicine_id,2) AS UNSIGNED)) AS max_id 
                    FROM product
                ");
                $rowP = $resP->fetch_assoc();
                $numP = ($rowP['max_id'] !== NULL) ? $rowP['max_id'] + 1 : 1;
                $medicine_id = "P" . str_pad($numP, 3, "0", STR_PAD_LEFT);

                $insert_product = $conn->prepare("
                    INSERT INTO product (medicine_id, medicine_name, description)
                    VALUES (?,?,?)
                ");
                $insert_product->bind_param("sss", $medicine_id, $med_name, $description);
                $insert_product->execute();
                $insert_product->close();
            }

            $detail_id = "PD" . str_pad($detail_number++, 3, "0", STR_PAD_LEFT);
            $subtotal = $quantity * $cost_price;
            $total_amount += $subtotal;

            /* Insert purchase_details */
            $stmt2 = $conn->prepare("
                INSERT INTO purchase_details
                (purchase_detail_id, purchase_id, medicine_id, admin_id, quantity, cost_price)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt2->bind_param("sssidd",
                $detail_id,
                $purchase_id,
                $medicine_id,
                $admin_id,
                $quantity,
                $cost_price
            );
            $stmt2->execute();
            $stmt2->close();

            /* ================= STOCK UPDATE ================= */

            $check_stock = $conn->prepare("
                SELECT stock_id, quantity 
                FROM stock 
                WHERE medicine_id=? AND batch_no=?
            ");
            $check_stock->bind_param("ss", $medicine_id, $batch_no);
            $check_stock->execute();
            $stock_result = $check_stock->get_result();

            if($stock_result->num_rows > 0){

                $row_stock = $stock_result->fetch_assoc();
                $new_qty = $row_stock['quantity'] + $quantity;

                $update_stock = $conn->prepare("
                    UPDATE stock
                    SET quantity=?, expiry_date=?, selling_price=?
                    WHERE stock_id=?
                ");
                $update_stock->bind_param("idss",
                    $new_qty,
                    $expiry_date,
                    $selling_price,
                    $row_stock['stock_id']
                );
                $update_stock->execute();
                $update_stock->close();

            } else {

                /* STOCK AUTO ID S001 */

                $resS = $conn->query("
                    SELECT MAX(CAST(SUBSTRING(stock_id,2) AS UNSIGNED)) AS max_id 
                    FROM stock
                ");
                $rowS = $resS->fetch_assoc();
                $numS = ($rowS['max_id'] !== NULL) ? $rowS['max_id'] + 1 : 1;
                $stock_id = "S" . str_pad($numS, 3, "0", STR_PAD_LEFT);

                $insert_stock = $conn->prepare("
                    INSERT INTO stock
                    (stock_id, medicine_id, batch_no, expiry_date, quantity, selling_price)
                    VALUES (?,?,?,?,?,?)
                ");
                $insert_stock->bind_param("ssssid",
                    $stock_id,
                    $medicine_id,
                    $batch_no,
                    $expiry_date,
                    $quantity,
                    $selling_price
                );
                $insert_stock->execute();
                $insert_stock->close();
            }

            $check_stock->close();
        }

        /* Update total amount */
        $stmt3 = $conn->prepare("
            UPDATE purchase SET total_amount=? WHERE purchase_id=?
        ");
        $stmt3->bind_param("ds", $total_amount, $purchase_id);
        $stmt3->execute();
        $stmt3->close();

        if(empty($error)){
            $message = "Purchase Successful! ID: $purchase_id | Total ₹".number_format($total_amount,2);
        }
    }
}

/* Dropdown data */
$vendors = $conn->query("SELECT vendor_id, name FROM vendor")->fetch_all(MYSQLI_ASSOC);
$admins = $conn->query("SELECT admin_id, username FROM admin")->fetch_all(MYSQLI_ASSOC);

include("header.php");
include("sidebar.php");
?>

<div class="main">
    <div class="topbar">
        <div class="topbar-text">
            <h2>Purchase Details</h2>
        </div>
        <div class="top-actions">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
<div class="main">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:center;">
    <h2>Add Purchase</h2>
    <a href="purchases.php" style="
        padding:8px 15px;
        background:#000;
        color:#fff;
        text-decoration:none;
        border-radius:5px;">
        ← Back to Purchase
    </a>
</div>
<div class="box">

<?php if($error) echo "<p style='color:red'>$error</p>"; ?>
<?php if($message) echo "<p style='color:green'>$message</p>"; ?>

<form method="POST">

<h3>Purchase Info</h3>

<input type="text" value="<?php echo $purchase_id; ?>" readonly>

<select name="vendor_id" required>
<option value="">Select Vendor</option>
<?php foreach($vendors as $v){ ?>
<option value="<?php echo $v['vendor_id']; ?>">
<?php echo $v['name']; ?>
</option>
<?php } ?>
</select>

<input type="date" name="purchase_date" required>

<h3>Purchase Details</h3>

<table border="1" width="100%" cellpadding="8">
<tr>
<th>Medicine Name</th>
<th>Description</th>
<th>Admin</th>
<th>Batch</th>
<th>Expiry</th>
<th>Qty</th>
<th>Cost Price</th>
<th>Selling Price</th>
</tr>

<tr>
<td><input type="text" name="medicine_name[]" required></td>
<td><input type="text" name="description[]" required></td>

<td>
<select name="admin_id[]" required>
<option value="">Select</option>
<?php foreach($admins as $a){ ?>
<option value="<?php echo $a['admin_id']; ?>">
<?php echo $a['username']; ?>
</option>
<?php } ?>
</select>
</td>

<td><input type="text" name="batch_no[]" required></td>
<td><input type="date" name="expiry_date[]" required></td>
<td><input type="number" name="quantity[]" min="1" required></td>
<td><input type="number" name="cost_price[]" step="0.01" required></td>
<td><input type="number" name="selling_price[]" step="0.01" required></td>
</tr>

</table>

<br>
<button type="submit">Add Purchase</button>

</form>

</div>
</div>

<?php include("footer.php"); ?>