<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "register"; // use ONE database only
$port = 3307;

mysqli_report(MYSQLI_REPORT_OFF);

$con = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$con) {
    $con = mysqli_connect("localhost", $user, $pass, $dbname, 3306);
}

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!mysqli_select_db($con, $dbname)) {
    if (!mysqli_query($con, "CREATE DATABASE IF NOT EXISTS `$dbname`")) {
        die("Database creation failed: " . mysqli_error($con));
    }

    if (!mysqli_select_db($con, $dbname)) {
        die("Unable to select database '$dbname': " . mysqli_error($con));
    }
}

if (!mysqli_set_charset($con, "utf8mb4")) {
    error_log("Failed to set charset: " . mysqli_error($con));
}

function popup_response_script($message, $type = 'error', $redirect = '') {
    $redirect_script = $redirect !== '' ? 'window.location.href = ' . json_encode($redirect) . ';' : '';
    return '<script>document.addEventListener("DOMContentLoaded", function () { var style = document.createElement("link"); style.rel = "stylesheet"; style.href = "popup.css"; document.head.appendChild(style); var script = document.createElement("script"); script.src = "popup.js"; script.onload = function () { AppPopup.show(' . json_encode($message) . ', ' . json_encode($type) . '); }; document.body.appendChild(script); window.addEventListener("appPopupClosed", function () { ' . $redirect_script . ' }); });</script>';
}

$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL DEFAULT '',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'registrar') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($con, $table_sql)) {
    die("Users table creation failed: " . mysqli_error($con));
}

$columns_result = mysqli_query($con, "SHOW COLUMNS FROM users");
$existing_columns = [];
if ($columns_result) {
    while ($column = mysqli_fetch_assoc($columns_result)) {
        $existing_columns[] = $column['Field'];
    }
}

$missing_columns = [
    'student_id' => "ALTER TABLE users ADD COLUMN student_id VARCHAR(50) NOT NULL DEFAULT '' AFTER id",
    'role' => "ALTER TABLE users ADD COLUMN role ENUM('student', 'registrar') NOT NULL DEFAULT 'student' AFTER password"
];

foreach ($missing_columns as $column_name => $alter_sql) {
    if (!in_array($column_name, $existing_columns, true)) {
        mysqli_query($con, $alter_sql);
    }
}

$appointments_columns_result = mysqli_query($con, "SHOW COLUMNS FROM appointments");
if ($appointments_columns_result) {
    $appointments_columns = [];
    $status_type = '';
    while ($column = mysqli_fetch_assoc($appointments_columns_result)) {
        $appointments_columns[] = $column['Field'];
        if ($column['Field'] === 'status') {
            $status_type = $column['Type'];
        }
    }

    if (strpos(strtolower($status_type), 'enum') === 0) {
        mysqli_query($con, "ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending'");
    }
}

$admin_email = 'admin@aclc.com';
$admin_password = 'admin123';
$admin_exists = mysqli_query($con, "SELECT id FROM users WHERE role = 'registrar' OR email = '$admin_email' LIMIT 1");

if ($admin_exists && mysqli_num_rows($admin_exists) === 0) {
    $hashed_admin_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $admin_insert = mysqli_prepare($con, "INSERT INTO users (student_id, first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");

    if ($admin_insert) {
        $admin_student_id = 'ADMIN-001';
        $admin_name = 'Registrar';
        $admin_last_name = 'Admin';
        $admin_role = 'registrar';

        mysqli_stmt_bind_param($admin_insert, 'ssssss', $admin_student_id, $admin_name, $admin_last_name, $admin_email, $hashed_admin_password, $admin_role);
        mysqli_stmt_execute($admin_insert);
        mysqli_stmt_close($admin_insert);
    }
}
?>