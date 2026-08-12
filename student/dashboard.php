<?php

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "student"
) {
    header("Location: ../login.php");
    exit;
}

$userName = $_SESSION["user_name"];

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
        href="../assests/css/student-dashboard.css"
    >

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
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

            <a href="#" class="nav-link active">
                <span>⌂</span>
                Dashboard
            </a>

            <a href="#" class="nav-link">
                <span>⌕</span>
                Find Rooms
            </a>

            <a href="#" class="nav-link">
                <span>♡</span>
                Saved Rooms
            </a>

            <a href="#" class="nav-link">
                <span>✉</span>
                Messages
            </a>

            <a href="#" class="nav-link">
                <span>◉</span>
                My Profile
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="../logout.php" class="logout-link">
                <span>↪</span>
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="mobile-brand">
                StudentNest
            </div>

            <div class="topbar-actions">

                <button class="icon-button">
                    🔔
                </button>

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


            <!-- WELCOME -->
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


            <!-- SEARCH -->
            <section class="search-section">

                <div class="search-box">

                    <span class="search-icon">
                        ⌕
                    </span>

                    <input
                        type="text"
                        placeholder="Search by location, area or room name..."
                    >

                </div>

                <button class="search-button">
                    Search Rooms
                </button>

            </section>


            <!-- QUICK STATS -->
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
                            24
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
                            5
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
                            3
                        </strong>

                    </div>

                </div>

            </section>


            <!-- ROOM SECTION -->
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

                    <a href="#">
                        View all →
                    </a>

                </div>


                <div class="room-grid">


                    <!-- ROOM CARD 1 -->
                    <article class="room-card">

                        <div class="room-image">

                            <img
                                src="../assets/images/room-1.jpg"
                                alt="Modern single room"
                            >

                            <button class="favorite-button">
                                ♡
                            </button>

                            <span class="availability">
                                Available
                            </span>

                        </div>


                        <div class="room-body">

                            <h3>
                                Modern Single Room
                            </h3>

                            <p class="room-location">
                                📍 Dharan, Sunsari
                            </p>


                            <div class="room-details">

                                <span>
                                    🛏 Single
                                </span>

                                <span>
                                    📶 Wi-Fi
                                </span>

                                <span>
                                    🍳 Kitchen
                                </span>

                            </div>


                            <div class="room-footer">

                                <div>

                                    <span class="rent-label">
                                        Monthly rent
                                    </span>

                                    <strong>
                                        Rs. 8,000
                                    </strong>

                                </div>

                                <a href="#" class="view-button">
                                    View
                                </a>

                            </div>

                        </div>

                    </article>


                    <!-- ROOM CARD 2 -->
                    <article class="room-card">

                        <div class="room-image">

                            <img
                                src="../assets/images/room-2.jpg"
                                alt="Student room"
                            >

                            <button class="favorite-button">
                                ♡
                            </button>

                            <span class="availability">
                                Available
                            </span>

                        </div>


                        <div class="room-body">

                            <h3>
                                Bright Student Room
                            </h3>

                            <p class="room-location">
                                📍 Itahari, Sunsari
                            </p>


                            <div class="room-details">

                                <span>
                                    🛏 Shared
                                </span>

                                <span>
                                    📶 Wi-Fi
                                </span>

                                <span>
                                    🚿 Bathroom
                                </span>

                            </div>


                            <div class="room-footer">

                                <div>

                                    <span class="rent-label">
                                        Monthly rent
                                    </span>

                                    <strong>
                                        Rs. 6,500
                                    </strong>

                                </div>

                                <a href="#" class="view-button">
                                    View
                                </a>

                            </div>

                        </div>

                    </article>


                    <!-- ROOM CARD 3 -->
                    <article class="room-card">

                        <div class="room-image">

                            <img
                                src="../assets/images/room-3.jpg"
                                alt="Comfortable room"
                            >

                            <button class="favorite-button">
                                ♡
                            </button>

                            <span class="availability">
                                Available
                            </span>

                        </div>


                        <div class="room-body">

                            <h3>
                                Comfortable Room
                            </h3>

                            <p class="room-location">
                                📍 Biratnagar, Morang
                            </p>


                            <div class="room-details">

                                <span>
                                    🛏 Single
                                </span>

                                <span>
                                    📶 Wi-Fi
                                </span>

                                <span>
                                    🛵 Parking
                                </span>

                            </div>


                            <div class="room-footer">

                                <div>

                                    <span class="rent-label">
                                        Monthly rent
                                    </span>

                                    <strong>
                                        Rs. 7,500
                                    </strong>

                                </div>

                                <a href="#" class="view-button">
                                    View
                                </a>

                            </div>

                        </div>

                    </article>

                </div>

            </section>

        </div>

    </main>

</div>

</body>

</html>