
<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only logged-in administrators may manage events.
if (empty($_SESSION['student_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin') {
    respond(403, ['success' => false, 'message' => 'Admin access required.']);
}

$method = $_SERVER['REQUEST_METHOD'];
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if ($method !== 'GET') {
    if (empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrf)) {
        respond(403, ['success' => false, 'message' => 'Invalid security token.']);
    }
}

function readJson() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respond(400, ['success' => false, 'message' => 'Invalid JSON data.']);
    }
    return $data;
}

function validateEvent($data) {
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $date = trim($data['event_date'] ?? '');
    $venue = trim($data['venue'] ?? '');

    if ($title === '' || $description === '' ||
        $date === '' || $venue === '') {
        respond(422, ['success' => false, 'message' => 'All fields are required.']);
    }

    if (mb_strlen($title) > 150 || mb_strlen($venue) > 150) {
        respond(422, ['success' => false, 'message' => 'Title and venue must be 150 characters or fewer.']);
    }

    $parsed = DateTime::createFromFormat('Y-m-d\TH:i', $date);
    if (!$parsed || $parsed->format('Y-m-d\TH:i') !== $date) {
        respond(422, ['success' => false, 'message' => 'Invalid event date or time.']);
    }

    return [$title, $description, $parsed->format('Y-m-d H:i:s'), $venue];
}

try {
    if ($method === 'GET') {
        $result = $conn->query(
            'SELECT event_id, title, description, event_date, venue
             FROM events ORDER BY event_date DESC'
        );

        if (!$result) {
            respond(500, ['success' => false, 'message' => 'Could not fetch events.']);
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        respond(200, ['success' => true, 'events' => $events]);
    }

    if ($method === 'POST') {
        $data = readJson();
        [$title, $description, $date, $venue] = validateEvent($data);

        $stmt = $conn->prepare(
            'INSERT INTO events (title, description, event_date, venue)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $title, $description, $date, $venue);
        $stmt->execute();

        respond(201, [
            'success' => true,
            'message' => 'Event added successfully.',
            'event_id' => $conn->insert_id
        ]);
    }

    if ($method === 'PUT') {
        $data = readJson();
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(422, ['success' => false, 'message' => 'Valid event ID is required.']);
        }

        [$title, $description, $date, $venue] = validateEvent($data);

        $stmt = $conn->prepare(
            'UPDATE events
             SET title = ?, description = ?, event_date = ?, venue = ?
             WHERE event_id = ?'
        );
        $stmt->bind_param('ssssi', $title, $description, $date, $venue, $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $check = $conn->prepare('SELECT event_id FROM events WHERE event_id = ?');
            $check->bind_param('i', $id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                respond(404, ['success' => false, 'message' => 'Event not found.']);
            }
        }

        respond(200, ['success' => true, 'message' => 'Event updated successfully.']);
    }

    if ($method === 'DELETE') {
        $data = readJson();
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(422, ['success' => false, 'message' => 'Valid event ID is required.']);
        }

        $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            respond(404, ['success' => false, 'message' => 'Event not found.']);
        }

        respond(200, ['success' => true, 'message' => 'Event deleted successfully.']);
    }

    header('Allow: GET, POST, PUT, DELETE');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);

} catch (Throwable $e) {
    error_log('Events API error: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'A server error occurred.']);
}