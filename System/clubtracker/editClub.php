<?php
session_start();
require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$isAdmin   = (strtolower($_SESSION['role'] ?? '') === 'admin');
$userid    = intval($_SESSION['userid']);
$username  = $_SESSION['username'] ?? 'User';
$message   = '';
$msgType   = '';

$club_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($club_id == 0){ die("No Club ID provided!"); }


if($isAdmin){
    $stmt = $con->prepare("SELECT * FROM club WHERE club_id=?");
    $stmt->bind_param("i", $club_id);
} else {
    
    $stmt = $con->prepare("
        SELECT c.* FROM club c
        JOIN club_membership cm ON cm.clubid = c.club_id
        WHERE c.club_id=? AND cm.userid=? AND cm.club_role='Chairperson'
    ");
    $stmt->bind_param("ii", $club_id, $userid);
}
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
if(!$club){ die("Club not found or you don't have permission."); }

// Handle update
if(isset($_POST['update_club'])){
    $club_name        = trim($_POST['club_name'] ?? '');
    $club_description = trim($_POST['club_description'] ?? '');

    if(empty($club_name) || empty($club_description)){
        $message = "Please fill in all fields.";
        $msgType = "error";
    } else {
        $upd = $con->prepare("UPDATE club SET club_name=?, club_description=? WHERE club_id=?");
        $upd->bind_param("ssi", $club_name, $club_description, $club_id);
        if($upd->execute()){
            $message = "✅ Club updated successfully!";
            $msgType = "success";
            // Refresh local data
            $club['club_name']        = $club_name;
            $club['club_description'] = $club_description;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Club – Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}

        
        header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
        .logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
        nav{display:flex;align-items:center;gap:20px;}
        nav a{color:white;text-decoration:none;font-size:14px;}
        nav a:hover{opacity:0.8;}

        .user-menu{display:flex;align-items:center;gap:10px;position: relative;}
        .avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}
        
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none; position: absolute; right: 0; top: 35px;
            background-color: white; min-width: 160px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1000; border-radius: 8px; overflow: hidden;
        }
        .dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; text-align: left;}
        .dropdown-content a:hover { background-color: #f1f5f9; color: #1E3A8A; }
        .dropdown:hover .dropdown-content { display: block; }

        
        .page-wrapper{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;}
        .edit-box{background:white;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,0.08);padding:36px;width:100%;max-width:500px;}
        .edit-box h2{font-size:22px;font-weight:700;color:#1E3A8A;margin-bottom:6px;}
        .edit-box .subtitle{font-size:12px;color:#888;margin-bottom:24px;}

        .form-group{margin-bottom:18px;}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
        .form-group input, .form-group textarea{
            width:100%;padding:12px 14px;
            border:1.5px solid #e2e8f0;border-radius:8px;
            font-family:'Poppins',sans-serif;font-size:14px;color:#333;
            transition:border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus{outline:none;border-color:#1E3A8A;}
        .form-group textarea{resize:vertical;min-height:120px;}

        .status-row{display:flex;align-items:center;gap:10px;padding:12px;background:#f8faff;border-radius:8px;margin-bottom:20px;font-size:13px;}
        .badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;}
        .badge-active, .badge-available{background:#dcfce7;color:#16a34a;}
        .badge-pending{background:#fef3c7;color:#D97706;}

        .alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;}
        .alert.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
        .alert.error{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;}

        .btn-submit{width:100%;padding:14px;background:#1E3A8A;color:white;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:background 0.2s;}
        .btn-submit:hover{background:#163070;}
        .btn-back{display:block;text-align:center;margin-top:20px;font-size:13px;color:#1E3A8A;text-decoration:none;font-weight:600;}
        .btn-back:hover{text-decoration:underline;}

        footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}
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
    <div class="edit-box">
        <h2>✏️ Edit Club</h2>
        <div class="subtitle">Update details for: <strong><?= htmlspecialchars($club['club_name'],ENT_QUOTES,'UTF-8') ?></strong></div>

        <?php if($message): ?>
            <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message,ENT_QUOTES,'UTF-8') ?></div>
        <?php endif; ?>

        <div class="status-row">
            <span style="color:#666;">Current Status:</span>
            <span class="badge badge-<?= strtolower($club['club_status']) ?>"><?= htmlspecialchars($club['club_status'],ENT_QUOTES,'UTF-8') ?></span>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="club_name">Club Name <span style="color:#dc2626">*</span></label>
                <input type="text" id="club_name" name="club_name"
                       value="<?= htmlspecialchars($club['club_name'],ENT_QUOTES,'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="club_description">Club Description <span style="color:#dc2626">*</span></label>
                <textarea id="club_description" name="club_description" required><?= htmlspecialchars($club['club_description'],ENT_QUOTES,'UTF-8') ?></textarea>
            </div>

            <button type="submit" name="update_club" class="btn-submit">💾 Save Changes</button>
        </form>

        <a href="<?= $isAdmin ? '../Admin/admin_club.php' : 'club.php?id='.$club_id ?>" class="btn-back">← Cancel & Go Back</a>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>
</body>
</html>