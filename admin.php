<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['user_role'] ?? '') !== 'registrar') {
    header("Location: homepage.php");
    exit();
}

// Fetch admin profile information
$admin_id = $_SESSION['user_id'];
$admin_stmt = mysqli_prepare($con, "SELECT student_id, first_name, last_name, email, role FROM users WHERE student_id = ?");
$admin = null;

if ($admin_stmt) {
    mysqli_stmt_bind_param($admin_stmt, "s", $admin_id);
    mysqli_stmt_execute($admin_stmt);
    $admin_result = mysqli_stmt_get_result($admin_stmt);
    $admin = mysqli_fetch_assoc($admin_result);
    mysqli_stmt_close($admin_stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = (int) $_POST['appointment_id'];
    $status = trim($_POST['status']);
    $stmt = mysqli_prepare($con, "UPDATE appointments SET status = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($con, "SELECT a.id, a.student_id, a.office, a.service, a.appointment_date, a.appointment_time, a.status, u.first_name, u.last_name FROM appointments a JOIN users u ON a.student_id = u.student_id WHERE a.status = 'Pending' ORDER BY a.appointment_date, a.appointment_time");

$history_sql = "SELECT a.id, a.student_id, a.office, a.service, a.appointment_date, a.appointment_time, a.status, u.first_name, u.last_name FROM appointments a JOIN users u ON a.student_id = u.student_id WHERE a.status IN ('Approved', 'Rejected') ORDER BY a.appointment_date DESC, a.appointment_time DESC";

if (($_GET['export'] ?? '') === 'csv') {
    $export_result = mysqli_query($con, $history_sql);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="processed-appointments.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Student ID', 'First Name', 'Last Name', 'Office', 'Service', 'Date', 'Time', 'Status']);
    if ($export_result) {
        while ($export_row = mysqli_fetch_assoc($export_result)) {
            fputcsv($output, [
                $export_row['id'],
                $export_row['student_id'],
                $export_row['first_name'],
                $export_row['last_name'],
                $export_row['office'],
                $export_row['service'],
                $export_row['appointment_date'],
                $export_row['appointment_time'],
                $export_row['status']
            ]);
        }
    }
    fclose($output);
    exit();
}

$pastResult = mysqli_query($con, $history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Registrar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <header class="dashboard-header">
        <h1>Registrar Admin Dashboard</h1>
        <nav class="nav-links">
            <button type="button" class="profile-trigger" id="profileTrigger" aria-label="View profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo rawurlencode($_SESSION['user_name'] ?? 'Admin'); ?>&background=003366&color=fff&size=120" alt="Profile avatar">
            </button>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <!-- Admin Profile Modal -->
    <div class="profile-modal" id="profileModal" aria-hidden="true">
        <div class="profile-modal-content">
            <button type="button" class="close-profile" id="closeProfile" aria-label="Close profile">&times;</button>
            <div class="profile-modal-header">
                <img src="https://ui-avatars.com/api/?name=<?php echo rawurlencode($_SESSION['user_name'] ?? 'Admin'); ?>&background=003366&color=fff&size=200" alt="Profile avatar">
                <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Registrar Admin'); ?></h3>
            </div>
            <div class="profile-details">
                <div>
                    <span>Admin ID</span>
                    <strong><?php echo htmlspecialchars($admin['student_id'] ?? $admin_id); ?></strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? ($admin['email'] ?? 'N/A')); ?></strong>
                </div>
                <div>
                    <span>Role</span>
                    <strong><?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? ($admin['role'] ?? 'registrar'))); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <main class="table-card">
        <div class="section-header-row">
            <h2>Pending Appointment Requests</h2>
            <label class="group-by-control" for="pendingGroupBy">
                <span>Group by</span>
                <select id="pendingGroupBy">
                    <option value="date">Date</option>
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                </select>
            </label>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Office</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $pendingYear = ''; $pendingMonth = ''; $pendingDate = ''; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                                $rowDate = $row['appointment_date'];
                                $rowYear = date('Y', strtotime($rowDate));
                                $rowMonth = date('Y-m', strtotime($rowDate));
                                if ($rowYear !== $pendingYear) {
                                    $pendingYear = $rowYear;
                                    $pendingMonth = '';
                                    $pendingDate = '';
                            ?>
                                <tr class="group-header year-group" data-group="year"><td colspan="9"><?php echo htmlspecialchars($rowYear); ?></td></tr>
                            <?php } ?>
                            <?php if ($rowMonth !== $pendingMonth) {
                                    $pendingMonth = $rowMonth;
                                    $pendingDate = '';
                            ?>
                                <tr class="group-header month-group" data-group="month"><td colspan="9"><?php echo htmlspecialchars(date('F', strtotime($rowDate))); ?></td></tr>
                            <?php } ?>
                            <?php if ($rowDate !== $pendingDate) { $pendingDate = $rowDate; ?>
                                <tr class="group-header date-group" data-group="date"><td colspan="9"><?php echo htmlspecialchars(date('l, F j', strtotime($rowDate))); ?></td></tr>
                            <?php } ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['office']); ?></td>
                                <td><?php echo htmlspecialchars($row['service']); ?></td>
                                <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($row['appointment_time']))); ?></td>
                                <td>
                                    <span class="badge badge-pending">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <select name="status">
                                            <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Approved" <?php echo ($row['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Rejected" <?php echo ($row['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-row">No pending appointments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <main class="table-card history-card" id="past-appointments">
        <div class="history-header-row">
            <h2>Past Appointment Records</h2>
            <div class="history-toolbar">
                <label class="group-by-control" for="historyGroupBy">
                    <span>Group by</span>
                    <select id="historyGroupBy">
                        <option value="date">Date</option>
                        <option value="month">Month</option>
                        <option value="year">Year</option>
                    </select>
                </label>
                <label class="history-search-box" for="pastSearch">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Search past appointments</span>
                    <input type="search" id="pastSearch" placeholder="Search records...">
                </label>
                <a href="admin.php?export=csv" class="export-button">
                    <i class="fas fa-file-csv" aria-hidden="true"></i>
                    Export to CSV
                </a>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Office</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="pastAppointmentsTable">
                    <?php if ($pastResult && mysqli_num_rows($pastResult) > 0): ?>
                        <?php $historyYear = ''; $historyMonth = ''; $historyDate = ''; ?>
                        <?php while ($pastRow = mysqli_fetch_assoc($pastResult)): ?>
                            <?php
                                $rowDate = $pastRow['appointment_date'];
                                $rowYear = date('Y', strtotime($rowDate));
                                $rowMonth = date('Y-m', strtotime($rowDate));
                                if ($rowYear !== $historyYear) {
                                    $historyYear = $rowYear;
                                    $historyMonth = '';
                                    $historyDate = '';
                            ?>
                                <tr class="group-header year-group" data-group="year"><td colspan="8"><?php echo htmlspecialchars($rowYear); ?></td></tr>
                            <?php } ?>
                            <?php if ($rowMonth !== $historyMonth) {
                                    $historyMonth = $rowMonth;
                                    $historyDate = '';
                            ?>
                                <tr class="group-header month-group" data-group="month"><td colspan="8"><?php echo htmlspecialchars(date('F', strtotime($rowDate))); ?></td></tr>
                            <?php } ?>
                            <?php if ($rowDate !== $historyDate) { $historyDate = $rowDate; ?>
                                <tr class="group-header date-group" data-group="date"><td colspan="8"><?php echo htmlspecialchars(date('l, F j', strtotime($rowDate))); ?></td></tr>
                            <?php } ?>
                            <tr class="data-row">
                                <td>#<?php echo htmlspecialchars($pastRow['id']); ?></td>
                                <td><?php echo htmlspecialchars($pastRow['student_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($pastRow['first_name'] . ' ' . $pastRow['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pastRow['office']); ?></td>
                                <td><?php echo htmlspecialchars($pastRow['service']); ?></td>
                                <td><?php echo htmlspecialchars($pastRow['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($pastRow['appointment_time']))); ?></td>
                                <td>
                                    <span class="badge <?php echo ($pastRow['status'] == 'Approved') ? 'badge-approved' : 'badge-rejected'; ?>">
                                        <?php echo htmlspecialchars($pastRow['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-row">No past appointment records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile Modal Handler
            const profileTrigger = document.getElementById('profileTrigger');
            const profileModal = document.getElementById('profileModal');
            const closeProfile = document.getElementById('closeProfile');

            if (profileTrigger && profileModal && closeProfile) {
                profileTrigger.addEventListener('click', function () {
                    profileModal.classList.add('show');
                    profileModal.setAttribute('aria-hidden', 'false');
                });


            const searchInput = document.getElementById('pastSearch');
            const rows = Array.from(document.querySelectorAll('#pastAppointmentsTable .data-row'));

            function setupGrouping(selectId, tableSelector) {
                const groupSelect = document.getElementById(selectId);
                const table = document.querySelector(tableSelector);
                if (!groupSelect || !table) return;

                groupSelect.addEventListener('change', function () {
                    table.querySelectorAll('.group-header').forEach(function (header) {
                        header.hidden = header.dataset.group !== groupSelect.value;
                    });
                });

                groupSelect.dispatchEvent(new Event('change'));
            }

            setupGrouping('pendingGroupBy', 'main.table-card:not(.history-card) table');
            setupGrouping('historyGroupBy', '#pastAppointmentsTable');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const keyword = this.value.trim().toLowerCase();

                    rows.forEach(function (row) {
                        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
                    });
                });
            }
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

            // Real-Time Table Search
        });
    </script>

</body>
</html>