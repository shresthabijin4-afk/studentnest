<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
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
    <title>Student Dashboard | StudentNest</title>
</head>
<body>

<h1>Welcome, <?= htmlspecialchars($name) ?> 👋</h1>

<p>This is the Student Dashboard.</p>

<a href="../logout.php">Logout</a>

</body>
</html>