<?php
session_start();
include("db.php");

$alert_message = "";
$redirect_url = "";
$is_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = mysqli_prepare($con, "SELECT student_id, first_name, last_name, email, password, role FROM users WHERE email = ?");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['student_id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    $alert_message = "Login successful!";
                    $redirect_url = ($user['role'] === 'registrar') ? 'admin.php' : 'homepage.php';
                    $is_success = true;
                } else {
                    $alert_message = "Invalid email or password.";
                    $redirect_url = "login.php";
                }
            } else {
                $alert_message = "Invalid email or password.";
                $redirect_url = "login.php";
            }

            mysqli_stmt_close($stmt);
        } else {
            $alert_message = "Database error.";
        }
    } else {
        $alert_message = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <title>Log-In</title>
</head>

<body>
    <div class="login">
        <h2>Log-In</h2>

        <form action="login.php" method="POST">
            <div class="input-container">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter your Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>

            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Enter your Password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="register.php">Sign-Up</a></p>
    </div>

    <!-- Custom Pop-up Modal -->
    <div class="custom-modal-overlay" id="customModal" aria-hidden="true">
        <div class="custom-modal-box">
            <div class="custom-modal-icon" id="modalIcon">
                <i class="fas fa-info-circle"></i>
            </div>
            <p class="custom-modal-text" id="modalText"></p>
            <button class="custom-modal-btn" id="modalBtn">OK</button>
        </div>
    </div>

    <script>
        const alertMsg = <?php echo json_encode($alert_message); ?>;
        const redirectUrl = <?php echo json_encode($redirect_url); ?>;
        const isSuccess = <?php echo json_encode($is_success); ?>;

        if (alertMsg) {
            const modal = document.getElementById('customModal');
            const modalText = document.getElementById('modalText');
            const modalBtn = document.getElementById('modalBtn');
            const modalIcon = document.getElementById('modalIcon');

            // Switch icon based on success or error state
            if (isSuccess) {
                modalIcon.innerHTML = '<i class="fas fa-check-circle" style="color: #22c55e;"></i>';
            } else {
                modalIcon.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>';
            }

            modalText.textContent = alertMsg;
            modal.classList.add('show');

            modalBtn.addEventListener('click', function () {
                modal.classList.remove('show');
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            });
        }
    </script>
</body>
</html>