
<?php
require_once __DIR__ . '/auth.php';

if (empty($_SESSION['student_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Admin access required. Please log in as an administrator.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub - Events API</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px;
               margin: 30px auto; padding: 0 16px; background: #f5f6fa; }
        form, .event { background: white; padding: 16px; margin: 12px 0;
                       border-radius: 8px; }
        input, textarea { display: block; box-sizing: border-box;
                          width: 100%; padding: 10px; margin: 8px 0; }
        button { padding: 8px 12px; margin: 4px; cursor: pointer; }
        #message { font-weight: bold; }
    </style>
</head>
<body>
    <h1>StudentHub Event Management</h1>
    <p id="message" role="status"></p>

    <form id="eventForm">
        <h2 id="formTitle">Add Event</h2>
        <input type="hidden" id="event_id">
        <input id="title" placeholder="Event title" maxlength="150" required>
        <textarea id="description" placeholder="Description" required></textarea>
        <input id="event_date" type="datetime-local" required>
        <input id="venue" placeholder="Venue" maxlength="150" required>
        <button type="submit">Save Event</button>
        <button type="button" id="cancelEdit" hidden>Cancel Edit</button>
    </form>

    <h2>All Events</h2>
    <div id="events">Loading events...</div>

    <script>
        const API = 'api/events.php';
        const csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
        const form = document.getElementById('eventForm');
        const eventsBox = document.getElementById('events');
        const message = document.getElementById('message');

        async function request(method, data = null) {
            const options = {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                }
            };

            if (data !== null) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }

            const response = await fetch(API, options);
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Request failed.');
            }
            return result;
        }

        function showMessage(text, isError = false) {
            message.textContent = text;
            message.style.color = isError ? 'red' : 'green';
        }

        async function loadEvents() {
            try {
                const result = await request('GET');
                eventsBox.replaceChildren();

                if (result.events.length === 0) {
                    eventsBox.textContent = 'No events found.';
                    return;
                }

                result.events.forEach(event => {
                    const card = document.createElement('div');
                    card.className = 'event';

                    const heading = document.createElement('h3');
                    heading.textContent = event.title;

                    const description = document.createElement('p');
                    description.textContent = event.description;

                    const details = document.createElement('p');
                    details.textContent =
                        'Date: ' + event.event_date +
                        ' | Venue: ' + event.venue;

                    const edit = document.createElement('button');
                    edit.textContent = 'Edit';
                    edit.addEventListener('click', () => editEvent(event));

                    const del = document.createElement('button');
                    del.textContent = 'Delete';
                    del.addEventListener('click', () => deleteEvent(event.event_id));

                    card.append(heading, description, details, edit, del);
                    eventsBox.appendChild(card);
                });
            } catch (error) {
                showMessage(error.message, true);
            }
        }

        function editEvent(event) {
            document.getElementById('event_id').value = event.event_id;
            document.getElementById('title').value = event.title;
            document.getElementById('description').value = event.description;
            document.getElementById('event_date').value =
                event.event_date.replace(' ', 'T').slice(0, 16);
            document.getElementById('venue').value = event.venue;
            document.getElementById('formTitle').textContent = 'Update Event';
            document.getElementById('cancelEdit').hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() {
            form.reset();
            document.getElementById('event_id').value = '';
            document.getElementById('formTitle').textContent = 'Add Event';
            document.getElementById('cancelEdit').hidden = true;
        }

        document.getElementById('cancelEdit').addEventListener('click', resetForm);

        form.addEventListener('submit', async event => {
            event.preventDefault();

            const id = document.getElementById('event_id').value;
            const data = {
                title: document.getElementById('title').value.trim(),
                description: document.getElementById('description').value.trim(),
                event_date: document.getElementById('event_date').value,
                venue: document.getElementById('venue').value.trim()
            };

            try {
                const result = id
                    ? await request('PUT', { ...data, event_id: Number(id) })
                    : await request('POST', data);

                showMessage(result.message);
                resetForm();
                await loadEvents();
            } catch (error) {
                showMessage(error.message, true);
            }
        });

        async function deleteEvent(id) {
            if (!confirm('Are you sure you want to delete this event?')) return;

            try {
                const result = await request('DELETE', { event_id: Number(id) });
                showMessage(result.message);
                await loadEvents();
            } catch (error) {
                showMessage(error.message, true);
            }
        }

        loadEvents();
    </script>
</body>
</html>