<?php
session_start();
require('database.php');

$error = "";

if (isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['username'])) {
    $username = mysqli_real_escape_string($con, trim($_POST['username']));
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE username = '$username'";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {
            
    
            session_regenerate_id(true);
            $_SESSION['username'] = $row['username'];
            $_SESSION['userid']   = $row['id']; 
            $_SESSION['role']     = $row['role'];

            
            setcookie("remember_me", $username, time() + (86400 * 7), "/");

            header("Location: index.php");
            exit();

        } else {
            $error = "wrong password";
        }
    } else {
        $error = "user no exist";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
     
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1E3A8A, #3B82F6); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; width: 350px; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1E3A8A; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .error { color: #B91C1C; background: #FEE2E2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if($error != "") echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" 
                   value="<?php echo isset($_COOKIE['remember_me']) ? htmlspecialchars($_COOKIE['remember_me']) : ''; ?>" required>
            
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <div style="margin-top: 20px; font-size: 13px;">
    <a href="forgot_password.php" style="color: #1E3A8A; text-decoration: none;">Forgot Password?</a>
</div>
<div style="margin-top: 10px; font-size: 13px; color: #666;">
    Don't have an account? <a href="register.php" style="color: #1E3A8A; text-decoration: none; font-weight: 600;">Register</a>
</div>
    </div>
    
</body>
</html>