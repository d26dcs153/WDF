<?php
// Start processing when form is submitted via POST
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ========================================================
    // 1. SANITIZATION
    // ========================================================
    $name    = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
    $email   = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
    $message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

    // Convert special HTML characters to prevent XSS attacks
    $nameSafe    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $emailSafe   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $messageSafe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // ========================================================
    // 2. SERVER-SIDE VALIDATION
    // ========================================================

    // Validate Name: Required, letters and spaces only, min 2 chars
    if (empty($nameSafe)) {
        $errors[] = "Name field is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]{2,50}$/", $nameSafe)) {
        $errors[] = "Name must contain only letters and spaces (2 to 50 characters).";
    }

    // Validate Email: Standard RFC validation
    if (empty($emailSafe)) {
        $errors[] = "Email field is required.";
    } elseif (!filter_var($emailSafe, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    // Validate Message: Required, minimum 5 characters
    if (empty($messageSafe)) {
        $errors[] = "Message field cannot be empty.";
    } elseif (strlen($messageSafe) < 5) {
        $errors[] = "Message must be at least 5 characters long.";
    }

    // ========================================================
    // 3. STORE IN JSON & CSV (If no validation errors)
    // ========================================================
    if (empty($errors)) {
        $dataDir = __DIR__ . '/data';

        // Automatically create data folder if it doesn't exist
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $record = [
            'id'        => uniqid('MSG_', true),
            'timestamp' => $timestamp,
            'name'      => $nameSafe,
            'email'     => $emailSafe,
            'message'   => $messageSafe
        ];

        // --- Option A: Append to JSON file ---
        $jsonFilePath = $dataDir . '/contacts.json';
        $currentJson = [];

        if (file_exists($jsonFilePath)) {
            $rawContent = file_get_contents($jsonFilePath);
            $currentJson = json_decode($rawContent, true) ?? [];
        }

        $currentJson[] = $record;
        file_put_contents($jsonFilePath, json_encode($currentJson, JSON_PRETTY_PRINT), LOCK_EX);

        // --- Option B: Append to CSV file ---
        $csvFilePath = $dataDir . '/contacts.csv';
        $isFirstWrite = !file_exists($csvFilePath);

        $fileHandle = fopen($csvFilePath, 'a');
        if ($fileHandle !== false) {
            // Write column headers if file is newly created
            if ($isFirstWrite) {
                fputcsv($fileHandle, ['Message ID', 'Timestamp', 'Name', 'Email', 'Message']);
            }
            // Add row
            fputcsv($fileHandle, [
                $record['id'],
                $record['timestamp'],
                $record['name'],
                $record['email'],
                $record['message']
            ]);
            fclose($fileHandle);
        }

        $successMessage = "Thank you, " . $nameSafe . "! Your message has been stored successfully.";
    }
} else {
    $errors[] = "Invalid access method. Please submit through the contact form.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="design.css">
    <title>Contact Submission Status</title>
    <style>
        .status-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
        }
        .alert-error ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .btn-return {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body class="contact">
<header>
    <div class="nav-container">
        <h1>StudentHub</h1>
    </div>
</header>

<main>
    <div class="status-container">
        <?php if (!empty($successMessage)): ?>
            <div class="alert-success">
                <strong>Success!</strong> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <strong>Submission Error:</strong>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <a href="contact.html" class="btn-return">&larr; Return to Contact Page</a>
    </div>
</main>

<footer>
    <p>Made by Niraja</p>
</footer>
</body>
</html>