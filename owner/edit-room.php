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
    header("Location: manage-rooms.php?error=Invalid room");
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
    header("Location: manage-rooms.php?error=Room not found");
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
$facilities = $room["facilities"];
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

    } elseif (!in_array($room_type, ["single", "shared", "1BHK", "2BHK", "other"], true)) {

        $error = "Invalid room type.";

    } elseif (!in_array($status, ["available", "unavailable"], true)) {

        $error = "Invalid room status.";

    } else {

        $rent_value = (float) $rent;

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

            if ($update->execute()) {

                $success = "Room listing updated successfully.";

            } else {

                $error = "Unable to update room listing.";
            }

            $update->close();
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

    <title>Edit Room | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/edit-room.css"
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
                href="manage-rooms.php"
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

        <form method="POST">

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

                        <option value="single" <?= $room_type === "single" ? "selected" : "" ?>>
                            Single Room
                        </option>

                        <option value="shared" <?= $room_type === "shared" ? "selected" : "" ?>>
                            Shared Room
                        </option>

                        <option value="1BHK" <?= $room_type === "1BHK" ? "selected" : "" ?>>
                            1BHK
                        </option>

                        <option value="2BHK" <?= $room_type === "2BHK" ? "selected" : "" ?>>
                            2BHK
                        </option>

                        <option value="other" <?= $room_type === "other" ? "selected" : "" ?>>
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

                        <option value="available" <?= $status === "available" ? "selected" : "" ?>>
                            Available
                        </option>

                        <option value="unavailable" <?= $status === "unavailable" ? "selected" : "" ?>>
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
                        value="<?= htmlspecialchars($facilities ?? "") ?>"
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

            </div>

            <div class="form-actions">

                <a
                    href="manage-rooms.php"
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