<?php

session_start();

require_once __DIR__ . "/../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "owner"
) {
    header("Location: ../login.php");
    exit;
}

$owner_id = $_SESSION["user_id"];
$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

$stmt = $conn->prepare(
    "SELECT id, title, location, rent, room_type, status, created_at
     FROM rooms
     WHERE owner_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();

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
        href="../assests/css/manage-room.css"
    >

</head>

<body>

<div class="page">

    <div class="page-header">

        <div>
            <span class="eyebrow">Room Owner</span>

            <h1>Manage Rooms</h1>

            <p>
                View and manage your accommodation listings.
            </p>
        </div>

        <div class="header-actions">

            <a href="dashboard.php" class="secondary-btn">
                ← Dashboard
            </a>

            <a href="add-room.php" class="primary-btn">
                + Add Room
            </a>

        </div>

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


    <div class="rooms-container">

        <?php if ($result->num_rows > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>
                        <th>Room</th>
                        <th>Location</th>
                        <th>Rent</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php while ($room = $result->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <div class="room-title">

                                    <div class="room-placeholder">
                                        🏠
                                    </div>

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars($room["title"]) ?>
                                        </strong>

                                    </div>

                                </div>

                            </td>


                            <td>
                                <?= htmlspecialchars($room["location"]) ?>
                            </td>


                            <td>
                                Rs. <?= number_format((float)$room["rent"], 2) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(ucfirst($room["room_type"])) ?>
                            </td>


                            <td>

                                <?php if ($room["status"] === "available"): ?>

                                    <span class="status available">
                                        Available
                                    </span>

                                <?php else: ?>

                                    <span class="status unavailable">
                                        Unavailable
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?= date("d M Y", strtotime($room["created_at"])) ?>
                            </td>


                            <td>

                                <div class="actions">

                                    <a
                                        href="edit-room.php?id=<?= (int)$room["id"] ?>"
                                        class="action-btn edit"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="delete-room.php?id=<?= (int)$room["id"] ?>"
                                        class="action-btn delete"
                                        onclick="return confirm('Are you sure you want to delete this room listing?');"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    🏠
                </div>

                <h2>No room listings yet</h2>

                <p>
                    You haven't added any rooms. Create your first listing to make it visible to students.
                </p>

                <a href="add-room.php" class="primary-btn">
                    + Add Your First Room
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>

<?php

$stmt->close();

?>