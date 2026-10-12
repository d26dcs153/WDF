<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

require_once __DIR__ . '/db.php';

if (!empty($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me'], 2);

    if (
        count($parts) === 2 &&
        preg_match('/^[a-f0-9]{24}$/', $parts[0])
    ) {
        $stmt = $conn->prepare(
            "DELETE FROM remember_tokens WHERE selector = ?"
        );
        $stmt->bind_param('s', $parts[0]);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();

$_SESSION = [];
clearRememberCookie();

if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/WDFPRACTICALS/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_destroy();

header('Location: login.php');
exit;
?>