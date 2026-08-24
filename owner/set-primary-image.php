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
$image_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$room_id = isset($_GET["room_id"]) ? (int) $_GET["room_id"] : 0;

if ($image_id <= 0 || $room_id <= 0) {
    header("Location: manage-room.php?error=Invalid image");
    exit;
}

$stmt = $conn->prepare(
    "SELECT ri.id
     FROM room_images ri
     INNER JOIN rooms r ON r.id = ri.room_id
     WHERE ri.id = ?
     AND ri.room_id = ?
     AND r.owner_id = ?
     LIMIT 1"
);

$stmt->bind_param("iii", $image_id, $room_id, $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: edit-room.php?id=$room_id&error=Image not found");
    exit;
}

$stmt->close();

$conn->begin_transaction();

try {

    $reset = $conn->prepare(
        "UPDATE room_images
         SET is_primary = 0
         WHERE room_id = ?"
    );

    $reset->bind_param("i", $room_id);
    $reset->execute();
    $reset->close();

    $set = $conn->prepare(
        "UPDATE room_images
         SET is_primary = 1
         WHERE id = ?
         AND room_id = ?"
    );

    $set->bind_param("ii", $image_id, $room_id);

    if (!$set->execute()) {
        throw new Exception("Unable to set primary image.");
    }

    $set->close();

    $conn->commit();

    header(
        "Location: edit-room.php?id=$room_id&success=Primary image updated successfully"
    );
    exit;

} catch (Exception $e) {

    $conn->rollback();

    header(
        "Location: edit-room.php?id=$room_id&error=Unable to update primary image"
    );
    exit;
}