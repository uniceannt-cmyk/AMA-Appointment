<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: homepage.php#appointments");
    exit();
}

$appointment_id = (int) ($_POST['appointment_id'] ?? 0);
$student_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($con, "UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND status = 'Pending'");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $appointment_id, $student_id);
    mysqli_stmt_execute($stmt);
    $cancelled = mysqli_stmt_affected_rows($stmt) > 0;
    $message = $cancelled
        ? 'Appointment cancelled successfully.'
        : 'Only your pending appointments can be cancelled.';
    mysqli_stmt_close($stmt);
} else {
    $cancelled = false;
    $message = 'Unable to cancel appointment.';
}

echo popup_response_script($message, $cancelled ? 'success' : 'error', 'homepage.php#appointments');
