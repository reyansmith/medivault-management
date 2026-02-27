<?php
session_start();
$conn = mysqli_connect("localhost","root","","medivault_db");

if(isset($_POST['login']))
{
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // CHECK ADMIN
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

    // CHECK EMPLOYEE
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
<style>
body{
    margin:0;
    padding:0;
    font-family: Arial, sans-serif;
    background:#0f172a;   /* full dark theme */
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* Main Box */
.login-container{
    width:850px;
    height:480px;
    background:white;
    border-radius:10px;
    display:flex;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0, 0, 0, 0);
}

.login-left{
    width:45%;
    background:#2b6cb0;   /* professional medical blue */
    color:white;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    padding:40px;
}
.login-left h1{
    font-size:28px;
    margin-bottom:10px;
}

.login-left p{
    opacity:0.7;
    text-align:center;
}

/* RIGHT SIDE */
.login-right{
    width:55%;
    display:flex;
    justify-content:center;
    align-items:center;
}

.form-box{
    width:75%;
}

.form-box h2{
    margin-bottom:25px;
    color:#1e293b;
}

/* Inputs */
input{
    width:100%;
    padding:10px;
    margin-bottom:20px;
    border:1px solid #d1d5db;
    border-radius:6px;
    font-size:14px;
}

input:focus{
    outline:none;
    border:1px solid #2563eb;
}

/* Button – Same Theme Style */
button{
    width:100%;
    padding:10px;
    background:#1e293b;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
    transition:0.2s;
}

button:hover{
    background:#2563eb;
}

</style>
</head>

<body>

<div class="login-container">

    <div class="login-left">
        <h1>MEDIVAULT</h1>
        <p>Mannath Medicals Web System</p>
    </div>

    <div class="login-right">
        <div class="form-box">
            <h2>Login Your Account</h2>
            <form method="POST">
                <input type="number" name="id" placeholder="Enter ID" required>
                <input type="text" name="username" placeholder="Enter Username" required>
                <input type="password" name="password" placeholder="Enter Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>