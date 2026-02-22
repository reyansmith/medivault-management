<?php
session_start();
require_once ("config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

// Handle Add Vendor form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_vendor'])) {
        $vendor_id = $_POST['vendor_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        $stmt = $conn->prepare("INSERT INTO vendor (vendor_id, name, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $vendor_id, $name, $phone, $address);
        if ($stmt->execute()) {
            $message = "Vendor added successfully!";
        } else {
            $message = "Error adding vendor: " . $stmt->error;
        }
        $stmt->close();
    }

    // Handle Remove Vendor
    if (isset($_POST['remove_vendor'])) {
        $vendor_id = $_POST['remove_vendor_id'];
        $stmt = $conn->prepare("DELETE FROM vendor WHERE vendor_id = ?");
        $stmt->bind_param("s", $vendor_id);
        if ($stmt->execute()) {
            $message = "Vendor removed successfully!";
        } else {
            $message = "Error removing vendor: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch vendors
$vendors = [];
$result = $conn->query("SELECT vendor_id, name, phone, address FROM vendor ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
}
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">
    <div class="topbar">
        <div class="topbar-text">
            <h2>Vendors</h2>
        </div>
        <div class="top-actions">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="box">
        <?php 
        if ($message) {
            echo "<p style='color:green;'>" . $message . "</p>";
        }
        ?>

        <!-- Add Vendor Button -->
        <button id="addVendorBtn" style="margin-bottom:15px;">Add Vendor</button>

        <!-- Add Vendor Form (hidden initially) -->
        <form id="addVendorForm" method="POST" style="display:none; margin-bottom:20px;">
            <input type="text" name="vendor_id" placeholder="Vendor ID" required>
            <input type="text" name="name" placeholder="Vendor Name" required>
            <input type="text" name="phone" placeholder="Phone">
            <input type="text" name="address" placeholder="Address">
            <button type="submit" name="add_vendor">Save Vendor</button>
        </form>

        <div class="table-wrap">
            <table class="leaderboard-table transactions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($vendors)) {
                        echo "<tr><td colspan='5'>No vendors found.</td></tr>";
                    } else {
                        foreach ($vendors as $vendor) {
                            echo "<tr>";
                            echo "<td>" . $vendor['vendor_id'] . "</td>";
                            echo "<td>" . $vendor['name'] . "</td>";
                            echo "<td>" . $vendor['phone'] . "</td>";
                            echo "<td>" . $vendor['address'] . "</td>";
                            echo "<td>";
                            // Remove Vendor Form
                            echo "<form method='POST' style='display:inline;' onsubmit=\"return confirm('Are you sure you want to remove this vendor?');\">";
                            echo "<input type='hidden' name='remove_vendor_id' value='" . $vendor['vendor_id'] . "'>";
                            echo "<button type='submit' name='remove_vendor'>Remove</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Show/hide Add Vendor form
document.getElementById('addVendorBtn').addEventListener('click', function() {
    var form = document.getElementById('addVendorForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        this.textContent = 'Cancel';
    } else {
        form.style.display = 'none';
        this.textContent = 'Add Vendor';
    }
});
</script>

<?php include("footer.php"); ?>