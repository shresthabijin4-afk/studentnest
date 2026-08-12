<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION["user_name"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | StudentNest</title>
</head>
<body>

<h1>Welcome, <?= htmlspecialchars($name) ?> 👋</h1>

<p>This is the Admin Dashboard.</p>

<a href="../logout.php">Logout</a>

</body>
</html>