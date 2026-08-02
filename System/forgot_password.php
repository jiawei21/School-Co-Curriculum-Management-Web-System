<?php
session_start();
require('database.php');

$error = "";
$success = "";
$step = 1; 
$target_userid = 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (isset($_POST['verify_account'])) {
        $username = mysqli_real_escape_string($con, trim($_POST['username']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));

        $query = "SELECT id FROM user WHERE username = '$username' AND email = '$email'";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['temp_reset_id'] = $row['id']; // 存入 session 保证安全
            $step = 2; // 进入第二步
        } else {
            $error = "User info not found or email doesn't match.";
        }
    }

   
    if (isset($_POST['reset_password'])) {
        if (!isset($_SESSION['temp_reset_id'])) {
            header("Location: forgot_password.php");
            exit();
        }

        $new_pw = $_POST['new_password'];
        $confirm_pw = $_POST['confirm_password'];

        if ($new_pw !== $confirm_pw) {
            $error = "Passwords do not match!";
            $step = 2; 
        } else {
            $userid = $_SESSION['temp_reset_id'];
            $hashed_pw = password_hash($new_pw, PASSWORD_DEFAULT);
            
            $update = "UPDATE user SET password = '$hashed_pw' WHERE id = $userid";
            if (mysqli_query($con, $update)) {
                $success = "Password reset successfully!";
                unset($_SESSION['temp_reset_id']); 
                $step = 3; 
            } else {
                $error = "Database error. Please try again.";
                $step = 2;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reset Password - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1E3A8A, #3B82F6); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; width: 360px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); text-align: center; }
        h2 { color: #1E3A8A; margin-bottom: 10px; }
        p { font-size: 13px; color: #666; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd; box-sizing: border-box; font-family: inherit; }
        button { width: 100%; padding: 12px; background: #1E3A8A; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .error { color: #B91C1C; background: #FEE2E2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .success { color: #065F46; background: #D1FAE5; padding: 15px; border-radius: 8px; font-size: 14px; font-weight: 600; }
        .back-link { display: block; margin-top: 15px; font-size: 13px; color: #1E3A8A; text-decoration: none; }
    </style>
</head>
<body>

    <div class="box">
        <?php if($step < 3): ?>
            <h2>Reset Password</h2>
        <?php endif; ?>

        <?php if($error != "") echo "<div class='error'>$error</div>"; ?>

        <?php if($step == 1): ?>
            <p>Enter your username and registered email to continue.</p>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <button type="submit" name="verify_account">Verify Account</button>
            </form>
        <?php endif; ?>

        <?php if($step == 2): ?>
            <p>Account verified! Now enter your new password.</p>
            <form method="POST">
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit" name="reset_password">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if($step == 3): ?>
            <div class="success">🎉 <?= $success ?></div>
            <p style="margin-top:15px;">You can now log in with your new password.</p>
            <a href="login.php" style="display:block; background:#1E3A8A; color:white; padding:12px; border-radius:8px; text-decoration:none; font-weight:600;">Go to Login</a>
        <?php endif; ?>

        <?php if($step != 3): ?>
            <a href="login.php" class="back-link">← Back to Login</a>
        <?php endif; ?>
    </div>

</body>
</html>