<?php
// auth.php — shared authentication and session protection

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/WDFPRACTICALS/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

define('SESSION_TIMEOUT', 900); // 15 minutes

function clearRememberCookie(): void {
    setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/WDFPRACTICALS/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function redirectByRole(): void {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

function requireLogin(): void {
    // Expire inactive sessions
    if (isset($_SESSION['student_id'])) {
        if (
            isset($_SESSION['last_activity']) &&
            time() - $_SESSION['last_activity'] > SESSION_TIMEOUT
        ) {
            $_SESSION = [];
            session_regenerate_id(true);
            session_destroy();
            clearRememberCookie();

            header('Location: login.php?timeout=1');
            exit;
        }

        $_SESSION['last_activity'] = time();
        return;
    }

    // Restore a login using the optional remember-me token
    if (!empty($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me']);

        if (
            count($parts) === 2 &&
            preg_match('/^[a-f0-9]{24}$/', $parts[0]) &&
            preg_match('/^[a-f0-9]{64}$/', $parts[1])
        ) {
            require_once __DIR__ . '/db.php';

            $selector = $parts[0];
            $validator = $parts[1];

            $stmt = $conn->prepare(
                "SELECT rt.student_id, rt.token_hash, s.name, s.role
                 FROM remember_tokens rt
                 JOIN students s ON s.student_id = rt.student_id
                 WHERE rt.selector = ?
                 AND rt.expires_at > NOW()"
            );

            if ($stmt) {
                $stmt->bind_param('s', $selector);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result(
                        $studentId,
                        $tokenHash,
                        $studentName,
                        $studentRole
                    );
                    $stmt->fetch();

                    if (hash_equals(
                        $tokenHash,
                        hash('sha256', $validator)
                    )) {
                        session_regenerate_id(true);

                        $_SESSION['student_id'] = $studentId;
                        $_SESSION['name'] = $studentName;
                        $_SESSION['role'] = $studentRole;
                        $_SESSION['last_activity'] = time();

                        $stmt->close();
                        $conn->close();

                        // Rotate the remember-me token after use
                        header('Location: refresh_remember.php');
                        exit;
                    }
                }

                $stmt->close();
            }

            $conn->close();
        }

        clearRememberCookie();
    }

    header('Location: login.php');
    exit;
}
?>