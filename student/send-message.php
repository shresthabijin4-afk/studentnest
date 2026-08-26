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

$receiver_id = (int)($_POST["receiver_id"] ?? 0);
$room_id = (int)($_POST["room_id"] ?? 0);
$message = trim($_POST["message"] ?? "");

if ($receiver_id <= 0 || $message === "") {
    header("Location: messages.php");
    exit;
}

$owner_stmt = $conn->prepare(
    "SELECT id
     FROM users
     WHERE id = ?
     AND role = 'owner'
     LIMIT 1"
);

$owner_stmt->bind_param(
    "i",
    $receiver_id
);

$owner_stmt->execute();

$owner_result = $owner_stmt->get_result();

if ($owner_result->num_rows !== 1) {
    $owner_stmt->close();
    header("Location: messages.php");
    exit;
}

$owner_stmt->close();

if ($room_id > 0) {

    $room_stmt = $conn->prepare(
        "SELECT id
         FROM rooms
         WHERE id = ?
         AND owner_id = ?
         LIMIT 1"
    );

    $room_stmt->bind_param(
        "ii",
        $room_id,
        $receiver_id
    );

    $room_stmt->execute();

    $room_result = $room_stmt->get_result();

    if ($room_result->num_rows !== 1) {
        $room_id = 0;
    }

    $room_stmt->close();
}

$stmt = $conn->prepare(
    "INSERT INTO messages
    (sender_id, receiver_id, room_id, message)
    VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    "iiis",
    $student_id,
    $receiver_id,
    $room_id,
    $message
);

$stmt->execute();

$stmt->close();

$url = "messages.php?owner_id=" . $receiver_id;

if ($room_id > 0) {
    $url .= "&room_id=" . $room_id;
}

header("Location: " . $url);
exit;