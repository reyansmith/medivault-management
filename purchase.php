<?php
session_start();
require_once("config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

// Fetch vendors and products for dropdowns
$vendors = $conn->query("SELECT vendor_id, name FROM vendor");
$products = $conn->query("SELECT medicine_id, medicine_name FROM product");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $purchase_date = $_POST['purchase_date'];
    $admin_id = $_SESSION['admin_id']; // make sure this exists

    // Insert purchase header first
    $conn->query("INSERT INTO purchase (vendor_id, purchase_date, total_amount) VALUES ($vendor_id, '$purchase_date', 0)");
    $purchase_id = $conn->insert_id;

    $total_amount = 0;

    foreach ($_POST['medicine_id'] as $key => $medicine_id) {
        $quantity = (int)$_POST['quantity'][$key];
        $cost_price = (float)$_POST['cost_price'][$key];
        $subtotal = $quantity * $cost_price;
        $total_amount += $subtotal;

        // Insert into purchase_details
        $conn->query("INSERT INTO purchase_details (purchase_id, medicine_id, admin_id, quantity, cost_price) VALUES ($purchase_id, $medicine_id, $admin_id, $quantity, $cost_price)");

        // Update product stock
        $conn->query("UPDATE product SET stock = stock + $quantity WHERE medicine_id = $medicine_id");
    }

    // Update total_amount in purchase
    $conn->query("UPDATE purchase SET total_amount = $total_amount WHERE purchase_id = $purchase_id");

    header("Location: purchases.php"); // redirect to listing page
    exit();
}
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">
    <div class="topbar">
        <h2>Add Purchase</h2>
    </div>

    <form method="POST">
        <label>Vendor:</label>
        <select name="vendor_id" required>
            <?php while($v = $vendors->fetch_assoc()) { ?>
                <option value="<?= $v['vendor_id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
            <?php } ?>
        </select>

        <label>Purchase Date:</label>
        <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" required>

        <div id="medicines">
            <div class="medicine-row">
                <select name="medicine_id[]" required>
                    <?php while($p = $products->fetch_assoc()) { ?>
                        <option value="<?= $p['medicine_id'] ?>"><?= htmlspecialchars($p['medicine_name']) ?></option>
                    <?php } ?>
                </select>
                <input type="number" name="quantity[]" placeholder="Quantity" min="1" required>
                <input type="number" step="0.01" name="cost_price[]" placeholder="Cost Price" required>
                <button type="button" class="remove">Remove</button>
            </div>
        </div>

        <button type="button" id="add-medicine">Add Another Medicine</button>
        <br><br>
        <button type="submit">Save Purchase</button>
    </form>
</div>

<script>
document.getElementById('add-medicine').addEventListener('click', function(){
    let row = document.querySelector('.medicine-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('medicines').appendChild(row);
});

document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove')){
        let rows = document.querySelectorAll('.medicine-row');
        if(rows.length > 1){
            e.target.parentNode.remove();
        }
    }
});
</script>

<?php include("footer.php"); ?>