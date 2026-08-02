<?php
session_start();
require('../database.php');

if (!isset($_SESSION['userid'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

$userid = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'User';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');
$message = '';

if (isset($_POST['submit_external'])) {
    $title = $con->real_escape_string(trim($_POST['title']));
    $event_type = $con->real_escape_string($_POST['event_type']);
    $issued_date = $_POST['issued_date'];
    $award_level = $con->real_escape_string($_POST['award_level']);
    $notes = $con->real_escape_string(trim($_POST['notes']));


    $upload_ok = false;
    $new_filename = null;
    
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
        $target_dir = "../uploads/certificates/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_ext = pathinfo($_FILES["certificate"]["name"], PATHINFO_EXTENSION);
        $new_filename = "ext_" . time() . "_" . $userid . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["certificate"]["tmp_name"], $target_file)) {
            $upload_ok = true;
        }
    }

    if ($upload_ok) {
        
        $sql = "INSERT INTO achievement (userid, title, type, event_type, award_status, award_level, issued_date, notes, file_path) 
                VALUES ($userid, '$title', 'Award', '$event_type', 'Pending', '$award_level', '$issued_date', '$notes', '$new_filename')";
        
        if ($con->query($sql)) {
            $_SESSION['flash_message'] = "✅ External achievement submitted! Pending admin approval.";
            $_SESSION['flash_type'] = "success";
            header("Location: achievement_dashboard.php");
            exit();
        }
    } else {
        $message = "❌ Failed to upload certificate file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload External Achievement - Uni Event Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
<style>
    
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Poppins',sans-serif; background:#f4f6f9; display:flex; flex-direction:column; min-height:100vh;}

    
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;width:100%;}
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
    .card{background:white; padding:30px; border-radius:15px; box-shadow:0 8px 30px rgba(0,0,0,0.1); width:100%; max-width:500px;}
    h2{color:#1E3A8A; margin-bottom:20px; font-size:20px; font-weight:700;}
    
    .form-group{margin-bottom:15px;}
    label{display:block; font-size:12px; font-weight:600; margin-bottom:5px; color:#666;}
    input, select, textarea{width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit; font-size:14px;}
    input:focus, select:focus, textarea:focus{outline:none; border-color:#1E3A8A;}
    
    .btn-submit{background:#1E3A8A; color:white; border:none; padding:12px; width:100%; border-radius:8px; font-weight:600; cursor:pointer; margin-top:10px; transition: 0.3s;}
    .btn-submit:hover{background:#163070;}
    .btn-cancel{display:block; text-align:center; margin-top:15px; color:#888; text-decoration:none; font-size:13px;}
    .btn-cancel:hover{text-decoration:underline;}

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
    <div class="card">
        <h2>🏆 External Achievement</h2>
        <?php if($message): ?><p style="color:red; font-size:13px; margin-bottom:10px;"><?= $message ?></p><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Award / Activity Title</label>
                <input type="text" name="title" placeholder="e.g. State Level Swimming Competition" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="event_type" required>
                    <option value="Competition">Competition</option>
                    <option value="Sport">Sport</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Service/Volunteer">Service/Volunteer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Award Level (e.g. 1st Place, Gold, Participant)</label>
                <input type="text" name="award_level" required>
            </div>
            <div class="form-group">
                <label>Date Earned</label>
                <input type="date" name="issued_date" required>
            </div>
            <div class="form-group">
                <label>Certificate File (Image/PDF)</label>
                <input type="file" name="certificate" accept="image/*,.pdf" required>
            </div>
            <div class="form-group">
                <label>Additional Notes</label>
                <textarea name="notes" rows="2" placeholder="Describe your role or achievement..."></textarea>
            </div>
            <button type="submit" name="submit_external" class="btn-submit">Submit for Approval</button>
            <a href="achievement_dashboard.php" class="btn-cancel">Back to Dashboard</a>
        </form>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>