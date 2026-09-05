<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['user_role'] ?? '') === 'registrar') {
    header("Location: admin.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$user_stmt = mysqli_prepare($con, "SELECT student_id, first_name, last_name, email, role FROM users WHERE student_id = ?");
$user = null;

if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "s", $student_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user = mysqli_fetch_assoc($user_result);
    mysqli_stmt_close($user_stmt);
}

$result = mysqli_query($con, "SELECT id, office, service, appointment_date, appointment_time, status FROM appointments WHERE student_id = '$student_id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="popup.css">
    <script src="popup.js"></script>
</head>

<body>
    <header>
        <h1>AMA Appointment</h1>
        <nav>
            <ul>
                <li class="user-profile-nav">
                    <button type="button" class="profile-trigger" id="profileTrigger" aria-label="View profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo rawurlencode($_SESSION['user_name'] ?? 'Student'); ?>&background=003366&color=fff&size=120" alt="Profile image">
                    </button>
                </li>
                <li>
                    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="welcome-sec">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?>!</h2>
        <p>Select your office and schedule your appointment</p>
    </section>

    <section class="options" id="office-options">
        <div class="cards">
            <div class="card">
                <h4>Registrar Office</h4>
                <p>For Enrollment, Records, and Academic Documents</p>
                <a href="registrar.html" class="btn">Book Now</a>
            </div>
        </div>
    </section>

    <!-- Student Profile Modal -->
    <div class="profile-modal" id="profileModal" aria-hidden="true">
        <div class="profile-modal-content">
            <button type="button" class="close-profile" id="closeProfile" aria-label="Close profile">&times;</button>
            <div class="profile-modal-header">
                <img src="https://ui-avatars.com/api/?name=<?php echo rawurlencode($_SESSION['user_name'] ?? 'Student'); ?>&background=003366&color=fff&size=200" alt="Profile image">
                <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?></h3>
            </div>
            <div class="profile-details">
                <div>
                    <span>Student ID</span>
                    <strong><?php echo htmlspecialchars($user['student_id'] ?? $student_id); ?></strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? ($user['email'] ?? 'N/A')); ?></strong>
                </div>
                <div>
                    <span>Role</span>
                    <strong><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? ($user['role'] ?? 'student'))); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments List Table -->
    <section class="appointments" id="appointments">
        <div class="appointments-heading">
            <h3>My Upcoming appointments</h3>
            <div class="appointment-filters" aria-label="Filter upcoming appointments">
                <label class="sr-only" for="appointmentSearch">Search appointments</label>
                <input type="search" id="appointmentSearch" placeholder="Search appointments...">
                <label class="sr-only" for="appointmentStatusFilter">Filter by status</label>
                <select id="appointmentStatusFilter">
                    <option value="all">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Office</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                            $status = strtolower(trim($row['status']));
                            $statusClass = 'status-default';

                            if ($status === 'approved') {
                                $statusClass = 'status-approved';
                            } elseif ($status === 'declined' || $status === 'rejected') {
                                $statusClass = 'status-declined';
                            } elseif ($status === 'pending') {
                                $statusClass = 'status-pending';
                            }
                        ?>
                        <tr class="appointment-row" data-status="<?php echo htmlspecialchars($status); ?>">
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars(date("h:i A", strtotime($row['appointment_time']))); ?></td>
                            <td><?php echo htmlspecialchars($row['office']); ?></td>
                            <td><?php echo htmlspecialchars($row['service']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status === 'pending'): ?>
                                    <form method="POST" action="cancel_appointment.php" data-confirm="Cancel this appointment?">
                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <button type="submit" class="cancel-button">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No appointments yet.</td>
                    </tr>
                <?php endif; ?>
                <tr id="noAppointmentMatches" hidden>
                    <td colspan="6">No appointments match your filters.</td>
                </tr>
            </tbody>
        </table>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile Modal Logic
            const profileTrigger = document.getElementById('profileTrigger');
            const profileModal = document.getElementById('profileModal');
            const closeProfile = document.getElementById('closeProfile');

            if (profileTrigger && profileModal && closeProfile) {
                profileTrigger.addEventListener('click', function () {
                    profileModal.classList.add('show');
                    profileModal.setAttribute('aria-hidden', 'false');
                });

                closeProfile.addEventListener('click', function () {
                    profileModal.classList.remove('show');
                    profileModal.setAttribute('aria-hidden', 'true');
                });

                profileModal.addEventListener('click', function (event) {
                    if (event.target === profileModal) {
                        profileModal.classList.remove('show');
                        profileModal.setAttribute('aria-hidden', 'true');
                    }
                });
            }

            // Theme Toggle Logic
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                function updateTheme(theme) {
                    document.body.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                    
                    const icon = themeToggle.querySelector('i');
                    if (!icon) return;

                    if (theme === 'dark') {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                        themeToggle.setAttribute('aria-label', 'Toggle light mode');
                    } else {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                        themeToggle.setAttribute('aria-label', 'Toggle dark mode');
                    }
                }

                const savedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                
                if (savedTheme) {
                    updateTheme(savedTheme);
                } else if (prefersDark) {
                    updateTheme('dark');
                } else {
                    updateTheme('light');
                }

                themeToggle.addEventListener('click', function () {
                    const currentTheme = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    updateTheme(currentTheme);
                });
            }

            const appointmentSearch = document.getElementById('appointmentSearch');
            const appointmentStatusFilter = document.getElementById('appointmentStatusFilter');
            const appointmentRows = Array.from(document.querySelectorAll('.appointment-row'));
            const noAppointmentMatches = document.getElementById('noAppointmentMatches');

            function filterAppointments() {
                const keyword = appointmentSearch.value.trim().toLowerCase();
                const selectedStatus = appointmentStatusFilter.value;
                let visibleCount = 0;

                appointmentRows.forEach(function (row) {
                    const matchesText = row.textContent.toLowerCase().includes(keyword);
                    const matchesStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
                    const isVisible = matchesText && matchesStatus;
                    row.hidden = !isVisible;
                    if (isVisible) {
                        visibleCount += 1;
                    }
                });

                noAppointmentMatches.hidden = appointmentRows.length === 0 || visibleCount > 0;
            }

            if (appointmentSearch && appointmentStatusFilter) {
                appointmentSearch.addEventListener('input', filterAppointments);
                appointmentStatusFilter.addEventListener('change', filterAppointments);
            }

        });
    </script>
</body>
</html>