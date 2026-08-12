<?php
require_once "config/database.php";

$name = "";
$email = "";
$phone = "";
$role = "student";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "student";

    // Basic validation
    if ($name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must contain at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ["student", "owner"], true)) {
        $error = "Invalid account type.";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {

            // Securely hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role, phone)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $hashed_password,
                $role,
                $phone
            );

            if ($stmt->execute()) {
                $success = "Account created successfully. You can now login.";

                // Clear form values
                $name = "";
                $email = "";
                $phone = "";
                $role = "student";
            } else {
                $error = "Something went wrong. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | StudentNest</title>

    <link rel="stylesheet" href="assests/css/auth.css">
</head>

<body>

<div class="auth-page">

    <div class="auth-card">

        <div class="brand">
            <div class="brand-icon">🏠</div>
            <div>
                <h1>StudentNest</h1>
                <p>Find a place that feels like home.</p>
            </div>
        </div>

        <div class="form-header">
            <h2>Create your account</h2>
            <p>Join StudentNest and find your perfect accommodation.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars($name) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="example@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    value="<?= htmlspecialchars($phone) ?>"
                >
            </div>

            <div class="form-group">
                <label>Account Type</label>

                <div class="role-options">

                    <label class="role-card">
                        <input
                            type="radio"
                            name="role"
                            value="student"
                            <?= $role === "student" ? "checked" : "" ?>
                        >
                        <span class="role-content">
                            <strong>🎓 Student</strong>
                            <small>Find suitable accommodation</small>
                        </span>
                    </label>

                    <label class="role-card">
                        <input
                            type="radio"
                            name="role"
                            value="owner"
                            <?= $role === "owner" ? "checked" : "" ?>
                        >
                        <span class="role-content">
                            <strong>🏠 Room Owner</strong>
                            <small>List and manage your rooms</small>
                        </span>
                    </label>

                </div>
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 6 characters"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat password"
                        required
                    >
                </div>

            </div>

            <button type="submit" class="submit-btn">
                Create Account
            </button>

        </form>

        <div class="form-footer">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>

    </div>

</div>

</body>
</html>