<?php

$errors = [];
$successMessage = '';

$allowedCourses = ['BCA', 'B.Tech', 'MCA', 'M.Tech'];
$allowedYears = ['1', '2', '3', '4'];
$allowedGenders = ['Male', 'Female', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Get form data
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    $course = trim($_POST['course'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    $termsAccepted = isset($_POST['terms']);

    // 2. Validate input

    if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $name)) {
        $errors['name'] = 'Enter a valid name (2-50 letters and spaces).';
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors['username'] = 'Username must be 3-50 characters using letters, numbers, or underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile number must contain exactly 10 digits.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword || $confirmPassword === '') {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    if (!in_array($course, $allowedCourses, true)) {
        $errors['course'] = 'Select a valid course.';
    }

    if (!in_array($year, $allowedYears, true)) {
        $errors['year'] = 'Select a valid academic year.';
    }

    if (!in_array($gender, $allowedGenders, true)) {
        $errors['gender'] = 'Select a valid gender.';
    }

    if (!$termsAccepted) {
        $errors['terms'] = 'You must accept the terms and conditions.';
    }

    // 3. Insert into database if validation passes

    if (empty($errors)) {

        require_once __DIR__ . '/db.php';

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $yearNumber = (int) $year;

        $sql = "INSERT INTO students
                (name, username, email, mobile, course, year, gender, password)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $errors['database'] = 'Could not prepare query: ' . $conn->error;
        } else {

            // 8 parameters: 5 strings, 1 integer, 2 strings
            $stmt->bind_param(
                "sssssiss",
                $name,
                $username,
                $email,
                $mobile,
                $course,
                $yearNumber,
                $gender,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $successMessage = 'Registration successful! Student data has been saved to the database.';
            } elseif ($stmt->errno === 1062) {
                $errors['database'] = 'This email address or username is already registered.';
            } else {
                $errors['database'] = 'Database error (' . $stmt->errno . '): ' . $stmt->error;
            }

            $stmt->close();
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f4f6f9;
        }

        .card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        ul {
            margin: 5px 0 0 20px;
        }

        a.btn {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
        }

        a.btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
<div class="card">

    <?php if ($successMessage !== ''): ?>

        <div class="alert-success">
            <strong>Success!</strong><br>
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <a href="register.html" class="btn">Register Another Student</a>

    <?php elseif (!empty($errors)): ?>

        <div class="alert-error">
            <strong>Registration Failed:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <a href="register.html" class="btn">Go Back</a>

    <?php else: ?>

        <p>No submission received.</p>
        <a href="register.html" class="btn">Go to Register</a>

    <?php endif; ?>

</div>
</body>
</html>