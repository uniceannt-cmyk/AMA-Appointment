<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    $office = trim($_POST['office'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');

    if (!empty($office) && !empty($service) && !empty($date) && !empty($time)) {
        $stmt = mysqli_prepare($con, "INSERT INTO appointments (student_id, office, service, appointment_date, appointment_time) VALUES (?, ?, ?, ?, ?)");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $student_id, $office, $service, $date, $time);

            if (mysqli_stmt_execute($stmt)) {
                echo popup_response_script('Appointment booked successfully!', 'success', 'homepage.php');
                exit();
            } else {
                echo popup_response_script('Failed to book appointment.', 'error', 'homepage.php');
            }
        } else {
            echo popup_response_script('Database error.', 'error', 'homepage.php');
        }
    } else {
        echo popup_response_script('Please complete all appointment fields.', 'error', 'homepage.php');
    }
}
?>
