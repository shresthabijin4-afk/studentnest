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
$image_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$room_id = isset($_GET["room_id"]) ? (int)$_GET["room_id"] : 0;

if ($image_id <= 0 || $room_id <= 0) {
    header("Location: manage-room.php?error=Invalid request");
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        ri.id,
        ri.image_path,
        ri.is_primary
     FROM room_images ri
     INNER JOIN rooms r ON r.id = ri.room_id
     WHERE ri.id = ?
     AND ri.room_id = ?
     AND r.owner_id = ?
     LIMIT 1"
);

$stmt->bind_param(
    "iii",
    $image_id,
    $room_id,
    $owner_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header(
        "Location: edit-room.php?id=" .
        $room_id .
        "&error=Image not found"
    );
    exit;
}

$image = $result->fetch_assoc();

$stmt->close();

$image_path = $image["image_path"];
$is_primary = (int)$image["is_primary"];

$delete = $conn->prepare(
    "DELETE FROM room_images
     WHERE id = ?
     AND room_id = ?"
);

$delete->bind_param(
    "ii",
    $image_id,
    $room_id
);

if (!$delete->execute()) {
    $delete->close();

    header(
        "Location: edit-room.php?id=" .
        $room_id .
        "&error=Unable to delete image"
    );

    exit;
}

$delete->close();

$file_path = __DIR__ . "/../" . $image_path;

if (file_exists($file_path)) {
    unlink($file_path);
}

if ($is_primary === 1) {

    $primary_stmt = $conn->prepare(
        "SELECT id
         FROM room_images
         WHERE room_id = ?
         ORDER BY id ASC
         LIMIT 1"
    );

    $primary_stmt->bind_param(
        "i",
        $room_id
    );

    $primary_stmt->execute();

    $primary_result = $primary_stmt->get_result();

    if ($primary_result->num_rows === 1) {

        $new_primary = $primary_result->fetch_assoc();

        $primary_stmt->close();

        $update_primary = $conn->prepare(
            "UPDATE room_images
             SET is_primary = 1
             WHERE id = ?
             AND room_id = ?"
        );

        $update_primary->bind_param(
            "ii",
            $new_primary["id"],
            $room_id
        );

        $update_primary->execute();
        $update_primary->close();

    } else {
        $primary_stmt->close();
    }
}

header(
    "Location: edit-room.php?id=" .
    $room_id .
    "&success=Image deleted successfully"
);

exit;