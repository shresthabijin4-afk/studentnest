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

$search = trim($_GET["search"] ?? "");
$room_type = $_GET["room_type"] ?? "";
$max_rent = trim($_GET["max_rent"] ?? "");

$rooms = [];

$sql = "
    SELECT
        r.id,
        r.title,
        r.description,
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
";

$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (
        r.title LIKE ?
        OR r.location LIKE ?
        OR r.description LIKE ?
    )";

    $term = "%" . $search . "%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= "sss";
}

if ($room_type !== "") {
    $sql .= " AND r.room_type = ?";
    $params[] = $room_type;
    $types .= "s";
}

if ($max_rent !== "" && is_numeric($max_rent)) {
    $sql .= " AND r.rent <= ?";
    $params[] = (float)$max_rent;
    $types .= "d";
}

$sql .= " ORDER BY r.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

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

    <title>Find Rooms | StudentNest</title>

    <link
        rel="stylesheet"
        href="../assests/css/student-pages.css?v=3"
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
                Find Rooms
            </h1>

            <p>
                Search accommodation that matches your needs and budget.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </div>

    <form
        method="GET"
        class="filter-card"
    >

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search location or room name"
        >

        <select name="room_type">

            <option value="">
                All Room Types
            </option>

            <option
                value="single"
                <?= $room_type === "single" ? "selected" : "" ?>
            >
                Single Room
            </option>

            <option
                value="shared"
                <?= $room_type === "shared" ? "selected" : "" ?>
            >
                Shared Room
            </option>

            <option
                value="1BHK"
                <?= $room_type === "1BHK" ? "selected" : "" ?>
            >
                1BHK
            </option>

            <option
                value="2BHK"
                <?= $room_type === "2BHK" ? "selected" : "" ?>
            >
                2BHK
            </option>

            <option
                value="other"
                <?= $room_type === "other" ? "selected" : "" ?>
            >
                Other
            </option>

        </select>

        <input
            type="number"
            name="max_rent"
            value="<?= htmlspecialchars($max_rent) ?>"
            placeholder="Maximum rent"
            min="1"
        >

        <button type="submit">
            Search
        </button>

    </form>

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

                        <h2>
                            <?= htmlspecialchars($room["title"]) ?>
                        </h2>

                        <p class="location">
                            📍 <?= htmlspecialchars($room["location"]) ?>
                        </p>

                        <div class="details">

                            <span>
                                <?= htmlspecialchars($room["room_type"]) ?>
                            </span>

                            <?php if (!empty($room["facilities"])): ?>

                                <span>
                                    <?= htmlspecialchars($room["facilities"]) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <p class="description">
                            <?= htmlspecialchars($room["description"]) ?>
                        </p>

                        <div class="room-footer">

                            <strong>
                                Rs. <?= number_format((float)$room["rent"]) ?>/month
                            </strong>

                            <a
                                href="room-details.php?id=<?= (int)$room["id"] ?>"
                                class="view-btn"
                            >
                                View Details
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">

            <h2>
                No rooms found
            </h2>

            <p>
                Try changing your search or filter.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>

</html>