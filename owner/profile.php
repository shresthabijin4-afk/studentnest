<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "owner"
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$owner_id = (int) $_SESSION["user_id"];

$error = "";
$success = "";

$photo_error = "";
$photo_success = "";

$password_error = "";
$password_success = "";

$upload_dir = __DIR__ . "/../assets/images/owners/";
$upload_url = "../assets/images/owners/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$stmt = $conn->prepare(
    "SELECT id, name, email, profile_image
     FROM users
     WHERE id = ? AND role = 'owner'
     LIMIT 1"
);

$stmt->bind_param("i", $owner_id);
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

    $action = $_POST["action"] ?? "update_info";


    // Update name

    if ($action === "update_info") {

        $name = trim($_POST["name"] ?? "");

        if ($name === "") {

            $error = "Name is required.";

        } elseif (strlen($name) < 2) {

            $error = "Name must contain at least 2 characters.";

        } else {

            $update = $conn->prepare(
                "UPDATE users
                 SET name = ?
                 WHERE id = ? AND role = 'owner'"
            );

            if (!$update) {

                $error = "Unable to update profile.";

            } else {

                $update->bind_param(
                    "si",
                    $name,
                    $owner_id
                );

                if ($update->execute()) {

                    $_SESSION["user_name"] = $name;

                    $success = "Profile updated successfully.";

                } else {

                    $error = "Unable to update profile.";
                }

                $update->close();
            }
        }
    }


    // Update profile photo

    if ($action === "update_photo") {

        if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] === UPLOAD_ERR_NO_FILE) {

            $photo_error = "Please choose a photo to upload.";

        } elseif ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {

            $photo_error = "There was a problem uploading your photo.";

        } else {

            $allowed_types = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp",
            ];

            $file_type = mime_content_type($_FILES["photo"]["tmp_name"]);

            if (!isset($allowed_types[$file_type])) {

                $photo_error = "Only JPG, PNG, or WEBP images are allowed.";

            } elseif ($_FILES["photo"]["size"] > 2 * 1024 * 1024) {

                $photo_error = "Photo must be smaller than 2MB.";

            } else {

                $extension = $allowed_types[$file_type];
                $filename = "owner_" . $owner_id . "_" . uniqid() . "." . $extension;

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $filename)) {

                    $old_image = $user["profile_image"];

                    $update = $conn->prepare(
                        "UPDATE users
                         SET profile_image = ?
                         WHERE id = ? AND role = 'owner'"
                    );

                    $update->bind_param("si", $filename, $owner_id);

                    if ($update->execute()) {

                        $user["profile_image"] = $filename;

                        if (!empty($old_image) && file_exists($upload_dir . $old_image)) {
                            unlink($upload_dir . $old_image);
                        }

                        $photo_success = "Profile photo updated successfully.";

                    } else {

                        $photo_error = "Unable to save your new photo. Please try again.";
                    }

                    $update->close();

                } else {

                    $photo_error = "Unable to upload your photo. Please try again.";
                }
            }
        }
    }


    // Change password

    if ($action === "change_password") {

        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if ($current_password === "" || $new_password === "" || $confirm_password === "") {

            $password_error = "Please fill in all password fields.";

        } elseif (strlen($new_password) < 6) {

            $password_error = "New password must be at least 6 characters long.";

        } elseif ($new_password !== $confirm_password) {

            $password_error = "New passwords do not match.";

        } else {

            $pass_stmt = $conn->prepare(
                "SELECT password
                 FROM users
                 WHERE id = ? AND role = 'owner'
                 LIMIT 1"
            );

            $pass_stmt->bind_param("i", $owner_id);
            $pass_stmt->execute();

            $pass_row = $pass_stmt->get_result()->fetch_assoc();
            $pass_stmt->close();

            if (!$pass_row || !password_verify($current_password, $pass_row["password"])) {

                $password_error = "Your current password is incorrect.";

            } else {

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update = $conn->prepare(
                    "UPDATE users
                     SET password = ?
                     WHERE id = ? AND role = 'owner'"
                );

                $update->bind_param("si", $hashed_password, $owner_id);

                if ($update->execute()) {

                    $password_success = "Password changed successfully.";

                } else {

                    $password_error = "Unable to change your password. Please try again.";
                }

                $update->close();
            }
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

    <title>My Profile | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/profile.css?v=3"
    >

</head>

<body>

<div class="page">

    <div class="profile-card">

        <div class="top">

            <div>

                <span class="label">
                    Owner Portal
                </span>

                <h1>
                    My Profile
                </h1>

                <p>
                    View and update your account information.
                </p>

            </div>

            <a
                href="dashboard.php"
                class="back-btn"
            >
                ← Dashboard
            </a>

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

        <div class="profile-header">

            <?php if (!empty($user["profile_image"])): ?>

                <img
                    src="<?= $upload_url . htmlspecialchars($user["profile_image"]) ?>"
                    alt="Profile photo"
                    class="avatar"
                    style="object-fit: cover;"
                >

            <?php else: ?>

                <div class="avatar">
                    <?= htmlspecialchars(strtoupper(substr($name, 0, 1))) ?>
                </div>

            <?php endif; ?>

            <div>

                <h2>
                    <?= htmlspecialchars($name) ?>
                </h2>

                <p>
                    Room Owner
                </p>

            </div>

        </div>

        <form method="POST">

            <input type="hidden" name="action" value="update_info">

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
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
                    value="<?= htmlspecialchars($email) ?>"
                    disabled
                >

                <small>
                    Email address cannot be changed here.
                </small>

            </div>

            <div class="form-actions">

                <a
                    href="dashboard.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Save Changes
                </button>

            </div>

        </form>


        <div style="margin-top: 34px; padding-top: 30px; border-top: 1px solid var(--border);">

            <h2 style="font-size: 18px; margin-bottom: 20px;">
                Profile Photo
            </h2>

        <?php if ($photo_error !== ""): ?>

            <div class="message error">
                <?= htmlspecialchars($photo_error) ?>
            </div>

        <?php endif; ?>

        <?php if ($photo_success !== ""): ?>

            <div class="message success">
                <?= htmlspecialchars($photo_success) ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input type="hidden" name="action" value="update_photo">

            <div class="form-group">

                <label for="photo">
                    Upload New Photo
                </label>

                <input
                    type="file"
                    id="photo"
                    name="photo"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <small>
                    JPG, PNG, or WEBP. Maximum 2MB.
                </small>

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Update Photo
                </button>

            </div>

        </form>

        </div>


        <div style="margin-top: 34px; padding-top: 30px; border-top: 1px solid var(--border);">

            <h2 style="font-size: 18px; margin-bottom: 20px;">
                Change Password
            </h2>

        <?php if ($password_error !== ""): ?>

            <div class="message error">
                <?= htmlspecialchars($password_error) ?>
            </div>

        <?php endif; ?>

        <?php if ($password_success !== ""): ?>

            <div class="message success">
                <?= htmlspecialchars($password_success) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input type="hidden" name="action" value="change_password">

            <div class="form-group">

                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    placeholder="Minimum 6 characters"
                    required
                >

            </div>

            <div class="form-group">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="submit-btn"
                >
                    Change Password
                </button>

            </div>

        </form>

        </div>

    </div>

</div>

</body>

</html>