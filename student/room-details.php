
<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "student"
) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$student_id = (int) $_SESSION["user_id"];
$room_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($room_id <= 0) {
    header("Location: search-rooms.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        r.id,
        r.owner_id,
        r.title,
        r.description,
        r.location,
        r.rent,
        r.room_type,
        r.facilities,
        r.status,
        u.name AS owner_name,
        u.email AS owner_email
     FROM rooms r
     INNER JOIN users u ON u.id = r.owner_id
     WHERE r.id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $room_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: search-rooms.php");
    exit;
}

$room = $result->fetch_assoc();

$stmt->close();

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

$is_saved = false;

$check_table = $conn->query("SHOW TABLES LIKE 'saved_rooms'");

if ($check_table && $check_table->num_rows > 0) {

    $saved_stmt = $conn->prepare(
        "SELECT id
         FROM saved_rooms
         WHERE student_id = ? AND room_id = ?
         LIMIT 1"
    );

    if ($saved_stmt) {

        $saved_stmt->bind_param(
            "ii",
            $student_id,
            $room_id
        );

        $saved_stmt->execute();

        $saved_result = $saved_stmt->get_result();

        $is_saved = $saved_result->num_rows === 1;

        $saved_stmt->close();
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

<title>
<?= htmlspecialchars($room["title"]) ?> | StudentNest
</title>

<link rel="stylesheet" href="../assests/css/student-pages.css?v=5">
<link rel="stylesheet" href="../assests/css/room-details.css?v=1">

</head>

<body>

<div class="page">

<div class="page-header">

<div>

<span class="label">
Student Portal
</span>

<h1>
<?= htmlspecialchars($room["title"]) ?>
</h1>

<p>
<?= htmlspecialchars($room["location"]) ?>
</p>

</div>

<a
    href="search-rooms.php"
    class="back-btn"
>
← Find Rooms
</a>

</div>

<div class="room-details-page">

<div class="details-images">

<?php if (count($images) > 0): ?>

<?php foreach ($images as $image): ?>

<img
    src="../<?= htmlspecialchars($image["image_path"]) ?>"
    alt="<?= htmlspecialchars($room["title"]) ?>"
>

<?php endforeach; ?>

<?php else: ?>

<div class="no-image">
No Image Available
</div>

<?php endif; ?>

</div>

<div class="details-card">

<h2>
<?= htmlspecialchars($room["title"]) ?>
</h2>

<p class="location">
📍 <?= htmlspecialchars($room["location"]) ?>
</p>

<div class="details-list">

<div>

<strong>
Room Type
</strong>

<span>
<?= htmlspecialchars($room["room_type"]) ?>
</span>

</div>

<div>

<strong>
Monthly Rent
</strong>

<span>
Rs. <?= number_format((float)$room["rent"]) ?>
</span>

</div>

<div>

<strong>
Facilities
</strong>

<span>
<?= htmlspecialchars(
    $room["facilities"] ?: "Not specified"
) ?>
</span>

</div>

<div>

<strong>
Status
</strong>

<span>
<?= htmlspecialchars(
    ucfirst($room["status"])
) ?>
</span>

</div>

</div>

<h3>
Description
</h3>

<p class="description">
<?= nl2br(
    htmlspecialchars($room["description"])
) ?>
</p>

<div class="owner-box">

<h3>
Room Owner
</h3>

<p>
<strong>
<?= htmlspecialchars($room["owner_name"]) ?>
</strong>
</p>

<p>
<?= htmlspecialchars($room["owner_email"]) ?>
</p>

</div>

<div class="details-actions">

<?php if ($is_saved): ?>

<a
    href="saved-rooms.php"
    class="view-btn"
>
Saved Room
</a>

<?php else: ?>

<a
    href="save-room.php?id=<?= $room_id ?>"
    class="view-btn"
>
Save Room
</a>

<?php endif; ?>

<a
    href="messages.php?owner_id=<?= (int)$room["owner_id"] ?>&room_id=<?= $room_id ?>"
    class="view-btn"
>
Contact Owner
</a>

</div>

</div>

</div>

</div>

</body>

</html>
```
