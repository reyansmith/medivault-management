<?php
session_start();
$conn = mysqli_connect("localhost","root","","medivault_db");

if(isset($_POST['login']))
{
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 🔎 CHECK ADMIN
    $admin_query = mysqli_query($conn,
        "SELECT * FROM admin 
         WHERE admin_id='$id' 
         AND username='$username'"
    );

    if(mysqli_num_rows($admin_query) > 0)
    {
        $row = mysqli_fetch_assoc($admin_query);

        if(password_verify($password, $row['password']))
        {
            $_SESSION['role'] = "admin";
            $_SESSION['id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];

            header("Location: dashboard.php");
            exit();
        }
    }

    // 🔎 CHECK EMPLOYEE
    $emp_query = mysqli_query($conn,
        "SELECT * FROM employee 
         WHERE emp_id='$id' 
         AND username='$username'"
    );

    if(mysqli_num_rows($emp_query) > 0)
    {
        $row = mysqli_fetch_assoc($emp_query);

        if(password_verify($password, $row['password']))
        {
            $_SESSION['role'] = "employee";
            $_SESSION['id'] = $row['emp_id'];
            $_SESSION['username'] = $row['username'];

            header("Location: dashboard.php");
            exit();
        }
    }

    echo "<script>alert('Invalid Details');</script>";
}
?>
   

<!DOCTYPE html>
<html>
<head>
<title>Medivault Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-box">
<h2>MEDIVAULT LOGIN</h2>

<form method="POST">
<input type="number" name="id" placeholder="Enter ID" required>
<input type="text" name="username" placeholder="Enter Username" required>
<input type="password" name="password" placeholder="Enter Password" required>

<button type="submit" name="login">Login</button>

</form>

<p class="auth-help">Don't have an account? <a href="mregistration.php">Register Here</a></p>

</div>

</body>
</html>
