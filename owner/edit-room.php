```php
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
$room_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($room_id <= 0) {
    header("Location: manage-room.php?error=Invalid room");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, title, description, location, rent, room_type, facilities, status
     FROM rooms
     WHERE id = ? AND owner_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $room_id, $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: manage-room.php?error=Room not found");
    exit;
}

$room = $result->fetch_assoc();
$stmt->close();

$error = "";
$success = "";

$title = $room["title"];
$description = $room["description"];
$location = $room["location"];
$rent = $room["rent"];
$room_type = $room["room_type"];
$facilities = $room["facilities"] ?? "";
$status = $room["status"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $rent = trim($_POST["rent"] ?? "");
    $room_type = $_POST["room_type"] ?? "";
    $facilities = trim($_POST["facilities"] ?? "");
    $status = $_POST["status"] ?? "";

    if (
        $title === "" ||
        $description === "" ||
        $location === "" ||
        $rent === "" ||
        $room_type === "" ||
        $status === ""
    ) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($rent) || (float)$rent <= 0) {
        $error = "Please enter a valid monthly rent.";
    } elseif (
        !in_array(
            $room_type,
            ["single", "shared", "1BHK", "2BHK", "other"],
            true
        )
    ) {
        $error = "Invalid room type.";
    } elseif (
        !in_array(
            $status,
            ["available", "unavailable"],
            true
        )
    ) {
        $error = "Invalid room status.";
    } else {

        $rent_value = (float)$rent;

        $update = $conn->prepare(
            "UPDATE rooms
             SET title = ?,
                 description = ?,
                 location = ?,
                 rent = ?,
                 room_type = ?,
                 facilities = ?,
                 status = ?
             WHERE id = ?
             AND owner_id = ?"
        );

        if (!$update) {

            $error = "Unable to prepare the update.";

        } else {

            $update->bind_param(
                "sssdsssii",
                $title,
                $description,
                $location,
                $rent_value,
                $room_type,
                $facilities,
                $status,
                $room_id,
                $owner_id
            );

            if (!$update->execute()) {

                $error = "Unable to update room listing.";

            } else {

                $allowed_types = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                $image_stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM room_images
                     WHERE room_id = ?"
                );

                $image_stmt->bind_param("i", $room_id);
                $image_stmt->execute();

                $image_result = $image_stmt->get_result();
                $image_count = (int)$image_result->fetch_assoc()["total"];

                $image_stmt->close();

                $remaining_slots = 5 - $image_count;

                if (
                    $remaining_slots > 0 &&
                    isset($_FILES["images"]) &&
                    isset($_FILES["images"]["name"]) &&
                    is_array($_FILES["images"]["name"])
                ) {

                    $upload_dir = __DIR__ . "/../assets/images/rooms/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $total_files = count($_FILES["images"]["name"]);
                    $files_to_process = min(
                        $total_files,
                        $remaining_slots
                    );

                    for ($i = 0; $i < $files_to_process; $i++) {

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
                            $upload_dir . $filename;

                        if (
                            move_uploaded_file(
                                $tmp_name,
                                $destination
                            )
                        ) {

                            $image_path =
                                "assets/images/rooms/"
                                . $filename;

                            $is_primary = 0;

                            $insert_image = $conn->prepare(
                                "INSERT INTO room_images
                                (room_id, image_path, is_primary)
                                VALUES (?, ?, ?)"
                            );

                            if ($insert_image) {

                                $insert_image->bind_param(
                                    "isi",
                                    $room_id,
                                    $image_path,
                                    $is_primary
                                );

                                if ($insert_image->execute()) {
                                    $image_count++;
                                } else {
                                    unlink($destination);
                                }

                                $insert_image->close();
                            }
                        }
                    }
                }

                $success = "Room listing updated successfully.";
            }

            $update->close();
        }
    }
}

$image_stmt = $conn->prepare(
    "SELECT id, image_path, is_primary
     FROM room_images
     WHERE room_id = ?
     ORDER BY is_primary DESC, id ASC"
);

$image_stmt->bind_param("i", $room_id);
$image_stmt->execute();

$image_result = $image_stmt->get_result();

$images = [];

while ($image = $image_result->fetch_assoc()) {
    $images[] = $image;
}

$image_stmt->close();

$image_total = count($images);
$remaining_slots = max(0, 5 - $image_total);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Room | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/edit-room.css?v=5"
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
                    Edit Room
                </h1>

                <p>
                    Update the details of your accommodation listing.
                </p>

            </div>

            <a
                href="manage-room.php"
                class="back-btn"
            >
                ← Manage Rooms
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

                    <label for="status">
                        Availability *
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="available"
                            <?= $status === "available" ? "selected" : "" ?>
                        >
                            Available
                        </option>

                        <option
                            value="unavailable"
                            <?= $status === "unavailable" ? "selected" : "" ?>
                        >
                            Unavailable
                        </option>

                    </select>

                </div>

                <div class="form-group full">

                    <label for="facilities">
                        Facilities
                    </label>

                    <input
                        type="text"
                        id="facilities"
                        name="facilities"
                        value="<?= htmlspecialchars($facilities) ?>"
                        placeholder="Wi-Fi, Water, Parking, Kitchen..."
                    >

                </div>

                <div class="form-group full">

                    <label for="description">
                        Description *
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="7"
                        required
                    ><?= htmlspecialchars($description) ?></textarea>

                </div>

                <div class="form-group full">

                    <label>
                        Current Room Images
                    </label>

                    <?php if ($image_total > 0): ?>

                        <div class="image-grid">

                            <?php foreach ($images as $image): ?>

                                <div class="image-item">

                                    <img
                                        src="../<?= htmlspecialchars($image["image_path"]) ?>"
                                        alt="Room image"
                                    >

                                    <?php if ((int)$image["is_primary"] === 1): ?>

                                        <span class="primary-label">
                                            Primary
                                        </span>

                                    <?php endif; ?>

                                    <div class="image-actions">

                                        <?php if ((int)$image["is_primary"] !== 1): ?>

                                            <a
                                                href="set-primary-image.php?id=<?= (int)$image["id"] ?>&room_id=<?= $room_id ?>"
                                                class="image-btn primary-image-btn"
                                            >
                                                Set Primary
                                            </a>

                                        <?php endif; ?>

                                        <a
                                            href="delete-room-image.php?id=<?= (int)$image["id"] ?>&room_id=<?= $room_id ?>"
                                            class="image-btn delete-btn"
                                            onclick="return confirm('Delete this image?')"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p class="no-images">
                            No images uploaded for this room.
                        </p>

                    <?php endif; ?>

                </div>

                <div class="form-group full">

                    <label for="images">
                        Add New Images
                    </label>

                    <?php if ($remaining_slots > 0): ?>

                        <input
                            type="file"
                            id="images"
                            name="images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >

                        <small>
                            <?= $remaining_slots ?> image slot<?= $remaining_slots === 1 ? "" : "s" ?> remaining.
                            JPG, PNG, or WEBP. Maximum 5 MB per image.
                        </small>

                    <?php else: ?>

                        <small>
                            Maximum of 5 images reached. Delete an existing image to upload a new one.
                        </small>

                    <?php endif; ?>

                </div>

            </div>

            <div class="form-actions">

                <a
                    href="manage-room.php"
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

    </div>

</div>

</body>

</html>
```
