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

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $receiver_id = (int) ($_POST["receiver_id"] ?? 0);
    $room_id = (int) ($_POST["room_id"] ?? 0);
    $message = trim($_POST["message"] ?? "");

    if ($receiver_id <= 0 || $message === "") {

        $error = "Please enter a message.";

    } else {

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE id = ? AND role = 'student'
             LIMIT 1"
        );

        $check->bind_param("i", $receiver_id);
        $check->execute();

        $check_result = $check->get_result();

        if ($check_result->num_rows !== 1) {

            $error = "Invalid student.";

        } else {

            if ($room_id > 0) {

                $room_check = $conn->prepare(
                    "SELECT id
                     FROM rooms
                     WHERE id = ? AND owner_id = ?
                     LIMIT 1"
                );

                $room_check->bind_param(
                    "ii",
                    $room_id,
                    $owner_id
                );

                $room_check->execute();

                $room_result = $room_check->get_result();

                if ($room_result->num_rows !== 1) {
                    $room_id = 0;
                }

                $room_check->close();
            }

            if ($room_id > 0) {

                $stmt = $conn->prepare(
                    "INSERT INTO messages
                    (sender_id, receiver_id, room_id, message)
                    VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iiis",
                    $owner_id,
                    $receiver_id,
                    $room_id,
                    $message
                );

            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO messages
                    (sender_id, receiver_id, room_id, message)
                    VALUES (?, ?, NULL, ?)"
                );

                $stmt->bind_param(
                    "iis",
                    $owner_id,
                    $receiver_id,
                    $message
                );
            }

            if ($stmt->execute()) {
                $success = "Message sent successfully.";
            } else {
                $error = "Unable to send message.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

if (isset($_GET["delete"])) {

    $message_id = (int) $_GET["delete"];

    if ($message_id > 0) {

        $delete = $conn->prepare(
            "DELETE FROM messages
             WHERE id = ? AND sender_id = ?"
        );

        $delete->bind_param(
            "ii",
            $message_id,
            $owner_id
        );

        $delete->execute();
        $delete->close();

        header("Location: messages.php?success=Message deleted");
        exit;
    }
}

if (isset($_GET["read"])) {

    $message_id = (int) $_GET["read"];

    if ($message_id > 0) {

        $read = $conn->prepare(
            "UPDATE messages
             SET is_read = 1
             WHERE id = ?
             AND receiver_id = ?"
        );

        $read->bind_param(
            "ii",
            $message_id,
            $owner_id
        );

        $read->execute();
        $read->close();

        header("Location: messages.php");
        exit;
    }
}

if (isset($_GET["success"]) && $_GET["success"] !== "") {
    $success = $_GET["success"];
}

$students_stmt = $conn->prepare(
    "SELECT id, name, email
     FROM users
     WHERE role = 'student'
     ORDER BY name ASC"
);

$students_stmt->execute();

$students_result = $students_stmt->get_result();

$students = [];

while ($student = $students_result->fetch_assoc()) {
    $students[] = $student;
}

$students_stmt->close();

$rooms_stmt = $conn->prepare(
    "SELECT id, title
     FROM rooms
     WHERE owner_id = ?
     ORDER BY id DESC"
);

$rooms_stmt->bind_param("i", $owner_id);
$rooms_stmt->execute();

$rooms_result = $rooms_stmt->get_result();

$rooms = [];

while ($room = $rooms_result->fetch_assoc()) {
    $rooms[] = $room;
}

$rooms_stmt->close();

$messages_stmt = $conn->prepare(
    "SELECT
        m.id,
        m.message,
        m.is_read,
        m.created_at,
        m.room_id,
        s.id AS sender_id,
        s.name AS sender_name,
        s.email AS sender_email,
        r.title AS room_title
     FROM messages m
     INNER JOIN users s
        ON s.id = m.sender_id
     LEFT JOIN rooms r
        ON r.id = m.room_id
     WHERE m.sender_id = ?
        OR m.receiver_id = ?
     ORDER BY m.created_at DESC"
);

$messages_stmt->bind_param(
    "ii",
    $owner_id,
    $owner_id
);

$messages_stmt->execute();

$messages_result = $messages_stmt->get_result();

$messages = [];

while ($message = $messages_result->fetch_assoc()) {
    $messages[] = $message;
}

$messages_stmt->close();

$unread_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM messages
     WHERE receiver_id = ?
     AND is_read = 0"
);

$unread_stmt->bind_param("i", $owner_id);
$unread_stmt->execute();

$unread_result = $unread_stmt->get_result();
$unread_count = (int) $unread_result->fetch_assoc()["total"];

$unread_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Messages | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/messages.css?v=1"
    >

</head>

<body>

<div class="page">

    <div class="page-header">

        <div>

            <span class="label">
                Owner Portal
            </span>

            <h1>
                Messages
            </h1>

            <p>
                Communicate with students interested in your rooms.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </div>

    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <?php if ($success !== ""): ?>

        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>

    <div class="stats-card">

        <div>
            <span>
                Unread Messages
            </span>

            <strong>
                <?= $unread_count ?>
            </strong>
        </div>

    </div>

    <div class="content-grid">

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Send Message
                </h2>

                <p>
                    Contact a student directly.
                </p>

            </div>

            <form method="POST">

                <div class="form-group">

                    <label for="receiver_id">
                        Student
                    </label>

                    <select
                        id="receiver_id"
                        name="receiver_id"
                        required
                    >

                        <option value="">
                            Select student
                        </option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?= (int) $student["id"] ?>">
                                <?= htmlspecialchars($student["name"]) ?>
                                -
                                <?= htmlspecialchars($student["email"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="room_id">
                        Related Room
                    </label>

                    <select
                        id="room_id"
                        name="room_id"
                    >

                        <option value="0">
                            No specific room
                        </option>

                        <?php foreach ($rooms as $room): ?>

                            <option value="<?= (int) $room["id"] ?>">
                                <?= htmlspecialchars($room["title"]) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="message">
                        Message
                    </label>

                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        placeholder="Write your message..."
                        required
                    ></textarea>

                </div>

                <button
                    type="submit"
                    class="send-btn"
                >
                    Send Message
                </button>

            </form>

        </section>

        <section class="panel">

            <div class="panel-header">

                <h2>
                    Conversations
                </h2>

                <p>
                    Your recent messages.
                </p>

            </div>

            <?php if (count($messages) === 0): ?>

                <div class="empty-state">
                    <h3>
                        No messages yet
                    </h3>

                    <p>
                        Messages from students will appear here.
                    </p>
                </div>

            <?php else: ?>

                <div class="messages-list">

                    <?php foreach ($messages as $msg): ?>

                        <?php
                        $is_received =
                            (int) $msg["sender_id"] !== $owner_id;
                        ?>

                        <div
                            class="message-card <?= $is_received && (int)$msg["is_read"] === 0 ? "unread" : "" ?>"
                        >

                            <div class="message-top">

                                <div>

                                    <strong>
                                        <?= htmlspecialchars($msg["sender_name"]) ?>
                                    </strong>

                                    <?php if ($is_received): ?>

                                        <span class="badge">
                                            Student
                                        </span>

                                    <?php else: ?>

                                        <span class="badge sent">
                                            You
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <small>
                                    <?= htmlspecialchars(date("M d, Y h:i A", strtotime($msg["created_at"]))) ?>
                                </small>

                            </div>

                            <?php if (!empty($msg["room_title"])): ?>

                                <div class="room-label">
                                    Room:
                                    <?= htmlspecialchars($msg["room_title"]) ?>
                                </div>

                            <?php endif; ?>

                            <p class="message-text">
                                <?= nl2br(htmlspecialchars($msg["message"])) ?>
                            </p>

                            <div class="message-actions">

                                <?php if ($is_received && (int)$msg["is_read"] === 0): ?>

                                    <a
                                        href="messages.php?read=<?= (int)$msg["id"] ?>"
                                        class="read-btn"
                                    >
                                        Mark Read
                                    </a>

                                <?php endif; ?>

                                <?php if (!$is_received): ?>

                                    <a
                                        href="messages.php?delete=<?= (int)$msg["id"] ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Delete this message?')"
                                    >
                                        Delete
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</div>

</body>

</html>