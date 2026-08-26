<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "student"
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$student_id = (int)$_SESSION["user_id"];

$rooms = [];

$check = $conn->query("SHOW TABLES LIKE 'saved_rooms'");

if ($check && $check->num_rows > 0) {

    $stmt = $conn->prepare(
        "SELECT
            r.id,
            r.title,
            r.location,
            r.rent,
            r.room_type,
            r.facilities,
            r.status,
            (
                SELECT ri.image_path
                FROM room_images ri
                WHERE ri.room_id = r.id
                ORDER BY ri.is_primary DESC, ri.id ASC
                LIMIT 1
            ) AS image_path
         FROM saved_rooms s
         INNER JOIN rooms r ON r.id = s.room_id
         WHERE s.student_id = ?
         ORDER BY s.id DESC"
    );

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Saved Rooms | StudentNest</title>

<link rel="stylesheet" href="../assests/css/student-pages.css?v=4">

</head>

<body>

<div class="page">

<div class="page-header">

<div>

<span class="label">Student Portal</span>

<h1>Saved Rooms</h1>

<p>Rooms you have saved for later.</p>

</div>

<a href="dashboard.php" class="back-btn">
← Dashboard
</a>

</div>

<?php if (count($rooms) > 0): ?>

<div class="room-grid">

<?php foreach ($rooms as $room): ?>

<article class="room-card">

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

<span class="availability">
<?= htmlspecialchars(ucfirst($room["status"])) ?>
</span>

</div>

<div class="room-body">

<h2><?= htmlspecialchars($room["title"]) ?></h2>

<p class="location">
📍 <?= htmlspecialchars($room["location"]) ?>
</p>

<div class="details">

<span><?= htmlspecialchars($room["room_type"]) ?></span>

<?php if (!empty($room["facilities"])): ?>

<span><?= htmlspecialchars($room["facilities"]) ?></span>

<?php endif; ?>

</div>

<div class="room-footer">

<strong>
Rs. <?= number_format((float)$room["rent"]) ?>/month
</strong>

<a
href="room-details.php?id=<?= (int)$room["id"] ?>"
class="view-btn"
>
View Details
</a>

</div>

</div>

</article>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="empty-state">

<h2>No saved rooms</h2>

<p>Save rooms you like and they will appear here.</p>

<a href="search-rooms.php" class="view-btn">
Find Rooms
</a>

</div>

<?php endif; ?>

</div>

</body>

</html>