<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Only administrators can manage student records.
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

require_once __DIR__ . '/db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_token'];
$errors = [];
$message = $_SESSION['student_message'] ?? '';
$messageType = $_SESSION['student_message_type'] ?? 'success';

unset($_SESSION['student_message'], $_SESSION['student_message_type']);

$allowedCourses = ['BCA', 'B.Tech', 'MCA', 'M.Tech'];
$allowedYears = ['1', '2', '3', '4'];
$allowedGenders = ['Male', 'Female', 'Other'];

$form = [
    'student_id' => '',
    'name' => '',
    'username' => '',
    'email' => '',
    'mobile' => '',
    'course' => '',
    'year' => '',
    'gender' => ''
];

$isEditing = false;

// Redirect after successful database operations.
function finishOperation($conn, $message, $type = 'success') {
    $conn->close();
    $_SESSION['student_message'] = $message;
    $_SESSION['student_message_type'] = $type;
    header('Location: students.php');
    exit;
}

// PROCESS ADD, UPDATE AND DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($csrf, $submittedToken)) {
        $errors[] = 'Invalid or expired request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // DELETE
        if ($action === 'delete') {
            $id = filter_var(
                $_POST['student_id'] ?? '',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if (!$id) {
                $errors[] = 'Invalid student ID.';
            } else {
                $stmt = $conn->prepare(
                    'DELETE FROM students WHERE student_id = ?'
                );

                if (!$stmt) {
                    $errors[] = 'Could not prepare delete operation.';
                } else {
                    $stmt->bind_param('i', $id);

                    if ($stmt->execute() && $stmt->affected_rows === 1) {
                        $stmt->close();
                        finishOperation($conn, 'Student deleted successfully.');
                    } elseif ($stmt->errno) {
                        $errors[] = 'Failed to delete student.';
                    } else {
                        $errors[] = 'Student record was not found.';
                    }

                    $stmt->close();
                }
            }
        }

        // ADD OR UPDATE
        elseif ($action === 'add' || $action === 'update') {
            $form['student_id'] = trim($_POST['student_id'] ?? '');
            $form['name'] = trim($_POST['name'] ?? '');
            $form['username'] = trim($_POST['username'] ?? '');
            $form['email'] = trim($_POST['email'] ?? '');
            $form['mobile'] = trim($_POST['mobile'] ?? '');
            $form['course'] = trim($_POST['course'] ?? '');
            $form['year'] = trim($_POST['year'] ?? '');
            $form['gender'] = trim($_POST['gender'] ?? '');

            $isEditing = ($action === 'update');
            $id = filter_var(
                $form['student_id'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            // Validate fields on the server.
            if (!preg_match('/^[a-zA-Z\s]{2,50}$/', $form['name'])) {
                $errors[] = 'Name must contain 2-50 letters and spaces.';
            }

            if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $form['username'])) {
                $errors[] = 'Username must contain 3-50 letters, numbers, or underscores.';
            }

            if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) ||
                strlen($form['email']) > 100) {
                $errors[] = 'Enter a valid email address (maximum 100 characters).';
            }

            if (!preg_match('/^[0-9]{10}$/', $form['mobile'])) {
                $errors[] = 'Mobile number must contain exactly 10 digits.';
            }

            if (!in_array($form['course'], $allowedCourses, true)) {
                $errors[] = 'Select a valid course.';
            }

            if (!in_array($form['year'], $allowedYears, true)) {
                $errors[] = 'Select a valid academic year.';
            }

            if (!in_array($form['gender'], $allowedGenders, true)) {
                $errors[] = 'Select a valid gender.';
            }

            $password = $_POST['password'] ?? '';

            if ($action === 'add' && strlen($password) < 8) {
                $errors[] = 'New student password must be at least 8 characters.';
            }

            if ($action === 'add' &&
                !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $password)) {
                $errors[] = 'Password needs uppercase, lowercase, a number, and a special character.';
            }

            if ($isEditing && !$id) {
                $errors[] = 'Invalid student ID.';
            }

            if (empty($errors)) {
                $yearNumber = (int)$form['year'];

                if ($action === 'add') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare(
                        'INSERT INTO students
                         (name, username, email, mobile, course, year, gender, password)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'sssssiss',
                            $form['name'],
                            $form['username'],
                            $form['email'],
                            $form['mobile'],
                            $form['course'],
                            $yearNumber,
                            $form['gender'],
                            $passwordHash
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            finishOperation($conn, 'Student added successfully.');
                        }

                        if ($stmt->errno === 1062) {
                            $errors[] = 'That email address or username is already in use.';
                        } else {
                            $errors[] = 'Could not add student. Database error: ' . $stmt->error;
                        }

                        $stmt->close();
                    } else {
                        $errors[] = 'Could not prepare the add operation.';
                    }

                } else {
                    $stmt = $conn->prepare(
                        'UPDATE students
                         SET name = ?, username = ?, email = ?, mobile = ?,
                             course = ?, year = ?, gender = ?
                         WHERE student_id = ?'
                    );

                    if ($stmt) {
                        $stmt->bind_param(
                            'sssssisi',
                            $form['name'],
                            $form['username'],
                            $form['email'],
                            $form['mobile'],
                            $form['course'],
                            $yearNumber,
                            $form['gender'],
                            $id
                        );

                        if ($stmt->execute()) {
                            $stmt->close();
                            finishOperation($conn, 'Student details updated successfully.');
                        }

                        if ($stmt->errno === 1062) {
                            $errors[] = 'That email address or username is already in use.';
                        } else {
                            $errors[] = 'Could not update student. Database error: ' . $stmt->error;
                        }

                        $stmt->close();
                    } else {
                        $errors[] = 'Could not prepare the update operation.';
                    }
                }
            }
        } else {
            $errors[] = 'Unknown operation.';
        }
    }

    // Keep submitted values in the form when validation fails.
    if (isset($_POST['action']) &&
        in_array($_POST['action'], ['add', 'update'], true)) {
        $isEditing = ($_POST['action'] === 'update');
    }
}

// LOAD A STUDENT FOR EDITING
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id) {
        $stmt = $conn->prepare(
            'SELECT student_id, name, username, email, mobile, course, year, gender
             FROM students WHERE student_id = ?'
        );

        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->bind_result(
                $sid, $name, $username, $email,
                $mobile, $course, $year, $gender
            );

            if ($stmt->fetch()) {
                $form = [
                    'student_id' => $sid,
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'mobile' => $mobile,
                    'course' => $course,
                    'year' => $year,
                    'gender' => $gender
                ];
                $isEditing = true;
            } else {
                $message = 'Student record was not found.';
                $messageType = 'error';
            }

            $stmt->close();
        }
    } else {
        $message = 'Invalid student ID.';
        $messageType = 'error';
    }
}

// SEARCH AND FILTER
$search = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$yearFilter = trim($_GET['year'] ?? '');

if (!in_array($courseFilter, $allowedCourses, true)) {
    $courseFilter = '';
}

if (!in_array($yearFilter, $allowedYears, true)) {
    $yearFilter = '';
}

$like = '%' . $search . '%';
$yearFilterNumber = ($yearFilter === '') ? 0 : (int)$yearFilter;

$sql = "SELECT student_id, name, username, email, mobile, course, year, gender
        FROM students
        WHERE (? = '' OR name LIKE ? OR username LIKE ?
               OR email LIKE ? OR mobile LIKE ?)
          AND (? = '' OR course = ?)
          AND (? = 0 OR year = ?)
        ORDER BY student_id DESC";

$stmt = $conn->prepare($sql);
$students = [];

if ($stmt) {
    $stmt->bind_param(
        'sssssssii',
        $search, $like, $like, $like, $like,
        $courseFilter, $courseFilter,
        $yearFilterNumber, $yearFilterNumber
    );

    $stmt->execute();
    $stmt->bind_result(
        $sid, $name, $username, $email,
        $mobile, $course, $year, $gender
    );

    while ($stmt->fetch()) {
        $students[] = [
            'student_id' => $sid,
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'mobile' => $mobile,
            'course' => $course,
            'year' => $year,
            'gender' => $gender
        ];
    }

    $stmt->close();
} else {
    $errors[] = 'Could not load student records.';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Management | StudentHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            background: #f4f6f9;
            color: #222;
        }
        .container {
            max-width: 1200px;
            margin: auto;
        }
        .panel {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px #0001;
        }
        input, select, button {
            box-sizing: border-box;
            padding: 10px;
            margin: 5px 3px;
            max-width: 100%;
        }
        input, select {
            border: 1px solid #bbb;
            border-radius: 4px;
        }
        button, .btn {
            border: 0;
            border-radius: 4px;
            padding: 10px 14px;
            background: #174ea6;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .delete {
            background: #b42318;
        }
        .cancel {
            background: #666;
        }
        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .errors {
            color: #b42318;
        }
        .table-wrap {
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #e9eef7;
        }
        .actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .actions form {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="container">

    <h1>Student Management</h1>
    <p>
        Logged in as:
        <?= e($_SESSION['name'] ?? 'Administrator') ?>
        | <a href="admin_dashboard.php">Admin Dashboard</a>
    </p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'error' ? 'error' : 'success' ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <strong>Please check the following:</strong>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h2><?= $isEditing ? 'Edit Student' : 'Add Student' ?></h2>

        <form method="POST" action="students.php<?= $isEditing ? '?edit=' . (int)$form['student_id'] : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'add' ?>">

            <?php if ($isEditing): ?>
                <input type="hidden" name="student_id" value="<?= e($form['student_id']) ?>">
            <?php endif; ?>

            <label>
                Full name<br>
                <input name="name" maxlength="50" required
                       value="<?= e($form['name']) ?>">
            </label>

            <label>
                Username<br>
                <input name="username" minlength="3" maxlength="50"
                       pattern="[A-Za-z0-9_]{3,50}" required
                       value="<?= e($form['username']) ?>">
            </label>

            <label>
                Email<br>
                <input type="email" name="email" maxlength="100" required
                       value="<?= e($form['email']) ?>">
            </label>

            <label>
                Mobile<br>
                <input name="mobile" pattern="[0-9]{10}" maxlength="10"
                       inputmode="numeric" required
                       value="<?= e($form['mobile']) ?>">
            </label>

            <label>
                Course<br>
                <select name="course" required>
                    <option value="">Select course</option>
                    <?php foreach ($allowedCourses as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= $form['course'] === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Year<br>
                <select name="year" required>
                    <option value="">Select year</option>
                    <?php foreach ($allowedYears as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= (string)$form['year'] === $option ? 'selected' : '' ?>>
                            Year <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Gender<br>
                <select name="gender" required>
                    <option value="">Select gender</option>
                    <?php foreach ($allowedGenders as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= $form['gender'] === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php if (!$isEditing): ?>
                <label>
                    Initial password<br>
                    <input type="password" name="password" minlength="8"
                           autocomplete="new-password" required>
                </label>
            <?php else: ?>
                <p>Password is unchanged when editing student details.</p>
            <?php endif; ?>

            <br>
            <button type="submit">
                <?= $isEditing ? 'Save Changes' : 'Add Student' ?>
            </button>

            <?php if ($isEditing): ?>
                <a class="btn cancel" href="students.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <h2>Search and Filter Students</h2>

        <form method="GET" action="students.php">
            <input type="search" name="search"
                   placeholder="Name, username, email or mobile"
                   value="<?= e($search) ?>">

            <select name="course">
                <option value="">All courses</option>
                <?php foreach ($allowedCourses as $option): ?>
                    <option value="<?= e($option) ?>"
                        <?= $courseFilter === $option ? 'selected' : '' ?>>
                        <?= e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="year">
                <option value="">All years</option>
                <?php foreach ($allowedYears as $option): ?>
                    <option value="<?= e($option) ?>"
                        <?= $yearFilter === $option ? 'selected' : '' ?>>
                        Year <?= e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Search / Filter</button>
            <a class="btn cancel" href="students.php">Clear</a>
        </form>
    </div>

    <div class="panel">
        <h2>Student Records (<?= count($students) ?>)</h2>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Gender</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="9">No student records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= (int)$student['student_id'] ?></td>
                            <td><?= e($student['name']) ?></td>
                            <td><?= e($student['username']) ?></td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['mobile']) ?></td>
                            <td><?= e($student['course']) ?></td>
                            <td><?= e($student['year']) ?></td>
                            <td><?= e($student['gender']) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn"
                                       href="students.php?edit=<?= (int)$student['student_id'] ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="students.php"
                                          onsubmit="return confirm('Delete this student record? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id"
                                               value="<?= (int)$student['student_id'] ?>">
                                        <button class="delete" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="logout.php">
        <button type="submit">Log Out</button>
    </form>

</div>
</body>
</html>