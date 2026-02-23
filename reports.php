<?php
session_start();
require_once ("config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

// Function to get total amount for monthly
function getMonthlyTotal($conn, $table, $column, $dateColumn) {
    $query = "SELECT COALESCE(SUM($column), 0) AS total
              FROM $table
              WHERE YEAR($dateColumn) = YEAR(CURDATE())
                AND MONTH($dateColumn) = MONTH(CURDATE())";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (float)($row['total'] ?? 0);
}

// Function to get count of bills for monthly
function getMonthlyCount($conn, $table, $dateColumn) {
    $query = "SELECT COUNT(*) AS total
              FROM $table
              WHERE YEAR($dateColumn) = YEAR(CURDATE())
                AND MONTH($dateColumn) = MONTH(CURDATE())";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

// Monthly totals
$monthSales = getMonthlyTotal($conn, 'bill', 'total_amount', 'bill_date');
$monthBills = getMonthlyCount($conn, 'bill', 'bill_date');
$monthPurchases = getMonthlyTotal($conn, 'purchase', 'total_amount', 'purchase_date');
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">
    <div class="topbar">
        <div class="topbar-text">
            <h2>Monthly Reports</h2>
        </div>
        <div class="top-actions">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="cards">

        <div class="card">
            <h4>Sales</h4>
            <h2>&#8377; <?= number_format($monthSales, 2) ?></h2>
        </div>

        <div class="card">
            <h4>Bills</h4>
            <h2><?= $monthBills ?></h2>
        </div>

        <div class="card">
            <h4>Purchases</h4>
            <h2>&#8377; <?= number_format($monthPurchases, 2) ?></h2>
        </div>

    </div>
</div>

<?php include("footer.php"); ?>