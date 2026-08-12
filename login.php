<?php

session_start();

require_once __DIR__ . "/config/database.php";

$email = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                if ($user["role"] === "student") {

                    header("Location: student/dashboard.php");
                    exit;

                } elseif ($user["role"] === "owner") {

                    header("Location: owner/dashboard.php");
                    exit;

                } elseif ($user["role"] === "admin") {

                    header("Location: admin/dashboard.php");
                    exit;

                } else {

                    $error = "Invalid account role.";
                }

            } else {

                $error = "Invalid email or password.";
            }

        } else {

            $error = "Invalid email or password.";
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

    <title>Login | StudentNest</title>

    <link
        rel="stylesheet"
        href="assests/css/login.css"
    >

</head>

<body>

<div class="login-page">

    <div class="login-card">

        <!-- Brand -->

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


        <!-- Heading -->

        <div class="login-heading">

            <h2>Welcome back</h2>

            <p>
                Login to continue to your StudentNest account.
            </p>

        </div>


        <!-- Error -->

        <?php if ($error !== ""): ?>

            <div class="message error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- Login form -->

        <form method="POST" action="">

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

                <div class="password-label">

                    <label for="password">
                        Password
                    </label>

                    <a href="#">
                        Forgot password?
                    </a>

                </div>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="primary-btn"
            >
                Login
            </button>

        </form>


        <!-- Social login placeholder -->

        <div class="divider">

            <span>OR</span>

        </div>


        <button
            type="button"
            class="social-btn"
            disabled
        >
            <span>G</span>
            Continue with Google
        </button>


        <button
            type="button"
            class="social-btn"
            disabled
        >
            <span>f</span>
            Continue with Facebook
        </button>


        <!-- Register -->

        <div class="bottom-text">

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </div>

    </div>

</div>

</body>

</html>