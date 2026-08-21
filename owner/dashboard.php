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

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$owner_id = (int) $_SESSION["user_id"];
$owner_name = $_SESSION["user_name"] ?? "Room Owner";

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM rooms
     WHERE owner_id = ?"
);

if (!$stmt) {
    die("Database query failed: " . $conn->error);
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$total_rooms = (int) ($row["total"] ?? 0);

$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM rooms
     WHERE owner_id = ?
     AND status = 'available'"
);

if (!$stmt) {
    die("Database query failed: " . $conn->error);
}

$stmt->bind_param("i", $owner_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$available_rooms = (int) ($row["total"] ?? 0);

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Owner Dashboard | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/owner-dashboard.css"
    >
    
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-icon">
                🏠
            </div>

            <div>
                <h2>StudentNest</h2>
                <span>Owner Portal</span>
            </div>

        </div>

        <nav>

            <a
                href="dashboard.php"
                class="nav-link active"
            >
                <span>⌂</span>
                Dashboard
            </a>

            <a
                href="add-room.php"
                class="nav-link"
            >
                <span>＋</span>
                Add Room
            </a>

            <a
                href="manage-rooms.php"
                class="nav-link"
            >
                <span>▣</span>
                Manage Rooms
            </a>

            <a
                href="#"
                class="nav-link"
            >
                <span>✉</span>
                Messages
            </a>

            <a
                href="profile.php"
                class="nav-link"
            >
                <span>◉</span>
                My Profile
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="../logout.php"
                class="logout"
            >
                ↪ Logout
            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Owner Dashboard</h1>

                <p>
                    Manage your accommodation listings.
                </p>

            </div>

            <div class="user-info">

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(substr($owner_name, 0, 1))
                    ) ?>
                </div>

                <div>

                    <strong>
                        <?= htmlspecialchars($owner_name) ?>
                    </strong>

                    <span>
                        Room Owner
                    </span>

                </div>

            </div>

        </header>

        <div class="content">

            <section class="welcome-card">

                <div>

                    <span>
                        Welcome back
                    </span>

                    <h2>
                        <?= htmlspecialchars($owner_name) ?> 👋
                    </h2>

                    <p>
                        Manage your rooms and connect with students looking for accommodation.
                    </p>

                </div>

                <a
                    href="add-room.php"
                    class="primary-btn"
                >
                    + Add New Room
                </a>

            </section>

            <section class="stats">

                <div class="stat-card">

                    <div class="stat-icon">
                        🏠
                    </div>

                    <div>

                        <span>
                            Total Rooms
                        </span>

                        <strong>
                            <?= $total_rooms ?>
                        </strong>

                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
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

                    <div class="stat-icon blue">
                        ✉
                    </div>

                    <div>

                        <span>
                            Messages
                        </span>

                        <strong>
                            0
                        </strong>

                    </div>

                </div>

            </section>

            <section>

                <div class="section-heading">

                    <div>

                        <span>
                            Manage
                        </span>

                        <h2>
                            Quick Actions
                        </h2>

                    </div>

                </div>

                <div class="quick-grid">

                    <a
                        href="add-room.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            ＋
                        </div>

                        <div>

                            <h3>
                                Add New Room
                            </h3>

                            <p>
                                Create a new accommodation listing.
                            </p>

                        </div>

                    </a>

                    <a
                        href="manage-rooms.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            ▣
                        </div>

                        <div>

                            <h3>
                                Manage Rooms
                            </h3>

                            <p>
                                Edit or update your existing listings.
                            </p>

                        </div>

                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

</body>

</html>