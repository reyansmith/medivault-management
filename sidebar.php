<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>

<div class="sidebar">
    <h2 class="logo">MEDIVAULT</h2>

    <ul class="nav-links">
        <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
            <a href="inventory.php">
                <i class="fas fa-pills"></i>
                <span>Inventory</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'bill.php' ? 'active' : ''; ?>">
            <a href="bill.php">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Billing</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'purchases.php' ? 'active' : ''; ?>">
            <a href="purchases.php">
                <i class="fas fa-truck"></i>
                <span>Purchases</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'vendor.php' ? 'active' : ''; ?>">
            <a href="vendor.php">
                <i class="fas fa-user-md"></i>
                <span>Vendors</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>

        <li class="<?php echo $currentPage === 'employees.php' ? 'active' : ''; ?>">
            <a href="employees.php">
                <i class="fas fa-users"></i>
                <span>Employees</span>
            </a>
        </li>
    </ul>
</div>
