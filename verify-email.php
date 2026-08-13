<?php

session_start();

require_once __DIR__ . "/config/database.php";

$error = "";
$success = "";

if (!isset($_SESSION["verification_user_id"])) {
    header("Location: register.php");
    exit;
}

$user_id = $_SESSION["verification_user_id"];

$stmt = $conn->prepare(
    "SELECT id, name, email, email_verified
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_unset();
    session_destroy();

    header("Location: register.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ((int)$user["email_verified"] === 1) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST["code"] ?? "");

    if ($code === "") {

        $error = "Please enter the verification code.";

    } elseif (!preg_match('/^\d{6}$/', $code)) {

        $error = "Please enter a valid 6-digit code.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, code_hash, expires_at, attempts
             FROM verification_codes
             WHERE user_id = ?
             AND type = 'email'
             ORDER BY id DESC
             LIMIT 1"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {

            $error = "No verification code was found. Please request a new code.";

        } else {

            $verification = $result->fetch_assoc();

            if ((int)$verification["attempts"] >= 5) {

                $error = "Too many incorrect attempts. Please request a new code.";

            } elseif (strtotime($verification["expires_at"]) < time()) {

                $error = "Your verification code has expired. Please request a new code.";

            } elseif (!password_verify($code, $verification["code_hash"])) {

                $update = $conn->prepare(
                    "UPDATE verification_codes
                     SET attempts = attempts + 1
                     WHERE id = ?"
                );

                $update->bind_param("i", $verification["id"]);
                $update->execute();
                $update->close();

                $error = "Incorrect verification code.";

            } else {

                $update = $conn->prepare(
                    "UPDATE users
                     SET email_verified = 1
                     WHERE id = ?"
                );

                $update->bind_param("i", $user_id);
                $update->execute();
                $update->close();

                $delete = $conn->prepare(
                    "DELETE FROM verification_codes
                     WHERE id = ?"
                );

                $delete->bind_param("i", $verification["id"]);
                $delete->execute();
                $delete->close();

                unset($_SESSION["verification_user_id"]);

                $success = "Your email has been verified successfully.";

                header("refresh:2;url=login.php");
            }
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verify Email | StudentNest</title>

    <link
        rel="stylesheet"
        href="assests/css/verify-email.css"
    >

</head>

<body>

<div class="verification-page">

    <div class="verification-card">

        <div class="icon">
            ✉
        </div>

        <h1>Verify your email</h1>

        <p class="description">
            We've sent a 6-digit verification code to
        </p>

        <strong class="email">
            <?= htmlspecialchars($user["email"]) ?>
        </strong>

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

            <label for="code">
                Verification Code
            </label>

            <input
                type="text"
                id="code"
                name="code"
                maxlength="6"
                inputmode="numeric"
                placeholder="000000"
                autocomplete="one-time-code"
                required
            >

            <button type="submit">
                Verify Email
            </button>

        </form>

        <p class="back-text">
            Didn't receive the code?
            <a href="register.php">Register again</a>
        </p>

    </div>

</div>

</body>

</html>