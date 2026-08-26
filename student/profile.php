<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "student"
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$student_id = (int)$_SESSION["user_id"];

$error = "";
$success = "";

$stmt = $conn->prepare(
    "SELECT id, name, email
     FROM users
     WHERE id = ? AND role = 'student'
     LIMIT 1"
);

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

$name = $user["name"];
$email = $user["email"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");

    if ($name === "") {

        $error = "Name is required.";

    } elseif (strlen($name) < 2) {

        $error = "Name must contain at least 2 characters.";

    } else {

        $update = $conn->prepare(
            "UPDATE users
             SET name = ?
             WHERE id = ? AND role = 'student'"
        );

        if ($update) {

            $update->bind_param("si", $name, $student_id);

            if ($update->execute()) {

                $_SESSION["user_name"] = $name;
                $success = "Profile updated successfully.";

            } else {

                $error = "Unable to update profile.";
            }

            $update->close();

        } else {

            $error = "Unable to update profile.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Profile | StudentNest</title>

<link rel="stylesheet" href="../assests/css/student-pages.css?v=2">
<link rel="stylesheet" href="../assests/css/student-profile.css?v=1">

</head>

<body>

<div class="page">

<div class="page-header">

<div>

<span class="label">Student Portal</span>

<h1>My Profile</h1>

<p>View and update your account information.</p>

</div>

<a href="dashboard.php" class="back-btn">
← Dashboard
</a>

</div>

<div class="profile-card">

<div class="profile-header">

<div class="avatar">
<?= strtoupper(substr($name, 0, 1)) ?>
</div>

<div>

<h2><?= htmlspecialchars($name) ?></h2>

<p>Student</p>

</div>

</div>

<?php if ($error !== ""): ?>

<div class="message error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<?php if ($success !== ""): ?>

<div class="message success">
<?= htmlspecialchars($success) ?>
</div>

<?php endif; ?>

<form method="POST">

<div class="form-group">

<label>Full Name</label>

<input
type="text"
name="name"
value="<?= htmlspecialchars($name) ?>"
required
>

</div>

<div class="form-group">

<label>Email Address</label>

<input
type="email"
value="<?= htmlspecialchars($email) ?>"
disabled
>

<small>
Email address cannot be changed here.
</small>

</div>

<div class="form-actions">

<a href="dashboard.php" class="cancel-btn">
Cancel
</a>

<button type="submit" class="submit-btn">
Save Changes
</button>

</div>

</form>

</div>

</div>

</body>

</html>