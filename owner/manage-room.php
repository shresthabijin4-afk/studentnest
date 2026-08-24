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

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

$stmt = $conn->prepare(
    "SELECT
        r.id,
        r.title,
        r.description,
        r.location,
        r.rent,
        r.room_type,
        r.facilities,
        r.status,
        r.created_at,
        (
            SELECT ri.image_path
            FROM room_images ri
            WHERE ri.room_id = r.id
            ORDER BY ri.is_primary DESC, ri.id ASC
            LIMIT 1
        ) AS image_path
     FROM rooms r
     WHERE r.owner_id = ?
     ORDER BY r.id DESC"
);

$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();

$rooms = [];

while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Rooms | StudentNest</title>

  <link
    rel="stylesheet"
    href="../assests/css/manage-room.css?v=2"
>

</head>

<body>

<div class="page">

    <div class="page-header">

        <div>

            <span class="label">
                Owner Portal
            </span>

            <h1>
                Manage Rooms
            </h1>

            <p>
                View, edit and manage your accommodation listings.
            </p>

        </div>

        <a
            href="add-room.php"
            class="add-btn"
        >
            + Add New Room
        </a>

    </div>

    <?php if ($success !== ""): ?>

        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <?php if (count($rooms) === 0): ?>

        <div class="empty-state">

            <div class="empty-icon">
                🏠
            </div>

            <h2>
                No rooms listed yet
            </h2>

            <p>
                Add your first room to start receiving accommodation requests.
            </p>

            <a
                href="add-room.php"
                class="add-btn"
            >
                + Add Your First Room
            </a>

        </div>

    <?php else: ?>

        <div class="rooms-grid">

            <?php foreach ($rooms as $room): ?>

                <div class="room-card">

                    <div class="room-image">

                        <?php if (!empty($room["image_path"])): ?>

                            <img
                                src="../<?= htmlspecialchars($room["image_path"]) ?>"
                                alt="<?= htmlspecialchars($room["title"]) ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No Image
                            </div>

                        <?php endif; ?>

                        <span
                            class="status <?= $room["status"] === "available" ? "available" : "unavailable" ?>"
                        >
                            <?= htmlspecialchars(ucfirst($room["status"])) ?>
                        </span>

                    </div>

                    <div class="room-content">

                        <h2>
                            <?= htmlspecialchars($room["title"]) ?>
                        </h2>

                        <p class="location">
                            <?= htmlspecialchars($room["location"]) ?>
                        </p>

                        <div class="room-info">

                            <div>
                                <span>
                                    Room Type
                                </span>

                                <strong>
                                    <?= htmlspecialchars($room["room_type"]) ?>
                                </strong>
                            </div>

                            <div>
                                <span>
                                    Monthly Rent
                                </span>

                                <strong>
                                    Rs. <?= number_format((float)$room["rent"]) ?>
                                </strong>
                            </div>

                        </div>

                        <?php if (!empty($room["facilities"])): ?>

                            <p class="facilities">
                                <?= htmlspecialchars($room["facilities"]) ?>
                            </p>

                        <?php endif; ?>

                        <div class="room-actions">

                            <a
                                href="edit-room.php?id=<?= (int)$room["id"] ?>"
                                class="action-btn edit"
                            >
                                Edit
                            </a>

                            <a
    href="delete-room.php?id=<?= (int)$room["id"] ?>"
    class="action-btn delete"
    onclick="return confirm('Are you sure you want to delete this room and all its images?')"
>
    Delete
</a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>

</html>