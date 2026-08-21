<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
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

    $owner_id = $_SESSION["user_id"];

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

    } elseif (!is_numeric($rent) || (float)$rent <= 0) {

        $error = "Please enter a valid monthly rent.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO rooms
            (owner_id, title, description, location, rent, room_type, facilities, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'available')"
        );

        $rent_value = (float)$rent;

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

            $success = "Room listing created successfully.";

            $title = "";
            $description = "";
            $location = "";
            $rent = "";
            $room_type = "";
            $facilities = "";

        } else {

            $error = "Unable to create room listing. Please try again.";
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

                <h1>Add New Room</h1>

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

                    <label>
                        Room Images
                    </label>

                    <div class="upload-box">

                        <span>📷</span>

                        <strong>
                            Image upload will be added next
                        </strong>

                        <small>
                            Multiple room photos will be supported.
                        </small>

                    </div>

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