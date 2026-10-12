<?php
require_once __DIR__ . '/auth.php';

if (empty($_SESSION['student_id']) || empty($_COOKIE['remember_me'])) {
    header('Location: login.php');
    exit;
}

$parts = explode(':', $_COOKIE['remember_me'], 2);

if (
    count($parts) !== 2 ||
    !preg_match('/^[a-f0-9]{24}$/', $parts[0])
) {
    clearRememberCookie();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

$oldSelector = $parts[0];
$studentId = (int) $_SESSION['student_id'];

$newSelector = bin2hex(random_bytes(12));
$newValidator = bin2hex(random_bytes(32));
$newHash = hash('sha256', $newValidator);
$expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

$stmt = $conn->prepare(
    "UPDATE remember_tokens
     SET selector = ?, token_hash = ?, expires_at = ?
     WHERE selector = ? AND student_id = ?"
);

$stmt->bind_param(
    'ssssi',
    $newSelector,
    $newHash,
    $expires,
    $oldSelector,
    $studentId
);

$stmt->execute();
$updated = $stmt->affected_rows === 1;

$stmt->close();
$conn->close();

if (!$updated) {
    clearRememberCookie();
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

setcookie('remember_me', $newSelector . ':' . $newValidator, [
    'expires' => time() + 60 * 60 * 24 * 30,
    'path' => '/WDFPRACTICALS/',
    'secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

redirectByRole();
?>