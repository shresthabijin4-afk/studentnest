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

$owner_id = (int)$_SESSION["user_id"];
$room_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($room_id <= 0) {
    header("Location: manage-room.php?error=Invalid room");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id
     FROM rooms
     WHERE id = ?
     AND owner_id = ?
     LIMIT 1"
);

$stmt->bind_param(
    "ii",
    $room_id,
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();

    header(
        "Location: manage-room.php?error=Room not found"
    );

    exit;
}

$stmt->close();

$image_stmt = $conn->prepare(
    "SELECT image_path
     FROM room_images
     WHERE room_id = ?"
);

$image_stmt->bind_param(
    "i",
    $room_id
);

$image_stmt->execute();

$image_result = $image_stmt->get_result();

$image_paths = [];

while ($image = $image_result->fetch_assoc()) {
    $image_paths[] = $image["image_path"];
}

$image_stmt->close();

$conn->begin_transaction();

try {

    $delete_images = $conn->prepare(
        "DELETE FROM room_images
         WHERE room_id = ?"
    );

    $delete_images->bind_param(
        "i",
        $room_id
    );

    $delete_images->execute();
    $delete_images->close();

    $delete_room = $conn->prepare(
        "DELETE FROM rooms
         WHERE id = ?
         AND owner_id = ?"
    );

    $delete_room->bind_param(
        "ii",
        $room_id,
        $owner_id
    );

    $delete_room->execute();

    if ($delete_room->affected_rows !== 1) {
        throw new Exception("Room could not be deleted.");
    }

    $delete_room->close();

    $conn->commit();

    foreach ($image_paths as $image_path) {

        $file_path = __DIR__ . "/../" . $image_path;

        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    header(
        "Location: manage-room.php?success=Room deleted successfully"
    );

    exit;

} catch (Exception $e) {

    $conn->rollback();

    header(
        "Location: manage-room.php?error=Unable to delete room"
    );

    exit;
}