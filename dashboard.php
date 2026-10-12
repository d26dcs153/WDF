<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Admins should use their own dashboard
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
    <p>You are logged in as a student.</p>
    <p>Your student ID is <?= (int) $_SESSION['student_id'] ?>.</p>

    <form method="POST" action="logout.php">
        <button type="submit">Log Out</button>
    </form>
</body>
</html>