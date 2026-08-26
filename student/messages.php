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
$userName = $_SESSION["user_name"] ?? "Student";

$selected_owner = isset($_GET["owner_id"]) ? (int)$_GET["owner_id"] : 0;
$selected_room = isset($_GET["room_id"]) ? (int)$_GET["room_id"] : 0;

if ($selected_owner > 0) {
    $read_stmt = $conn->prepare(
        "UPDATE messages
         SET is_read = 1
         WHERE sender_id = ?
         AND receiver_id = ?"
    );

    $read_stmt->bind_param(
        "ii",
        $selected_owner,
        $student_id
    );

    $read_stmt->execute();
    $read_stmt->close();
}

$owners = [];

$stmt = $conn->prepare(
    "SELECT
        u.id,
        u.name,
        u.email,
        MAX(m.created_at) AS last_message,
        SUM(
            CASE
                WHEN m.receiver_id = ?
                AND m.is_read = 0
                THEN 1
                ELSE 0
            END
        ) AS unread_count
     FROM users u
     INNER JOIN messages m
        ON (
            m.sender_id = u.id
            OR m.receiver_id = u.id
        )
     WHERE
        u.id != ?
        AND (
            m.sender_id = ?
            OR m.receiver_id = ?
        )
     GROUP BY u.id, u.name, u.email
     ORDER BY last_message DESC"
);

$stmt->bind_param(
    "iiii",
    $student_id,
    $student_id,
    $student_id,
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $owners[] = $row;
}

$stmt->close();

$messages = [];

if ($selected_owner > 0) {

    $message_stmt = $conn->prepare(
        "SELECT
            m.id,
            m.sender_id,
            m.receiver_id,
            m.room_id,
            m.message,
            m.created_at,
            u.name AS sender_name
         FROM messages m
         INNER JOIN users u
            ON u.id = m.sender_id
         WHERE
            (
                m.sender_id = ?
                AND m.receiver_id = ?
            )
            OR
            (
                m.sender_id = ?
                AND m.receiver_id = ?
            )
         ORDER BY m.created_at ASC"
    );

    $message_stmt->bind_param(
        "iiii",
        $student_id,
        $selected_owner,
        $selected_owner,
        $student_id
    );

    $message_stmt->execute();

    $message_result = $message_stmt->get_result();

    while ($row = $message_result->fetch_assoc()) {
        $messages[] = $row;
    }

    $message_stmt->close();
}

$selected_owner_name = "";

if ($selected_owner > 0) {

    $owner_stmt = $conn->prepare(
        "SELECT name
         FROM users
         WHERE id = ?
         AND role = 'owner'
         LIMIT 1"
    );

    $owner_stmt->bind_param("i", $selected_owner);
    $owner_stmt->execute();

    $owner_result = $owner_stmt->get_result();

    if ($owner_result->num_rows === 1) {
        $selected_owner_name =
            $owner_result->fetch_assoc()["name"];
    }

    $owner_stmt->close();
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

    <title>Messages | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/student-pages.css?v=4"
    >

    <style>

        .messages-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            margin-top: 25px;
        }

        .conversation-list,
        .chat-box {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
        }

        .conversation-list h3 {
            margin-top: 0;
        }

        .conversation {
            display: block;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #222;
            margin-bottom: 8px;
        }

        .conversation:hover,
        .conversation.active {
            background: #eef9ff;
        }

        .conversation strong {
            display: block;
        }

        .conversation small {
            color: #777;
        }

        .chat-box {
            min-height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            min-height: 350px;
            padding: 10px 0;
        }

        .message-bubble {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 14px;
            margin-bottom: 12px;
        }

        .message-sent {
            margin-left: auto;
            background: #dff3ff;
        }

        .message-received {
            margin-right: auto;
            background: #f1f1f1;
        }

        .message-time {
            display: block;
            font-size: 11px;
            color: #777;
            margin-top: 5px;
        }

        .message-form {
            display: flex;
            gap: 10px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .message-form textarea {
            flex: 1;
            min-height: 50px;
            resize: none;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        .message-form button {
            border: none;
            border-radius: 10px;
            padding: 0 22px;
            cursor: pointer;
            background: #79c7e8;
            color: white;
            font-weight: 600;
        }

        .empty-chat {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #777;
        }

        @media (max-width: 800px) {

            .messages-layout {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="page">

    <div class="page-header">

        <div>

            <span class="label">
                Student Portal
            </span>

            <h1>
                Messages
            </h1>

            <p>
                Communicate with room owners.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </div>

    <div class="messages-layout">

        <div class="conversation-list">

            <h3>
                Conversations
            </h3>

            <?php if (count($owners) > 0): ?>

                <?php foreach ($owners as $owner): ?>

                    <a
                        href="messages.php?owner_id=<?= (int)$owner["id"] ?>"
                        class="conversation <?= $selected_owner === (int)$owner["id"] ? "active" : "" ?>"
                    >

                        <strong>
                            <?= htmlspecialchars($owner["name"]) ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars($owner["email"]) ?>
                        </small>

                        <?php if ((int)$owner["unread_count"] > 0): ?>

                            <small>
                                <?= (int)$owner["unread_count"] ?> unread
                            </small>

                        <?php endif; ?>

                    </a>

                <?php endforeach; ?>

            <?php else: ?>

                <p>
                    No conversations yet.
                </p>

            <?php endif; ?>

        </div>

        <div class="chat-box">

            <?php if ($selected_owner > 0 && $selected_owner_name !== ""): ?>

                <div class="chat-header">

                    <h3>
                        <?= htmlspecialchars($selected_owner_name) ?>
                    </h3>

                    <small>
                        Room Owner
                    </small>

                </div>

                <div class="chat-messages">

                    <?php if (count($messages) > 0): ?>

                        <?php foreach ($messages as $message): ?>

                            <div
                                class="message-bubble <?= (int)$message["sender_id"] === $student_id ? "message-sent" : "message-received" ?>"
                            >

                                <?= nl2br(htmlspecialchars($message["message"])) ?>

                                <span class="message-time">
                                    <?= htmlspecialchars($message["created_at"]) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-chat">
                            Start a conversation with this owner.
                        </div>

                    <?php endif; ?>

                </div>

                <form
                    method="POST"
                    action="send-message.php"
                    class="message-form"
                >

                    <input
                        type="hidden"
                        name="receiver_id"
                        value="<?= $selected_owner ?>"
                    >

                    <input
                        type="hidden"
                        name="room_id"
                        value="<?= $selected_room ?>"
                    >

                    <textarea
                        name="message"
                        placeholder="Write your message..."
                        required
                    ></textarea>

                    <button type="submit">
                        Send
                    </button>

                </form>

            <?php else: ?>

                <div class="empty-chat">
                    Select a conversation to view messages.
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>