<?php
session_start();
include("header.php");
include("sidebar.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

$conn = new mysqli("localhost","root","","medivault_db");
if($conn->connect_error) die("Connection failed");

$bill_generated = false;
$error = "";

/* Generate Next Bill ID */
$result = $conn->query("
    SELECT MAX(CAST(SUBSTRING(bill_id,5) AS UNSIGNED)) AS max_id 
    FROM bill
");

$row = $result->fetch_assoc();
$number = ($row['max_id'] !== NULL) ? $row['max_id'] + 1 : 1;
$bill_id = "BILL" . str_pad($number, 3, "0", STR_PAD_LEFT);

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $emp_id = $_POST['emp_id'];
    $payment_method = $_POST['payment_method'];
    $medicine_id = $_POST['medicine_id'];
    $quantity = intval($_POST['quantity']);

    if($quantity <= 0){
        $error = "Invalid quantity!";
    } 
    else {

        /* Fetch ONLY non-expired valid batch (FEFO) */
        $stock_query = $conn->query("
            SELECT stock_id, quantity, selling_price, expiry_date
            FROM stock
            WHERE medicine_id = '$medicine_id'
            AND quantity > 0
            AND expiry_date IS NOT NULL
            AND expiry_date != '0000-00-00'
            AND expiry_date >= CURDATE()
            ORDER BY expiry_date ASC
            LIMIT 1
        ");

        if($stock_query->num_rows == 0){
            $error = "No valid (non-expired) stock available!";
        }
        else {

            $stock = $stock_query->fetch_assoc();
            $available_quantity = $stock['quantity'];
            $selling_price = $stock['selling_price'];
            $stock_id = $stock['stock_id'];

            if($quantity > $available_quantity){
                $error = "Insufficient stock! Only $available_quantity available in earliest batch.";
            }
            else {

                $subtotal = $quantity * $selling_price;
                $total_amount = $subtotal;

                /* Insert Bill */
                $conn->query("INSERT INTO bill 
                (bill_id, emp_id, bill_date, total_amount, payment_method, customer_name, customer_contact)
                VALUES 
                ('$bill_id','$emp_id',NOW(),'$total_amount','$payment_method','$customer_name','$customer_contact')");

                /* Insert Bill Details */
                $bill_detail_id = "BD" . time();

                $conn->query("INSERT INTO bill_details 
                (bill_detail_id,bill_id,medicine_id,quantity,selling_price)
                VALUES
                ('$bill_detail_id','$bill_id','$medicine_id','$quantity','$selling_price')");

                /* Deduct ONLY from selected valid batch */
                $conn->query("UPDATE stock 
                              SET quantity = quantity - $quantity 
                              WHERE stock_id='$stock_id'");

                $bill_generated = true;
                $bill_date = date("d-m-Y");

                /* Fetch Items for display */
                $items = $conn->query("
                    SELECT p.medicine_name, bd.quantity, bd.selling_price
                    FROM bill_details bd
                    JOIN product p ON bd.medicine_id = p.medicine_id
                    WHERE bd.bill_id='$bill_id'
                ");
            }
        }
    }
}

/* Load Medicines (only valid for billing dropdown) */
$medicines = $conn->query("
SELECT DISTINCT s.medicine_id, p.medicine_name 
FROM stock s
JOIN product p ON s.medicine_id = p.medicine_id
WHERE s.quantity > 0
AND s.expiry_date IS NOT NULL
AND s.expiry_date != '0000-00-00'
AND s.expiry_date >= CURDATE()
");
?>
<style>
.bill-container{
    width:750px;
    margin:30px auto;
    background:#ffffff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}

.flex{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    font-size:14px;
}

th{
    background:#1e293b;
    color:white;
    padding:10px;
    text-align:center;
}

td{
    padding:10px;
    text-align:center;
    border-bottom:1px solid #e5e7eb;
}

tr:hover{
    background:#f9fafb;
}

.total-box{
    margin-top:25px;
    width:40%;
    float:right;
    background:#f8fafc;
    padding:15px;
    border-radius:8px;
}

.total-box table{
    width:100%;
}

.total-box td{
    border:none;
    padding:6px 0;
    font-size:14px;
}

.final{
    background:#1e293b;
    color:white;
    padding:10px;
    font-weight:bold;
    border-radius:6px;
}

input,select{
    width:100%;
    padding:8px;
    margin:6px 0;
    border:1px solid #d1d5db;
    border-radius:6px;
    font-size:14px;
}

input:focus,select:focus{
    outline:none;
    border:1px solid #2563eb;
}

button{
    padding:10px;
    background:#1e293b;
    color:white;
    border:none;
    border-radius:6px;
    width:100%;
    font-weight:600;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    background:#2563eb;
}

.error{
    color:#dc2626;
    font-weight:600;
    margin-top:10px;
}
</style>


<div class="main">
    <div class="topbar">
        <div class="topbar-text">
            <h2>Billing</h2>
        </div>
        <div class="top-actions">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

<div class="bill-container">

<?php if(!$bill_generated){ ?>

<h3>BILL INVOICE</h3>

<?php if($error != ""){ ?>
<p class="error"><?php echo $error; ?></p>
<?php } ?>

<form method="POST">

Customer Name:
<input type="text" name="customer_name" required>

Customer Contact:
<input type="text" name="customer_contact" required>

Employee ID:
<input type="text" name="emp_id" required>

Payment Method:
<select name="payment_method">
<option>Cash</option>
<option>UPI</option>
<option>Card</option>
</select>

Medicine:
<select name="medicine_id">
<?php while($row=$medicines->fetch_assoc()){ ?>
<option value="<?php echo $row['medicine_id']; ?>">
<?php echo $row['medicine_name']; ?>
</option>
<?php } ?>
</select>

Quantity:
<input type="number" name="quantity" required>

<br>
<button type="submit">Generate Bill</button>

</form>

<?php } ?>

<?php if($bill_generated){ ?>

<h2 style="text-align:center;color:#1a5dab;">MEDIVAULT PHARMACY</h2>
<hr>

<div class="flex">
<div>
<b>Bill No:</b> <?php echo $bill_id; ?><br>
<b>Date:</b> <?php echo $bill_date; ?>
</div>

<div>
<b>Employee:</b> <?php echo $emp_id; ?><br>
<b>Payment:</b> <?php echo $payment_method; ?>
</div>
</div>

<hr>

<p>
<b>Customer:</b> <?php echo $customer_name; ?><br>
<b>Contact:</b> <?php echo $customer_contact; ?>
</p>

<table>
<tr>
<th>No.</th>
<th>Medicine</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
</tr>

<?php
$no=1;
while($row=$items->fetch_assoc()){
$sub=$row['quantity']*$row['selling_price'];
?>

<tr>
<td><?php echo $no++; ?></td>
<td><?php echo $row['medicine_name']; ?></td>
<td><?php echo $row['quantity']; ?></td>
<td><?php echo $row['selling_price']; ?></td>
<td><?php echo $sub; ?></td>
</tr>

<?php } ?>
</table>

<div class="total-box">
<table>
<tr>
<td>Subtotal:</td>
<td>₹ <?php echo $subtotal; ?></td>
</tr>
<tr class="final">
<td>Total:</td>
<td>₹ <?php echo $total_amount; ?></td>
</tr>
</table>
</div>

<div style="clear:both;"></div>

<br><br>
<a href="bill.php"><button>New Bill</button></a>

<?php } ?>

</div>
</div>

<?php include("footer.php"); ?>