<?php
$conn = mysqli_connect("localhost","root","","medivault_db");

if(isset($_POST['register']))
{
    $role = $_POST['role'];
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    // 🔐 HASH PASSWORD
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if($role == "admin")
    {
        $check = mysqli_query($conn,
            "SELECT * FROM admin WHERE admin_id='$id'"
        );

        if(mysqli_num_rows($check) > 0)
        {
            echo "<script>alert('Admin ID already exists');</script>";
        }
        else
        {
            mysqli_query($conn,
            "INSERT INTO admin(admin_id,username,password,email)
             VALUES('$id','$username','$hashed_password','$email')");

            echo "<script>alert('Admin Registered Successfully');</script>";
        }
    }
    else
    {
        $check = mysqli_query($conn,
            "SELECT * FROM employee WHERE emp_id='$id'"
        );

        if(mysqli_num_rows($check) > 0)
        {
            echo "<script>alert('Employee ID already exists');</script>";
        }
        else
        {
            mysqli_query($conn,
            "INSERT INTO employee(emp_id,username,password,email)
             VALUES('$id','$username','$hashed_password','$email')");

            echo "<script>alert('Employee Registered Successfully');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Medivault Registration</title>
<style>
body{
    margin:0;
    font-family:Arial;
    background:#0f9d9a;
}
.box{
    width:400px;
    margin:80px auto;
    background:white;
    padding:40px;
    text-align:center;
    border-radius:10px;
    box-shadow:0px 0px 15px gray;
}
input, select{
    width:90%;
    padding:12px;
    margin:10px 0;
}
button{
    width:95%;
    padding:12px;
    background:navy;
    color:white;
    border:none;
}
button:hover{
    background:darkblue;
}
a{
    text-decoration:none;
    color:navy;
    font-weight:bold;
}
</style>
</head>
<body>

<div class="box">
<h2>MEDIVAULT REGISTRATION</h2>

<form method="POST">

<select name="role" required>
    <option value="">Select Role</option>
    <option value="admin">Admin</option>
    <option value="employee">Employee</option>
</select>

<input type="number" name="id" placeholder="Enter ID" required>
<input type="text" name="username" placeholder="Enter Username" required>
<input type="email" name="email" placeholder="Enter Email" required>
<input type="password" name="password" placeholder="Enter Password" required>

<button type="submit" name="register">Register</button>

</form>

<br>
Already have account?
<a href="mlogin.php">Login Here</a>

</div>

</body>
</html>