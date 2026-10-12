<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['student_id'])) {
    redirectByRole();
}

$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = 'Invalid request. Please try again.';
    } elseif ($identity === '' || $password === '') {
        $error = 'Enter your username/email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT student_id, name, username, email, password, role
             FROM students
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result(
                $id, $name, $username, $email, $hash, $role
            );
            $stmt->fetch();

            if (password_verify($password, $hash)) {
                session_regenerate_id(true);

                $_SESSION['student_id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['last_activity'] = time();

                // Upgrade old password hashes if needed
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare(
                        "UPDATE students SET password = ? WHERE student_id = ?"
                    );
                    $update->bind_param('si', $newHash, $id);
                    $update->execute();
                    $update->close();
                }

                // Optional Remember Me
                if (isset($_POST['remember'])) {
                    $selector = bin2hex(random_bytes(12));
                    $validator = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $validator);
                    $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

                    $insert = $conn->prepare(
                        "INSERT INTO remember_tokens
                         (student_id, selector, token_hash, expires_at)
                         VALUES (?, ?, ?, ?)"
                    );
                    $insert->bind_param(
                        'isss', $id, $selector, $tokenHash, $expires
                    );
                    $insert->execute();
                    $insert->close();

                    setcookie('remember_me', $selector . ':' . $validator, [
                        'expires' => time() + 60 * 60 * 24 * 30,
                        'path' => '/WDFPRACTICALS/',
                        'secure' => !empty($_SERVER['HTTPS'])
                            && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                $stmt->close();
                $conn->close();

                unset($_SESSION['csrf_token']);
                redirectByRole();
            }
        }

        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }

        $error = 'Invalid username/email or password.';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub Login</title>
</head>
<body>
    <h2>StudentHub Login</h2>

    <?php if (isset($_GET['timeout'])): ?>
        <p>Your session expired due to inactivity. Please log in again.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color:red">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="identity">Username or email</label><br>
        <input type="text" id="identity" name="identity" required><br><br>

        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label>
            <input type="checkbox" name="remember" value="1">
            Remember me for 30 days
        </label><br><br>

        <button type="submit">Log In</button>
    </form>

    <p><a href="register.html">Create an account</a></p>
</body>
</html>