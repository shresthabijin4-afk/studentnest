
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

$room_id = isset($_GET["room_id"])
    ? (int) $_GET["room_id"]
    : (int) ($_POST["room_id"] ?? 0);

if ($room_id <= 0) {
    header("Location: search-rooms.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT
        r.id,
        r.owner_id,
        r.title,
        r.status,
        u.name AS owner_name
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

$owner_id = (int) $room["owner_id"];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $message = trim($_POST["message"] ?? "");

    if ($message === "") {

        $error = "Please enter a message.";

    } elseif (strlen($message) > 2000) {

        $error = "Message cannot exceed 2000 characters.";

    } else {

        $insert = $conn->prepare(
            "INSERT INTO messages
            (sender_id, receiver_id, room_id, message)
            VALUES (?, ?, ?, ?)"
        );

        if (!$insert) {

            $error = "Unable to prepare message.";

        } else {

            $insert->bind_param(
                "iiis",
                $student_id,
                $owner_id,
                $room_id,
                $message
            );

            if ($insert->execute()) {

                $insert->close();

                header(
                    "Location: messages.php?success=Message sent successfully."
                );

                exit;

            } else {

                $error = "Unable to send message.";
            }

            $insert->close();
        }
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

<title>Contact Owner | StudentNest</title>

<link
    rel="stylesheet"
    href="../assests/css/student-pages.css?v=6"
>

<link
    rel="stylesheet"
    href="../assests/css/student-message.css?v=1"
>

</head>

<body>

<div class="page">

    <div class="page-header">

        <div>

            <span class="label">
                Student Portal
            </span>

            <h1>
                Contact Owner
            </h1>

            <p>
                <?= htmlspecialchars($room["title"]) ?>
            </p>

        </div>

        <a
            href="room-details.php?id=<?= $room_id ?>"
            class="back-btn"
        >
            ← Room Details
        </a>

    </div>

    <div class="message-page-card">

        <div class="message-card-header">

            <span class="message-label">
                Room Inquiry
            </span>

            <h2>
                Send Message
            </h2>

            <p>
                Contact the owner about this room.
            </p>

        </div>

        <div class="owner-info">

            <div class="owner-avatar">
                <?= strtoupper(substr($room["owner_name"], 0, 1)) ?>
            </div>

            <div>

                <span>
                    Room Owner
                </span>

                <strong>
                    <?= htmlspecialchars($room["owner_name"]) ?>
                </strong>

            </div>

        </div>

        <?php if ($error !== ""): ?>

            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="room_id"
                value="<?= $room_id ?>"
            >

            <div class="message-form-group">

                <label for="message">
                    Your Message
                </label>

                <textarea
                    id="message"
                    name="message"
                    rows="8"
                    maxlength="2000"
                    placeholder="Write your message to the room owner..."
                    required
                ></textarea>

                <small>
                    Maximum 2000 characters.
                </small>

            </div>

            <div class="message-actions">

                <a
                    href="room-details.php?id=<?= $room_id ?>"
                    class="cancel-message-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="send-message-btn"
                >
                    Send Message
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>
```
