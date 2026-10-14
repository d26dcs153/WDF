<?php
require_once __DIR__ . '/auth.php';
requireLogin();

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
    <p>You are logged in as an administrator.</p>
    <a href="students.php">Manage Students</a><br>
    <a href="events.php">Manage Events</a><br>
    <form method="POST" action="logout.php">
        <button type="submit">Log Out</button>
    </form>
</body>
</html>