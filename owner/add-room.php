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

$error = "";
$success = "";

$title = "";
$description = "";
$location = "";
$rent = "";
$room_type = "";
$facilities = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $owner_id = (int) $_SESSION["user_id"];

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $rent = trim($_POST["rent"] ?? "");
    $room_type = $_POST["room_type"] ?? "";
    $facilities = trim($_POST["facilities"] ?? "");

    if (
        $title === "" ||
        $description === "" ||
        $location === "" ||
        $rent === "" ||
        $room_type === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!is_numeric($rent) || (float) $rent <= 0) {

        $error = "Please enter a valid monthly rent.";

    } elseif (
        !in_array(
            $room_type,
            ["single", "shared", "1BHK", "2BHK", "other"],
            true
        )
    ) {

        $error = "Please select a valid room type.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO rooms
            (owner_id, title, description, location, rent, room_type, facilities, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'available')"
        );

        if (!$stmt) {

            $error = "Unable to create room listing.";

        } else {

            $rent_value = (float) $rent;

            $stmt->bind_param(
                "isssdss",
                $owner_id,
                $title,
                $description,
                $location,
                $rent_value,
                $room_type,
                $facilities
            );

            if ($stmt->execute()) {

                $room_id = $conn->insert_id;

                $upload_dir = __DIR__ . "/../assets/images/rooms/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (
                    isset($_FILES["images"]) &&
                    isset($_FILES["images"]["name"]) &&
                    !empty($_FILES["images"]["name"][0])
                ) {

                    $allowed_types = [
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ];

                    $image_count = min(
                        count($_FILES["images"]["name"]),
                        5
                    );

                    $uploaded_images = 0;

                    for ($i = 0; $i < $image_count; $i++) {

                        if (
                            $_FILES["images"]["error"][$i]
                            !== UPLOAD_ERR_OK
                        ) {
                            continue;
                        }

                        if (
                            $_FILES["images"]["size"][$i]
                            > 5 * 1024 * 1024
                        ) {
                            continue;
                        }

                        $tmp_name = $_FILES["images"]["tmp_name"][$i];

                        $file_type = mime_content_type($tmp_name);

                        if (
                            !in_array(
                                $file_type,
                                $allowed_types,
                                true
                            )
                        ) {
                            continue;
                        }

                        $extension = match ($file_type) {
                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp",
                            default => ""
                        };

                        if ($extension === "") {
                            continue;
                        }

                        $filename =
                            uniqid("room_", true)
                            . "."
                            . $extension;

                        $destination =
                            $upload_dir
                            . $filename;

                        if (
                            move_uploaded_file(
                                $tmp_name,
                                $destination
                            )
                        ) {

                            $image_path =
                                "assets/images/rooms/"
                                . $filename;

                            $is_primary =
                                ($uploaded_images === 0)
                                ? 1
                                : 0;

                            $image_stmt = $conn->prepare(
                                "INSERT INTO room_images
                                (room_id, image_path, is_primary)
                                VALUES (?, ?, ?)"
                            );

                            if ($image_stmt) {

                                $image_stmt->bind_param(
                                    "isi",
                                    $room_id,
                                    $image_path,
                                    $is_primary
                                );

                                $image_stmt->execute();

                                $image_stmt->close();

                                $uploaded_images++;
                            }
                        }
                    }
                }

                $success =
                    "Room listing created successfully.";

                $title = "";
                $description = "";
                $location = "";
                $rent = "";
                $room_type = "";
                $facilities = "";

            } else {

                $error =
                    "Unable to create room listing. Please try again.";
            }

            $stmt->close();
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

    <title>Add Room | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/add-room.css"
    >

</head>

<body>

<div class="page">

    <div class="form-card">

        <div class="top">

            <div>

                <span class="label">
                    Room Owner
                </span>

                <h1>
                    Add New Room
                </h1>

                <p>
                    Add the details of your available accommodation.
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

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-grid">

                <div class="form-group full">

                    <label for="title">
                        Room Title *
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        placeholder="Example: Modern Single Room near College"
                        value="<?= htmlspecialchars($title) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="location">
                        Location *
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        placeholder="Example: Dharan, Sunsari"
                        value="<?= htmlspecialchars($location) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="rent">
                        Monthly Rent (Rs.) *
                    </label>

                    <input
                        type="number"
                        id="rent"
                        name="rent"
                        placeholder="8000"
                        min="1"
                        step="0.01"
                        value="<?= htmlspecialchars($rent) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="room_type">
                        Room Type *
                    </label>

                    <select
                        id="room_type"
                        name="room_type"
                        required
                    >

                        <option value="">
                            Select room type
                        </option>

                        <option
                            value="single"
                            <?= $room_type === "single" ? "selected" : "" ?>
                        >
                            Single Room
                        </option>

                        <option
                            value="shared"
                            <?= $room_type === "shared" ? "selected" : "" ?>
                        >
                            Shared Room
                        </option>

                        <option
                            value="1BHK"
                            <?= $room_type === "1BHK" ? "selected" : "" ?>
                        >
                            1BHK
                        </option>

                        <option
                            value="2BHK"
                            <?= $room_type === "2BHK" ? "selected" : "" ?>
                        >
                            2BHK
                        </option>

                        <option
                            value="other"
                            <?= $room_type === "other" ? "selected" : "" ?>
                        >
                            Other
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="facilities">
                        Facilities
                    </label>

                    <input
                        type="text"
                        id="facilities"
                        name="facilities"
                        placeholder="Wi-Fi, Water, Parking, Kitchen..."
                        value="<?= htmlspecialchars($facilities) ?>"
                    >

                </div>

                <div class="form-group full">

                    <label for="description">
                        Description *
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        placeholder="Describe the room, facilities, nearby locations and other important information..."
                        required
                    ><?= htmlspecialchars($description) ?></textarea>

                </div>

                <div class="form-group full">

                    <label for="images">
                        Room Images
                    </label>

                    <input
                        type="file"
                        id="images"
                        name="images[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    >

                    <small>
                        Select up to 5 JPG, PNG, or WEBP images. Maximum 5 MB per image.
                    </small>

                </div>

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
                    Create Room Listing
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>