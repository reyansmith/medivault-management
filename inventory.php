<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: mlogin.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'];

    // ADD PRODUCT
    if ($action == "add_product") {
        $id   = $_POST['medicine_id'];   // VARCHAR
        $name = $_POST['medicine_name'];
        $desc = $_POST['description'];

        $check = $conn->query("SELECT * FROM product WHERE medicine_id='$id'");
        if ($check->num_rows > 0) {
            $msg = "Medicine ID already exists";
        } else {
            $conn->query("INSERT INTO product (medicine_id,medicine_name,description)
                          VALUES ('$id','$name','$desc')");
            $msg = "Medicine added successfully";
        }
    }

    // DELETE PRODUCT
    if ($action == "delete_product") {
        $id = $_POST['medicine_id'];  // VARCHAR
        $conn->query("DELETE FROM product WHERE medicine_id='$id'");
        $msg = "Medicine deleted successfully";
    }

    // ADD STOCK
    if ($action == "add_stock") {

        $stock_id = $_POST['stock_id'];   // VARCHAR
        $med      = $_POST['medicine_id'];
        $batch    = $_POST['batch_no'];
        $exp      = $_POST['expiry_date'];
        $qty      = $_POST['quantity'];
        $price    = $_POST['selling_price'];

        $conn->query("INSERT INTO stock 
            (stock_id, medicine_id, batch_no, expiry_date, quantity, selling_price)
            VALUES 
            ('$stock_id','$med','$batch','$exp','$qty','$price')");

        $msg = "Stock added successfully";
    }

    // DELETE STOCK
    if ($action == "delete_stock") {
        $id = $_POST['stock_id'];   // VARCHAR
        $conn->query("DELETE FROM stock WHERE stock_id='$id'");
        $msg = "Stock deleted successfully";
    }
}

$section = isset($_GET['section']) ? $_GET['section'] : "products";


$search = "";
if(isset($_GET['search']) && $_GET['search'] != ""){
    $search = $conn->real_escape_string($_GET['search']);
    $products = $conn->query("
        SELECT p.*, IFNULL(SUM(s.quantity),0) AS total_stock
        FROM product p
        LEFT JOIN stock s ON s.medicine_id=p.medicine_id
        WHERE p.medicine_name LIKE '%$search%' 
           OR p.medicine_id LIKE '%$search%'
        GROUP BY p.medicine_id
    ");
} else {
    $products = $conn->query("
        SELECT p.*, IFNULL(SUM(s.quantity),0) AS total_stock
        FROM product p
        LEFT JOIN stock s ON s.medicine_id=p.medicine_id
        GROUP BY p.medicine_id
    ");
}

$stocks = $conn->query("
    SELECT s.*, p.medicine_name
    FROM stock s
    JOIN product p ON p.medicine_id=s.medicine_id
");
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">

    <div class="topbar">
        <h2>Inventory</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <?php if($msg!=""){ ?>
        <div class="inv-alert"><?php echo $msg; ?></div>
    <?php } ?>

    <div class="inv-nav">
        <a href="?section=products" class="<?php echo ($section=='products')?'on':''; ?>">Products</a>
        <a href="?section=stock" class="<?php echo ($section=='stock')?'on':''; ?>">Stock</a>
    </div>

    <!-- PRODUCTS -->
    <?php if($section=="products"){ ?>
    <div class="inv-grid">

        <div class="box">
            <h3>Add Medicine</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_product">

                <div class="form-group">
                    <label>Medicine ID</label>
                    <input type="text" name="medicine_id" required>
                </div>

                <div class="form-group">
                    <label>Medicine Name</label>
                    <input type="text" name="medicine_name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>

                <button class="inv-btn primary">Save</button>
            </form>
        </div>

        <div class="box">
            <h3>Medicine List</h3>
            <form method="GET" class="inv-search-form">
    <input type="hidden" name="section" value="products">
    <input type="text" name="search" class="inv-search-input"
           placeholder="Search Medicine..."
           value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
    <button class="inv-btn primary" type="submit">Search</button>
</form>
            <table class="leaderboard-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Total Stock</th>
                    <th>Action</th>
                </tr>
                <?php while($row=$products->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo $row['medicine_id']; ?></td>
                    <td><?php echo $row['medicine_name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><?php echo $row['total_stock']; ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                            <button class="inv-btn danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

    </div>
    <?php } ?>

    <!-- STOCK -->
    <?php if($section=="stock"){ ?>
<div class="inv-grid">

    <div class="box">
        <h3>Add Stock</h3>
        <form method="post">
            <input type="hidden" name="action" value="add_stock">

            <div class="form-group">
                <label>Stock ID</label>
                <input type="text" name="stock_id" required>
            </div>

            <div class="form-group">
                <label>Medicine</label>
                <select name="medicine_id" required>
                    <?php
                    $list = $conn->query("SELECT * FROM product");
                    while($p=$list->fetch_assoc()){
                        echo "<option value='".$p['medicine_id']."'>".$p['medicine_name']."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Batch No</label>
                <input type="text" name="batch_no" required>
            </div>

            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" required>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" required>
            </div>

            <div class="form-group">
                <label>Selling Price</label>
                <input type="number" step="0.01" name="selling_price" required>
            </div>

            <button class="inv-btn primary">Add Stock</button>
        </form>
    </div>

    <div class="box">
        <h3>Stock List</h3>
        <table class="leaderboard-table">
            <tr>
                <th>Stock ID</th>
                <th>Medicine</th>
                <th>Batch</th>
                <th>Expiry</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
            <?php while($s=$stocks->fetch_assoc()){ ?>
            <tr>
                <td><?php echo $s['stock_id']; ?></td>
                <td><?php echo $s['medicine_name']; ?></td>
                <td><?php echo $s['batch_no']; ?></td>
                <td><?php echo $s['expiry_date']; ?></td>
                <td><?php echo $s['quantity']; ?></td>
                <td><?php echo $s['selling_price']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="action" value="delete_stock">
                        <input type="hidden" name="stock_id" value="<?php echo $s['stock_id']; ?>">
                        <button class="inv-btn danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>
<?php } ?>

</div>

<?php include("footer.php"); ?>

