<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">
            StudentHub
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMenu"
                aria-controls="navbarMenu"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>

                <?php if ($role === 'admin'): ?>

                    <li class="nav-item">
                        <a class="nav-link"
                           href="admin_dashboard.php">Dashboard</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           href="students.php">Manage Students</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           href="events.php">Manage Events</a>
                    </li>

                <?php elseif ($role === 'student'): ?>

                    <li class="nav-item">
                        <a class="nav-link"
                           href="events.php">Events</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link"
                           href="profile.php">Profile</a>
                    </li>

                <?php endif; ?>

                <?php if (isset($_SESSION['student_id'])): ?>
                    <li class="nav-item">
                        <form method="POST" action="logout.php"
                              class="d-inline">
                            <button type="submit"
                                    class="btn btn-outline-light btn-sm mt-1 ms-lg-2">
                                Log Out
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>