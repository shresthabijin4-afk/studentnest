<?php

session_start();

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/mail.php";

$name = "";
$email = "";
$role = "student";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "student";

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "" || $confirm_password === "") {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters long.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (!in_array($role, ["student", "owner"], true)) {

        $error = "Invalid account type.";

    } else {

        $check = $conn->prepare(
            "SELECT id, email_verified
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $existing = $result->fetch_assoc();

            if ((int)$existing["email_verified"] === 0) {
                $error = "This email is already registered but not verified.";
            } else {
                $error = "An account with this email already exists.";
            }

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $conn->begin_transaction();

            try {

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (name, email, password, role, email_verified)
                    VALUES (?, ?, ?, ?, 0)"
                );

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $email,
                    $hashed_password,
                    $role
                );

                if (!$stmt->execute()) {
                    throw new Exception("Unable to create account.");
                }

                $user_id = $stmt->insert_id;

                $stmt->close();

                $code = (string) random_int(100000, 999999);
                $code_hash = password_hash($code, PASSWORD_DEFAULT);
                $expires_at = date("Y-m-d H:i:s", time() + 600);

                $code_stmt = $conn->prepare(
                    "INSERT INTO verification_codes
                    (user_id, type, code_hash, expires_at)
                    VALUES (?, 'email', ?, ?)"
                );

                $code_stmt->bind_param(
                    "iss",
                    $user_id,
                    $code_hash,
                    $expires_at
                );

                if (!$code_stmt->execute()) {
                    throw new Exception("Unable to create verification code.");
                }

                $code_stmt->close();

                $mail = createMailer();

                $mail->addAddress(
                    $email,
                    $name
                );

                $mail->Subject = "StudentNest Email Verification";

                $mail->Body = '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; color: #172033;">
                        <div style="text-align: center; margin-bottom: 25px;">
                            <h1 style="color: #2563eb; margin-bottom: 5px;">StudentNest</h1>
                            <p style="color: #6b7280;">Find a place that feels like home.</p>
                        </div>

                        <h2 style="margin-bottom: 10px;">Verify your email</h2>

                        <p>Hello ' . htmlspecialchars($name) . ',</p>

                        <p>
                            Thank you for creating your StudentNest account.
                            Use the verification code below to verify your email address.
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

                        <p style="color: #6b7280; font-size: 13px; margin-top: 30px;">
                            If you did not create this account, you can safely ignore this email.
                        </p>
                    </div>
                ';

                $mail->AltBody =
                    "Your StudentNest verification code is: " . $code .
                    ". This code expires in 10 minutes.";

                $mail->send();

                $conn->commit();

                $_SESSION["verification_user_id"] = $user_id;

                header("Location: verify-email.php");
                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                $error = "Registration could not be completed. Please try again.";
            }
        }

        $check->close();
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

    <title>Create Account | StudentNest</title>

    <link
        rel="stylesheet"
        href="/studentnest/assests/css/Registration.css"
    >

</head>

<body>

<div class="page-container">

    <div class="register-card">

        <div class="brand">

            <div class="brand-icon">
                🏠
            </div>

            <div>

                <h1>StudentNest</h1>

                <p>
                    Find a place that feels like home.
                </p>

            </div>

        </div>


        <div class="form-heading">

            <h2>Create your account</h2>

            <p>
                Join StudentNest and find suitable accommodation.
            </p>

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


        <form method="POST" action="">

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

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

                <label for="email">
                    Email Address
                </label>

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

                <label>
                    Account Type
                </label>

                <div class="role-container">

                    <label class="role-option">

                        <input
                            type="radio"
                            name="role"
                            value="student"
                            <?= $role === "student" ? "checked" : "" ?>
                        >

                        <span class="role-box">

                            <span class="role-icon">
                                🎓
                            </span>

                            <span>

                                <strong>
                                    Student
                                </strong>

                                <small>
                                    Find suitable accommodation
                                </small>

                            </span>

                        </span>

                    </label>


                    <label class="role-option">

                        <input
                            type="radio"
                            name="role"
                            value="owner"
                            <?= $role === "owner" ? "checked" : "" ?>
                        >

                        <span class="role-box">

                            <span class="role-icon">
                                🏠
                            </span>

                            <span>

                                <strong>
                                    Room Owner
                                </strong>

                                <small>
                                    List and manage rooms
                                </small>

                            </span>

                        </span>

                    </label>

                </div>

            </div>


            <div class="password-row">

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 6 characters"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Repeat password"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="primary-btn"
            >
                Create Account
            </button>

        </form>


        <div class="bottom-text">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>

    </div>

</div>

</body>

</html>