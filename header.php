<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? ($_SESSION['name'] ?? "Admin");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medivault</title>
   
    <link rel="stylesheet" href="style.css">

    <script src="https://kit.fontawesome.com/6c8e1d3298.js" crossorigin="anonymous"></script>
</head>
<body>

<div class="container">
