<?php
session_start();
require("../database.php");

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = intval($_SESSION['userid']);
$username_session = $_SESSION['username'] ?? 'User';
$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');
$message = '';
$msgType = '';


$stmt = $con->prepare("SELECT username, email FROM user WHERE id = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();


if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_username) || empty($new_email)) {
        $message = "Username and Email cannot be empty.";
        $msgType = "error";
    } else {
        $upd = $con->prepare("UPDATE user SET username = ?, email = ? WHERE id = ?");
        $upd->bind_param("ssi", $new_username, $new_email, $userid);
        
        if ($upd->execute()) {
            $_SESSION['username'] = $new_username; 
            $message = "✅ Profile updated successfully!";
            $msgType = "success";
            
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
                    $pw_upd = $con->prepare("UPDATE user SET password = ? WHERE id = ?");
                    $pw_upd->bind_param("si", $hashed_pw, $userid);
                    $pw_upd->execute();
                    $message .= " Password also updated.";
                } else {
                    $message = "Profile updated, but passwords did not match.";
                    $msgType = "error";
                }
            }
           
            $user['username'] = $new_username;
            $user['email'] = $new_email;
        } else {
            $message = "Update failed: " . $con->error;
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}
        
        header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
        .logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
        nav{display:flex;align-items:center;gap:20px;}
        nav a{color:white;text-decoration:none;font-size:14px;}
        nav a:hover{opacity:0.8;}

       
        .user-menu{display:flex;align-items:center;gap:12px;position: relative;}
        .avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;text-decoration:none;}
        
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 35px;
            background-color: white; min-width: 160px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1000; border-radius: 8px; overflow: hidden;
        }
        .dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; text-align: left;}
        .dropdown-content a:hover { background-color: #f1f5f9; color: #1E3A8A; }
        .dropdown:hover .dropdown-content { display: block; }

        
        .page-wrapper{flex:1; display:flex; justify-content:center; align-items:flex-start; padding:40px 20px;}
        .edit-card{background:white; border-radius:16px; box-shadow:0 4px 25px rgba(0,0,0,0.08); padding:35px; width:100%; max-width:500px;}
        
        h2{color:#1E3A8A; font-size:22px; margin-bottom:8px; font-weight:700;}
        .subtitle{font-size:13px; color:#888; margin-bottom:25px;}

        .form-group{margin-bottom:18px;}
        label{display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#444;}
        input{width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:8px; font-family:inherit; font-size:14px; transition:0.2s;}
        input:focus{outline:none; border-color:#1E3A8A;}

        .alert{padding:12px; border-radius:8px; margin-bottom:20px; font-size:13px; font-weight:500;}
        .success{background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;}
        .error{background:#fee2e2; color:#dc2626; border:1px solid #fca5a5;}

        .btn-save{width:100%; padding:12px; background:#1E3A8A; color:white; border:none; border-radius:8px; font-weight:600; font-size:15px; cursor:pointer; margin-top:10px; transition:0.2s;}
        .btn-save:hover{background:#163070;}
        
        .back-link{display:block; text-align:center; margin-top:20px; font-size:13px; color:#666; text-decoration:none;}
        .back-link:hover{color:#1E3A8A; text-decoration:underline;}

        hr{margin:25px 0; border:0; border-top:1px solid #eee;}
        footer{text-align:center; padding:20px; background:#1E3A8A; color:white; font-size:13px; margin-top:auto;}
    </style>
</head>
<body>

<header>
    <div class="logo"><a href="../index.php">Uni Event Tracker</a></div>
    <nav>
        <a href="../Event/event_dashboard.php">Event</a>
        <a href="../clubtracker/clubHandler.php">Club</a>
        <a href="../merit/merit_dashboard.php">Merit</a>
        <a href="../achievement/achievement_dashboard.php">Achievement</a>
         <?php if(isset($_SESSION['username'])){ ?>
        <div class="user-menu">
            <div class="avatar-circle" <a href="user/userprofile.php"><?php echo strtoupper(mb_substr($_SESSION['username'], 0, 1)); ?></div>
            <a href="../user/userprofile.php"><span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span><a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){ ?>
            <a href="../Admin/admin_dashboard.php">Admin Panel</a>
            <?php } ?>
            <a href="../logout.php">Logout</a>
        </div>
        <?php } else { ?>
        <a href="../register.php">Register</a>
        <a href="../login.php">Login</a>
        <?php } ?>
    </nav>
</header>

<div class="page-wrapper">
    <div class="edit-card">
        <h2>Settings</h2>
        <p class="subtitle">Update your personal information and security.</p>

        <?php if($message): ?>
            <div class="alert <?= $msgType ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <hr>
            <p style="font-size:11px; font-weight:700; color:#1E3A8A; margin-bottom:15px; text-transform:uppercase; letter-spacing:1px;">Security</p>
            
            <div class="form-group">
                <label>New Password (Leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="••••••••">
            </div>

            <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        </form>

        <a href="userprofile.php" class="back-link">← Cancel and Go Back</a>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>