<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($student_id) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($password)) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo call_user_func('popup_response_script', 'Please enter a valid email address.');
        } else {
            $check_stmt = mysqli_prepare($con, "SELECT student_id FROM users WHERE student_id = ? OR email = ?");
            if ($check_stmt === false) {
                echo call_user_func('popup_response_script', 'Database prepare failed: ' . mysqli_error($con));
            } else {
                mysqli_stmt_bind_param($check_stmt, "ss", $student_id, $email);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);

                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    echo call_user_func_array('popup_response_script', ['Student ID or email is already registered!', 'error', 'register.php']);
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $insert_stmt = mysqli_prepare($con, "INSERT INTO users (student_id, first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?, 'student')");
                    if ($insert_stmt === false) {
                        echo call_user_func('popup_response_script', 'Insert prepare failed: ' . mysqli_error($con));
                    } else {
                        mysqli_stmt_bind_param($insert_stmt, "sssss", $student_id, $first_name, $last_name, $email, $hashed_password);

                        if (mysqli_stmt_execute($insert_stmt)) {
                            echo call_user_func_array('popup_response_script', ['Registration Successful!', 'success', 'login.php']);
                            exit();
                        } else {
                            echo call_user_func('popup_response_script', 'Database Error: ' . mysqli_error($con));
                        }
                        mysqli_stmt_close($insert_stmt);
                    }
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    } else {
        echo call_user_func('popup_response_script', 'Please fill out all fields.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="register">
        <h2>Sign Up</h2>
        <form action="register.php" method="POST">


            <div class="input-container">
                <i class="fas fa-id-card"></i>
                <input type="text" name="student_id" placeholder="Enter your ID Number" required>
            </div>

            <div class="input-container">
                <i class="fas fa-user"></i>
                <input type="text" name="first_name" placeholder="Enter your First Name" required>
            </div>

            <div class="input-container">
                <i class="fas fa-user"></i>
                <input type="text" name="last_name" placeholder="Enter your Last Name" required>
            </div>

            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your Email" required>
            </div>

            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Create a Password" required>
            </div>

            <button type="submit">Sign-Up</button>

        </form>

        <p>Already have an account? <a href="login.php">Log-In</a></p>
    </div>

</body>
</html>