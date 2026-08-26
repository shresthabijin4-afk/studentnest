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
$room_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($room_id <= 0) {
    header("Location: search-rooms.php");
    exit;
}

$check = $conn->query("SHOW TABLES LIKE 'saved_rooms'");

if (!$check || $check->num_rows === 0) {
    header("Location: room-details.php?id=" . $room_id . "&error=Saved rooms are not available");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id
     FROM saved_rooms
     WHERE student_id = ? AND room_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $student_id, $room_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    $insert = $conn->prepare(
        "INSERT INTO saved_rooms (student_id, room_id)
         VALUES (?, ?)"
    );

    $insert->bind_param("ii", $student_id, $room_id);
    $insert->execute();
    $insert->close();

} else {

    $stmt->close();
}

header("Location: saved-rooms.php");
exit;