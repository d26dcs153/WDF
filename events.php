<?php
require_once 'auth.php';
requireLogin();
require_once 'db.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admins only.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_token'];
$uploadDir = __DIR__ . '/uploads/event_posters/';

if (!is_dir($uploadDir) &&
    !mkdir($uploadDir, 0755, true) &&
    !is_dir($uploadDir)) {
    exit('Could not create the poster upload directory.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function savePoster($file, $uploadDir) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Poster upload failed.');
    }

    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Poster must be under 2 MB.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException(
            'Only JPG, PNG, and WEBP posters are allowed.'
        );
    }

    // Confirm the uploaded image can actually be decoded.
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Invalid image file.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

    if (!move_uploaded_file(
        $file['tmp_name'],
        $uploadDir . $filename
    )) {
        throw new RuntimeException('Could not save poster.');
    }

    return $filename;
}

function removePoster($filename, $uploadDir) {
    if ($filename && basename($filename) === $filename) {
        $path = $uploadDir . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

$message = '';
$error = '';
$editEvent = null;

// Handle all changes through POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPoster = null;
    $oldPosterToRemove = null;

    try {
        if (!hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token'] ?? ''
        )) {
            throw new RuntimeException('Invalid request token. Reload and try again.');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = filter_var(
                $_POST['event_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id) {
                throw new RuntimeException('Invalid event ID.');
            }

            $stmt = $conn->prepare(
                'SELECT poster FROM events WHERE event_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                throw new RuntimeException('Event not found.');
            }

            $stmt = $conn->prepare(
                'DELETE FROM events WHERE event_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            removePoster($event['poster'], $uploadDir);
            $message = 'Event deleted successfully.';

        } elseif ($action === 'add' || $action === 'update') {
            $id = filter_var(
                $_POST['event_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date = trim($_POST['event_date'] ?? '');
            $venue = trim($_POST['venue'] ?? '');

            if (
                $title === '' || strlen($title) > 150 ||
                $description === '' ||
                $venue === '' || strlen($venue) > 150 ||
                $date === ''
            ) {
                throw new RuntimeException(
                    'Please fill in all fields correctly.'
                );
            }

            $dateObject = DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $date
            );

            if (!$dateObject ||
                $dateObject->format('Y-m-d\TH:i') !== $date) {
                throw new RuntimeException('Invalid event date.');
            }

            $eventDate = $dateObject->format('Y-m-d H:i:s');

            if ($action === 'add') {
                $newPoster = savePoster($_FILES['poster'] ?? [], $uploadDir);

                $stmt = $conn->prepare(
                    'INSERT INTO events
                    (title, description, event_date, venue, poster)
                    VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'sssss',
                    $title, $description, $eventDate, $venue, $newPoster
                );
                $stmt->execute();
                $stmt->close();

                $message = 'Event added successfully.';

            } else {
                if (!$id) {
                    throw new RuntimeException('Invalid event ID.');
                }

                $stmt = $conn->prepare(
                    'SELECT poster FROM events WHERE event_id = ?'
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$existing) {
                    throw new RuntimeException('Event not found.');
                }

                $oldPosterToRemove = $existing['poster'];
                $newPoster = savePoster($_FILES['poster'] ?? [], $uploadDir);
                $poster = $newPoster ?? $oldPosterToRemove;

                $stmt = $conn->prepare(
                    'UPDATE events
                     SET title = ?, description = ?, event_date = ?,
                         venue = ?, poster = ?
                     WHERE event_id = ?'
                );
                $stmt->bind_param(
                    'sssssi',
                    $title, $description, $eventDate,
                    $venue, $poster, $id
                );
                $stmt->execute();
                $stmt->close();

                if ($newPoster !== null) {
                    removePoster($oldPosterToRemove, $uploadDir);
                }

                $message = 'Event updated successfully.';
            }

        } else {
            throw new RuntimeException('Unknown action.');
        }

    } catch (Throwable $ex) {
        if ($newPoster !== null) {
            removePoster($newPoster, $uploadDir);
        }

        $error = $ex instanceof RuntimeException
            ? $ex->getMessage()
            : 'Something went wrong. Please try again.';

        if (!($ex instanceof RuntimeException)) {
            error_log($ex->getMessage());
        }
    }
}

// Load an event into the editing form.
if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $conn->prepare(
            'SELECT * FROM events WHERE event_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editEvent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Retrieve all events.
$result = $conn->query(
    'SELECT * FROM events ORDER BY event_date DESC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Management | StudentHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 25px;
            color: #222;
        }
        .container {
            max-width: 1100px;
            margin: auto;
        }
        .panel {
            background: white;
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #00000012;
        }
        input, textarea, button {
            font: inherit;
            padding: 10px;
            box-sizing: border-box;
        }
        input:not([type=file]), textarea {
            width: 100%;
            margin: 6px 0 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type=file] {
            display: block;
            margin: 8px 0 15px;
        }
        button, .link {
            display: inline-block;
            background: #263c78;
            color: white;
            border: 0;
            padding: 9px 13px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .danger { background: #b42332; }
        .muted { color: #555; }
        .success { color: #187443; }
        .error { color: #b42332; }
        .event {
            border-bottom: 1px solid #ddd;
            padding: 18px 0;
        }
        .poster {
            width: 180px;
            max-height: 180px;
            object-fit: contain;
            display: block;
            margin: 10px 0;
        }
        .actions { display: flex; gap: 8px; align-items: center; }
        .actions form { margin: 0; }
        @media (max-width: 600px) {
            body { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="container">

    <h1>StudentHub — Event Management</h1>
    <p>
        <a class="link" href="admin_dashboard.php">Admin Dashboard</a>
    </p>

    <section class="panel">
        <h2><?= $editEvent ? 'Update Event' : 'Add New Event' ?></h2>

        <?php if ($message): ?>
            <p class="success"><?= e($message) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= e($csrf) ?>">
            <input type="hidden" name="action"
                   value="<?= $editEvent ? 'update' : 'add' ?>">

            <?php if ($editEvent): ?>
                <input type="hidden" name="event_id"
                       value="<?= (int)$editEvent['event_id'] ?>">
            <?php endif; ?>

            <label>Event Title</label>
            <input name="title" maxlength="150" required
                   value="<?= e($editEvent['title'] ?? '') ?>">

            <label>Description</label>
            <textarea name="description" rows="4" required><?= e(
                $editEvent['description'] ?? ''
            ) ?></textarea>

            <label>Date and Time</label>
            <input type="datetime-local" name="event_date" required
                   value="<?= $editEvent
                       ? e(date('Y-m-d\TH:i', strtotime($editEvent['event_date'])))
                       : '' ?>">

            <label>Venue</label>
            <input name="venue" maxlength="150" required
                   value="<?= e($editEvent['venue'] ?? '') ?>">

            <?php if (!empty($editEvent['poster'])): ?>
                <p>Current poster:</p>
                <img class="poster"
                     src="uploads/event_posters/<?= rawurlencode(basename($editEvent['poster'])) ?>"
                     alt="Current event poster">
            <?php endif; ?>

            <label>Event Poster (JPG, PNG, WEBP — max 2 MB)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
            <input type="file" name="poster"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

            <p class="muted">
                <?= $editEvent
                    ? 'Leave the poster empty to keep the current image.'
                    : 'A poster is optional.' ?>
            </p>

            <button type="submit">
                <?= $editEvent ? 'Save Changes' : 'Add Event' ?>
            </button>

            <?php if ($editEvent): ?>
                <a class="link" href="events.php">Cancel</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>All Events</h2>

        <?php if ($result->num_rows === 0): ?>
            <p>No events have been added yet.</p>
        <?php endif; ?>

        <?php while ($event = $result->fetch_assoc()): ?>
            <article class="event">
                <h3><?= e($event['title']) ?></h3>
                <p><?= nl2br(e($event['description'])) ?></p>
                <p><strong>Date:</strong>
                    <?= e(date('d M Y, h:i A', strtotime($event['event_date']))) ?>
                </p>
                <p><strong>Venue:</strong> <?= e($event['venue']) ?></p>

                <?php if (!empty($event['poster'])): ?>
                    <img class="poster"
                         src="uploads/event_posters/<?= rawurlencode(basename($event['poster'])) ?>"
                         alt="<?= e($event['title']) ?> poster">
                <?php endif; ?>

                <div class="actions">
                    <a class="link"
                       href="events.php?edit=<?= (int)$event['event_id'] ?>">
                        Edit
                    </a>

                    <form method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="event_id"
                               value="<?= (int)$event['event_id'] ?>">
                        <button class="danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        <?php endwhile; ?>
    </section>
</div>
</body>
</html>