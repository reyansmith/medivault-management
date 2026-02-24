<?php
session_start();
require_once("config.php");

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../mlogin.php");
    exit();
}

$message = "";
$error_message = "";
$section = (isset($_GET['section']) && $_GET['section'] === "users") ? "users" : "sessions";

/* ---------------- REMOVE EMPLOYEE ---------------- */
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    $stmt_del = $conn->prepare("DELETE FROM employee WHERE emp_id = ?");
    $stmt_del->bind_param("s", $delete_id);
    if ($stmt_del->execute()) {
        $message = "Employee removed successfully!";
    }
    $stmt_del->close();
}

/* ---------------- USER MANAGEMENT (CREATE / UPDATE) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === "create_user") {
        $new_id = trim($_POST['new_emp_id'] ?? "");
        $new_username = trim($_POST['new_username'] ?? "");
        $new_email = trim($_POST['new_email'] ?? "");
        $new_password = $_POST['new_password'] ?? "";

        if ($new_id === "" || $new_username === "" || $new_email === "" || $new_password === "") {
            $error_message = "Please fill all fields to create a user.";
        } else {
            $stmt_check = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
            $stmt_check->bind_param("s", $new_id);
            $stmt_check->execute();
            $exists = $stmt_check->get_result();
            $stmt_check->close();

            if ($exists && $exists->num_rows > 0) {
                $error_message = "Employee ID already exists.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO employee (emp_id, username, email, password) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $new_id, $new_username, $new_email, $hashed_password);

                if ($stmt_insert->execute()) {
                    $message = "User created successfully.";
                } else {
                    $error_message = "Failed to create user.";
                }
                $stmt_insert->close();
            }
        }
    }

    // if ($_POST['action'] === "update_user") {
    //     $edit_id = trim($_POST['edit_emp_id'] ?? "");
    //     $edit_username = trim($_POST['edit_username'] ?? "");
    //     $edit_email = trim($_POST['edit_email'] ?? "");
    //     $edit_password = $_POST['edit_password'] ?? "";

    //     if ($edit_id === "" || $edit_username === "" || $edit_email === "") {
    //         $error_message = "ID, username, and email are required for update.";
    //     } else {
    //         if ($edit_password !== "") {
    //             $hashed_password = password_hash($edit_password, PASSWORD_DEFAULT);
    //             $stmt_update = $conn->prepare("UPDATE employee SET username = ?, email = ?, password = ? WHERE emp_id = ?");
    //             $stmt_update->bind_param("ssss", $edit_username, $edit_email, $hashed_password, $edit_id);
    //         } else {
    //             $stmt_update = $conn->prepare("UPDATE employee SET username = ?, email = ? WHERE emp_id = ?");
    //             $stmt_update->bind_param("sss", $edit_username, $edit_email, $edit_id);
    //         }

    //         if ($stmt_update->execute()) {
    //             if ($stmt_update->affected_rows > 0) {
    //                 $message = "User updated successfully.";
    //             } else {
    //                 $error_message = "No changes made or user not found.";
    //             }
    //         } else {
    //             $error_message = "Failed to update user.";
    //         }
    //         $stmt_update->close();
    //     }
    // }
}

/* ---------------- FETCH SESSION TABLE ---------------- */
$session_rows = [];
$session_query = $conn->query("
    SELECT s.emp_id, e.username, s.login_time, s.logout_time
    FROM `session` s
    LEFT JOIN employee e ON e.emp_id = s.emp_id
    ORDER BY s.login_time DESC
");
if ($session_query) {
    while ($row = $session_query->fetch_assoc()) {
        $session_rows[] = $row;
    }
}

/* ---------------- FETCH USERS ---------------- */
$users = [];
$users_query = $conn->query("SELECT emp_id, username, email FROM employee ORDER BY username ASC");
if ($users_query) {
    while ($row = $users_query->fetch_assoc()) {
        $users[] = $row;
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
        <form method="GET" class="employees-section-form">
            <label for="section"><strong>Choose Section:</strong></label>
            <select name="section" id="section" onchange="this.form.submit()">
                <option value="sessions" <?php echo ($section === "sessions") ? "selected" : ""; ?>>Session Table</option>
                <option value="users" <?php echo ($section === "users") ? "selected" : ""; ?>>User Management</option>
            </select>
        </form>

        <?php if ($message) { ?>
            <p class="status-success"><?php echo htmlspecialchars($message); ?></p>
        <?php } ?>
        <?php if ($error_message) { ?>
            <p class="status-error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php } ?>

        <?php if ($section === "sessions") { ?>
            <h3>Session Table</h3>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Username</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($session_rows)) { ?>
                    <tr>
                        <td colspan="4">No session records found.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($session_rows as $session_row) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($session_row['emp_id']); ?></td>
                            <td><?php echo htmlspecialchars($session_row['username'] ?? "-"); ?></td>
                            <td><?php echo htmlspecialchars($session_row['login_time']); ?></td>
                            <td><?php echo htmlspecialchars($session_row['logout_time'] ?: "Still Logged In"); ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>

        <?php if ($section === "users") { ?>
            <h3>User Management</h3>

            <div class="employees-user-grid">
                <form method="POST" class="employees-user-card">
                    <input type="hidden" name="action" value="create_user">
                    <h4 class="employees-user-title">Create User</h4>
                    <input type="text" name="new_emp_id" placeholder="Employee ID" required class="employees-user-input">
                    <input type="text" name="new_username" placeholder="Username" required class="employees-user-input">
                    <input type="email" name="new_email" placeholder="Email" required class="employees-user-input">
                    <input type="password" name="new_password" placeholder="Password" required class="employees-user-input">
                    <button type="submit" class="employees-user-btn">Create</button>
                </form>
            </div>

            <h4>Employee Users</h4>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)) { ?>
                    <tr>
                        <td colspan="4">No users found.</td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['emp_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <a href="?section=users&delete=<?php echo urlencode($user['emp_id']); ?>"
                                   onclick="return confirm('Delete this user?')">
                                   Remove
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>

    </div>
</div>

<?php include("footer.php"); ?>
