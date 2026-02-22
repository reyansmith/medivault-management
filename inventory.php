<?php
session_start();
require_once ("config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

$inventory = [];
$sql = "
SELECT p.medicine_name,
       s.batch_no,
       s.expiry_date,
       s.quantity,
       pd.cost_price,
       s.selling_price
FROM stock s
INNER JOIN product p ON p.medicine_id = s.medicine_id
LEFT JOIN purchase_details pd 
       ON pd.medicine_id = s.medicine_id
          AND pd.purchase_detail_id = (
              SELECT purchase_detail_id 
              FROM purchase_details 
              WHERE medicine_id = s.medicine_id 
              ORDER BY purchase_detail_id DESC 
              LIMIT 1
          )
ORDER BY p.medicine_name ASC, s.expiry_date ASC
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
}
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">
    <div class="topbar">
        <div class="topbar-text">
            <h2>Inventory</h2>
        </div>
        <div class="top-actions">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="box">
        <div class="table-wrap">
            <table class="leaderboard-table transactions-table">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)) { ?>
                        <tr>
                            <td colspan="6">No inventory data found.</td>
                        </tr>
                    <?php } else { ?>
                        <?php
                        foreach ($inventory as $item) {
                            echo "<tr>";
                            echo "<td>" . $item['medicine_name'] . "</td>";
                            echo "<td>" . $item['batch_no'] . "</td>";
                            echo "<td>" . $item['expiry_date'] . "</td>";
                            echo "<td>" . (int)$item['quantity'] . "</td>";
                            echo "<td>&#8377; " . number_format((float)$item['cost_price'], 2) . "</td>";
                            echo "<td>&#8377; " . number_format((float)$item['selling_price'], 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>