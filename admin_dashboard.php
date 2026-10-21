<?php
require_once __DIR__ . '/auth.php';
requireLogin();

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

require_once __DIR__ . '/db.php';

// Fetch dashboard statistics
$studentCount = 0;
$eventCount = 0;
$registrationCount = 0;
$dashboardError = false;

$queries = [
    'students' => "SELECT COUNT(*) AS total FROM students",
    'events' => "SELECT COUNT(*) AS total FROM events",
    'registrations' => "SELECT COUNT(*) AS total FROM registrations"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);

    if ($result) {
        $count = (int) $result->fetch_assoc()['total'];

        if ($key === 'students') {
            $studentCount = $count;
        } elseif ($key === 'events') {
            $eventCount = $count;
        } else {
            $registrationCount = $count;
        }
    } else {
        error_log("Dashboard query failed: " . $conn->error);
        $dashboardError = true;
    }
}

// Fetch latest registrations
$sql = "SELECT
            r.registration_id,
            r.registered_at,
            s.name AS student_name,
            s.email,
            e.event_name
        FROM registrations r
        INNER JOIN students s
            ON r.student_id = s.student_id
        INNER JOIN events e
            ON r.event_id = e.event_id
        ORDER BY r.registered_at DESC, r.registration_id DESC
        LIMIT 5";

$latestRegistrations = $conn->query($sql);

if (!$latestRegistrations) {
    error_log("Latest registrations query failed: " . $conn->error);
    $dashboardError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Dashboard | StudentHub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f6fb;
            font-family: Arial, sans-serif;
        }

        .dashboard {
            padding-top: 35px;
            padding-bottom: 40px;
        }

        .stat-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,.06);
            height: 100%;
        }

        .stat-card .card-body {
            padding: 25px;
        }

        .stat-number {
            font-size: 34px;
            font-weight: bold;
        }

        .stat-label {
            color: #666;
            font-size: 15px;
        }

        .table-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,.06);
        }

        .section-title {
            font-weight: 700;
        }
    </style>
</head>

<body>

<?php require __DIR__ . '/navbar.php'; ?>

<main class="container dashboard">

    <div class="mb-4">
        <h2 class="fw-bold">
            Admin Dashboard
        </h2>

        <p class="text-secondary">
            Welcome back,
            <?= htmlspecialchars($_SESSION['name'] ?? 'Admin',
                ENT_QUOTES, 'UTF-8') ?>!
            Here's an overview of StudentHub.
        </p>
    </div>

    <?php if ($dashboardError): ?>
        <div class="alert alert-warning">
            Some dashboard information could not be loaded.
            Please check the database configuration.
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-4 mb-5">

        <div class="col-sm-6 col-lg-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-number text-primary">
                        <?= $studentCount ?>
                    </div>
                    <a href="students.php"
                       class="text-decoration-none">
                        Manage students &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-label">Total Events</div>
                    <div class="stat-number text-success">
                        <?= $eventCount ?>
                    </div>
                    <a href="events.php"
                       class="text-decoration-none">
                        Manage events &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="stat-label">Total Registrations</div>
                    <div class="stat-number text-warning">
                        <?= $registrationCount ?>
                    </div>
                    <span class="text-secondary">
                        Student event registrations
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Latest registrations -->
    <div class="card table-card">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between
                        align-items-center mb-3">
                <h4 class="section-title mb-0">
                    Latest Registrations
                </h4>
                <span class="badge text-bg-primary">
                    Latest 5
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Registration ID</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Event</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($latestRegistrations &&
                              $latestRegistrations->num_rows > 0): ?>

                        <?php while ($row =
                            $latestRegistrations->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= (int) $row['registration_id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['student_name'],
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['email'],
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['event_name'],
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['registered_at'] ?? '',
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="5"
                                class="text-center text-secondary py-4">
                                No registrations found yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>