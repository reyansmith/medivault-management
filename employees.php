<?php
session_start();
require_once ("config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

$employees = [];
$result = $conn->query("SELECT emp_id, username FROM employee ORDER BY username ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<?php include("header.php"); ?>
<?php include("sidebar.php"); ?>

<div class="main">
    <div class="topbar">
        <div>
            <h2>Employees</h2>
            <p>Manage employee records</p>
        </div>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="box">
        <h3>Employee List</h3>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)) { ?>
                    <tr>
                        <td colspan="2">No employees found.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($employees as $employee) { ?>
                        <tr>
                            <td><?php echo (int)$employee['emp_id']; ?></td>
                            <td><?php echo htmlspecialchars($employee['username'], ENT_QUOTES, "UTF-8"); ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("footer.php"); ?>
