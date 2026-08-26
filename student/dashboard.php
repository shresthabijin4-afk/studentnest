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

$rooms = [];

$stmt = $conn->prepare(
    "SELECT
        r.id,
        r.title,
        r.location,
        r.rent,
        r.room_type,
        r.facilities,
        r.status,
        (
            SELECT ri.image_path
            FROM room_images ri
            WHERE ri.room_id = r.id
            ORDER BY ri.is_primary DESC, ri.id ASC
            LIMIT 1
        ) AS image_path
     FROM rooms r
     WHERE r.status = 'available'
     ORDER BY r.id DESC
     LIMIT 6"
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

$stmt->close();

$room_count_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM rooms
     WHERE status = 'available'"
);

$available_rooms = (int) $room_count_result->fetch_assoc()["total"];

$saved_rooms = 0;

$saved_check = $conn->query(
    "SHOW TABLES LIKE 'saved_rooms'"
);

if ($saved_check && $saved_check->num_rows > 0) {

    $saved_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM saved_rooms
         WHERE student_id = ?"
    );

    if ($saved_stmt) {
        $saved_stmt->bind_param("i", $student_id);
        $saved_stmt->execute();

        $saved_result = $saved_stmt->get_result();

        if ($saved_result->num_rows === 1) {
            $saved_rooms = (int) $saved_result->fetch_assoc()["total"];
        }

        $saved_stmt->close();
    }
}

$messages = 0;

$message_check = $conn->query(
    "SHOW TABLES LIKE 'messages'"
);

if ($message_check && $message_check->num_rows > 0) {

    $message_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM messages
         WHERE receiver_id = ?"
    );

    if ($message_stmt) {
        $message_stmt->bind_param("i", $student_id);
        $message_stmt->execute();

        $message_result = $message_stmt->get_result();

        if ($message_result->num_rows === 1) {
            $messages = (int) $message_result->fetch_assoc()["total"];
        }

        $message_stmt->close();
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

    <title>Student Dashboard | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/student-dashboard.css?v=2"
    >

</head>

<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="brand-icon">
                🏠
            </div>

            <div>
                <h2>StudentNest</h2>
                <span>Student Portal</span>
            </div>

        </div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-link active">
                <span>⌂</span>
                Dashboard
            </a>

            <a href="search-rooms.php" class="nav-link">
                <span>⌕</span>
                Find Rooms
            </a>

            <a href="saved-rooms.php" class="nav-link">
                <span>♡</span>
                Saved Rooms
            </a>

            <a href="messages.php" class="nav-link">
                <span>✉</span>
                Messages
            </a>

            <a href="profile.php" class="nav-link">
                <span>◉</span>
                My Profile
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="../logout.php"
                class="logout-link"
            >
                <span>↪</span>
                Logout
            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div class="mobile-brand">
                StudentNest
            </div>

            <div class="topbar-actions">

                <a
                    href="messages.php"
                    class="icon-button"
                >
                    🔔
                </a>

                <div class="profile-mini">

                    <div class="profile-avatar">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>

                    <div class="profile-text">

                        <strong>
                            <?= htmlspecialchars($userName) ?>
                        </strong>

                        <span>
                            Student
                        </span>

                    </div>

                </div>

            </div>

        </header>

        <div class="content-wrapper">

            <section class="welcome-section">

                <div>

                    <p class="welcome-label">
                        Student Dashboard
                    </p>

                    <h1>
                        Welcome back,
                        <?= htmlspecialchars($userName) ?> 👋
                    </h1>

                    <p class="welcome-description">
                        Find a comfortable place that fits your needs and budget.
                    </p>

                </div>

            </section>

            <section class="search-section">

                <form
                    action="search-rooms.php"
                    method="GET"
                    class="search-form"
                >

                    <div class="search-box">

                        <span class="search-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search by location, area or room name..."
                        >

                    </div>

                    <button
                        type="submit"
                        class="search-button"
                    >
                        Search Rooms
                    </button>

                </form>

            </section>

            <section class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon blue">
                        🏠
                    </div>

                    <div>

                        <span>
                            Available Rooms
                        </span>

                        <strong>
                            <?= $available_rooms ?>
                        </strong>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon red">
                        ♡
                    </div>

                    <div>

                        <span>
                            Saved Rooms
                        </span>

                        <strong>
                            <?= $saved_rooms ?>
                        </strong>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon green">
                        ✉
                    </div>

                    <div>

                        <span>
                            Messages
                        </span>

                        <strong>
                            <?= $messages ?>
                        </strong>

                    </div>

                </div>

            </section>

            <section class="rooms-section">

                <div class="section-heading">

                    <div>

                        <p class="section-label">
                            Explore
                        </p>

                        <h2>
                            Available Rooms
                        </h2>

                    </div>

                    <a href="search-rooms.php">
                        View all →
                    </a>

                </div>

                <?php if (count($rooms) > 0): ?>

                    <div class="room-grid">

                        <?php foreach ($rooms as $room): ?>

                            <article class="room-card">

                                <div class="room-image">

                                    <?php if (!empty($room["image_path"])): ?>

                                        <img
                                            src="../<?= htmlspecialchars($room["image_path"]) ?>"
                                            alt="<?= htmlspecialchars($room["title"]) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="no-image">
                                            No Image
                                        </div>

                                    <?php endif; ?>

                                    <span class="availability">
                                        Available
                                    </span>

                                </div>

                                <div class="room-body">

                                    <h3>
                                        <?= htmlspecialchars($room["title"]) ?>
                                    </h3>

                                    <p class="room-location">
                                        📍 <?= htmlspecialchars($room["location"]) ?>
                                    </p>

                                    <div class="room-details">

                                        <span>
                                            🛏 <?= htmlspecialchars($room["room_type"]) ?>
                                        </span>

                                        <?php if (!empty($room["facilities"])): ?>

                                            <span>
                                                <?= htmlspecialchars($room["facilities"]) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="room-footer">

                                        <div>

                                            <span class="rent-label">
                                                Monthly rent
                                            </span>

                                            <strong>
                                                Rs. <?= number_format((float)$room["rent"]) ?>
                                            </strong>

                                        </div>

                                        <a
                                            href="room-details.php?id=<?= (int)$room["id"] ?>"
                                            class="view-button"
                                        >
                                            View
                                        </a>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            No rooms available
                        </h3>

                        <p>
                            There are currently no available room listings.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

</body>

</html>