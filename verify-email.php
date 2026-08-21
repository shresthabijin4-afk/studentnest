<?php

session_start();

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/mail.php";

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

    $action = $_POST["action"] ?? "";

    if ($action === "verify") {

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

                    header("Location: login.php?verified=1");
                    exit;
                }
            }

            $stmt->close();
        }
    }

    if ($action === "resend") {

        $delete = $conn->prepare(
            "DELETE FROM verification_codes
             WHERE user_id = ?
             AND type = 'email'"
        );

        $delete->bind_param("i", $user_id);
        $delete->execute();
        $delete->close();

        try {

            $code = (string) random_int(100000, 999999);
            $code_hash = password_hash($code, PASSWORD_DEFAULT);
            $expires_at = date("Y-m-d H:i:s", time() + 600);

            $insert = $conn->prepare(
                "INSERT INTO verification_codes
                (user_id, type, code_hash, expires_at)
                VALUES (?, 'email', ?, ?)"
            );

            $insert->bind_param(
                "iss",
                $user_id,
                $code_hash,
                $expires_at
            );

            if (!$insert->execute()) {
                throw new Exception("Unable to create verification code.");
            }

            $insert->close();

            $mail = createMailer();

            $mail->addAddress(
                $user["email"],
                $user["name"]
            );

            $mail->Subject = "StudentNest Verification Code";

            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #172033;">
                    <div style="text-align: center; margin-bottom: 25px;">
                        <h1 style="color: #2563eb;">StudentNest</h1>
                    </div>

                    <h2>Your new verification code</h2>

                    <p>Hello ' . htmlspecialchars($user["name"]) . ',</p>

                    <p>
                        You requested a new verification code for your StudentNest account.
                    </p>

                    <div style="text-align: center; margin: 30px 0;">
                        <span style="
                            display: inline-block;
                            padding: 15px 25px;
                            background: #eff6ff;
                            color: #2563eb;
                            font-size: 30px;
                            font-weight: bold;
                            letter-spacing: 8px;
                            border-radius: 12px;
                        ">
                            ' . $code . '
                        </span>
                    </div>

                    <p>
                        This code will expire in <strong>10 minutes</strong>.
                    </p>
                </div>
            ';

            $mail->AltBody =
                "Your new StudentNest verification code is: " . $code .
                ". This code expires in 10 minutes.";

            $mail->send();

            $success = "A new verification code has been sent to your email.";

        } catch (Throwable $e) {

            $error = "Unable to send a new verification code. Please try again.";
        }
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

            <input
                type="hidden"
                name="action"
                value="verify"
            >

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

        <form method="POST" class="resend-form">

            <input
                type="hidden"
                name="action"
                value="resend"
            >

            <p class="resend-text">
                Didn't receive the code?
            </p>

            <button
                type="submit"
                class="resend-button"
            >
                Resend Code
            </button>

        </form>

        <p class="back-text">
            <a href="register.php">
                Back to registration
            </a>
        </p>

    </div>

</div>

</body>

</html>