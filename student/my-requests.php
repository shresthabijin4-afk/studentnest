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

$requests = [];

$table_check = $conn->query("SHOW TABLES LIKE 'room_requests'");

if ($table_check && $table_check->num_rows > 0) {

    $stmt = $conn->prepare(
        "SELECT
            rr.id,
            rr.room_id,
            rr.status,
            rr.created_at,
            r.title,
            r.location,
            r.rent,
            u.name AS owner_name
         FROM room_requests rr
         INNER JOIN rooms r ON r.id = rr.room_id
         INNER JOIN users u ON u.id = r.owner_id
         WHERE rr.student_id = ?
         ORDER BY rr.id DESC"
    );

    if ($stmt) {

        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        $stmt->close();
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

<title>My Requests | StudentNest</title>

<link
    rel="stylesheet"
    href="../assests/css/student-pages.css?v=5"
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
                My Requests
            </h1>

            <p>
                View your room requests and their current status.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </div>

    <?php if (count($requests) > 0): ?>

        <div class="room-grid">

            <?php foreach ($requests as $request): ?>

                <article class="room-card">

                    <div class="room-body">

                        <h2>
                            <?= htmlspecialchars($request["title"]) ?>
                        </h2>

                        <p class="location">
                            📍 <?= htmlspecialchars($request["location"]) ?>
                        </p>

                        <div class="details">

                            <span>
                                Room Rent:
                                Rs. <?= number_format((float)$request["rent"]) ?>
                            </span>

                            <span>
                                Owner:
                                <?= htmlspecialchars($request["owner_name"]) ?>
                            </span>

                        </div>

                        <p>
                            Request Date:
                            <?= htmlspecialchars($request["created_at"]) ?>
                        </p>

                        <div class="room-footer">

                            <strong>
                                <?= htmlspecialchars(
                                    ucfirst($request["status"])
                                ) ?>
                            </strong>

                            <a
                                href="room-details.php?id=<?= (int)$request["room_id"] ?>"
                                class="view-btn"
                            >
                                View Room
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">

            <h2>
                No Requests
            </h2>

            <p>
                You have not submitted any room requests yet.
            </p>

            <br>

            <a
                href="search-rooms.php"
                class="view-btn"
            >
                Find Rooms
            </a>

        </div>

    <?php endif; ?>

</div>

</body>

</html>