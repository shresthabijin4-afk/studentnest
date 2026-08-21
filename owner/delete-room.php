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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: manage-room.php?error=Invalid room ID.");
    exit;
}

$room_id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT id
     FROM rooms
     WHERE id = ? AND owner_id = ?"
);

$stmt->bind_param("ii", $room_id, $owner_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();

    header(
        "Location: manage-rooms.php?error=" .
        urlencode("Room not found or you do not have permission to delete it.")
    );

    exit;
}

$stmt->close();

$delete_stmt = $conn->prepare(
    "DELETE FROM rooms
     WHERE id = ? AND owner_id = ?"
);

$delete_stmt->bind_param("ii", $room_id, $owner_id);

if ($delete_stmt->execute()) {

    $delete_stmt->close();

    header(
        "Location: manage-rooms.php?success=" .
        urlencode("Room listing deleted successfully.")
    );

    exit;

} else {

    $delete_stmt->close();

    header(
        "Location: manage-rooms.php?error=" .
        urlencode("Unable to delete the room. Please try again.")
    );

    exit;
}
?>