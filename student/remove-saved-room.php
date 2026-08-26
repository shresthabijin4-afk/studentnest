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

if ($room_id > 0) {

    $stmt = $conn->prepare(
        "DELETE FROM saved_rooms
         WHERE student_id = ? AND room_id = ?"
    );

    if ($stmt) {

        $stmt->bind_param("ii", $student_id, $room_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: saved-rooms.php");
exit;