<?php
require('database.php');

$message = "";
$status = "";

if (isset($_POST['username'])) {

 
    $username = mysqli_real_escape_string($con, trim($_POST['username']));
    $email = mysqli_real_escape_string($con, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "student"; 

 
    if($password !== $confirm_password){
        $message = "Passwords do not match!";
        $status = "error";
    } else {
      
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $reg_date = date("Y-m-d H:i:s");

      
        $query = "INSERT INTO user (username, password, email, role, register_date)
                  VALUES ('$username', '$hashedPassword', '$email', '$role', '$reg_date')";

        if(mysqli_query($con, $query)){
            $message = "Register successful! <a href='login.php'>Login Now</a>";
            $status = "success";
        } else {
            $message = "Register failed: " . mysqli_error($con);
            $status = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Uni Event Tracker - Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1E3A8A, #3B82F6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-box {
            background: white;
            padding: 40px;
            width: 350px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box; 
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: #1E3A8A;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #162d6e; }
        .login-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .login-link a { color: #1E3A8A; text-decoration: none; font-weight: 500; }
        .msg {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
        }
        .error { background: #FEE2E2; color: #B91C1C; }
        .success { background: #D1FAE5; color: #065F46; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Create Account</h2>

    <?php if($message != ""): ?>
        <div class="msg <?php echo $status; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <input type="text" name="username" placeholder="Full Name / Username" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

</body>
</html>