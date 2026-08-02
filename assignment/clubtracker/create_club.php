<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid = $_SESSION['userid'];
$username = $_SESSION['username'] ?? 'User';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');
$message = "";
$messageType = "";


$stmt_check = $con->prepare("
    SELECT c.club_id, c.club_name, c.club_status 
    FROM club c
    JOIN club_membership cm ON c.club_id = cm.clubid
    WHERE cm.userid=?
    ORDER BY c.club_CreateDate DESC LIMIT 1
");
$stmt_check->bind_param("i",$userid);
$stmt_check->execute();
$myClub = $stmt_check->get_result()->fetch_assoc();

if(isset($_POST['create'])){
    $club_name        = trim($_POST['club_name']);
    $club_description = trim($_POST['club_description']);
    $date             = date("Y-m-d H:i:s");
    $club_status      = "Pending";

    $stmt = $con->prepare("INSERT INTO club (club_name, club_CreateDate, club_description, club_status) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $club_name, $date, $club_description, $club_status);

    if($stmt->execute()){
        $clubid = $con->insert_id;
        $role = "Chairperson";
        $register_status = "Approved";
        $stmt2 = $con->prepare("INSERT INTO club_membership (userid, clubid, club_role, club_joinDate, register_status) VALUES (?,?,?,?,?)");
        $stmt2->bind_param("iisss", $userid, $clubid, $role, $date, $register_status);
        $stmt2->execute();

        $message = "Club created successfully! You are now the Chairperson.";
        $messageType = "success";
        $myClub = ["club_id"=>$clubid, "club_name"=>$club_name, "club_status"=>$club_status];
    } else {
        $message = "Something went wrong. Please try again.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Club - Uni Event Tracker</title>
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

        
        .page-wrapper{flex:1;max-width:560px;margin:40px auto;padding:0 20px;width:100%;}
        .breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
        .breadcrumb a{color:#1E3A8A;text-decoration:none;}
        
        .page-header{margin-bottom:24px;}
        .page-header h2{font-size:24px;font-weight:700;color:#1E3A8A;}
        .page-header p{font-size:13px;color:#888;margin-top:4px;}

        .card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
        .card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;}
        .card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
        .card-body{padding:24px;}

        .form-group{margin-bottom:18px;}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#333;margin-bottom:6px;}
        .form-group input, .form-group textarea{
            width:100%; padding:10px 14px;
            border:1.5px solid #dde3f0; border-radius:8px;
            font-family:'Poppins',sans-serif; font-size:14px; color:#333;
        }
        
        .btn-submit{
            width:100%; padding:12px;
            background:#a855f7; color:white;
            border:none; border-radius:8px;
            font-family:'Poppins',sans-serif; font-size:14px; font-weight:600;
            cursor:pointer; transition:0.2s;
        }
        .btn-submit:hover{background:#9333ea;}

        .alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;}
        .alert-success{background:#dcfce7;color:#16a34a;}
        .alert-error{background:#fee2e2;color:#dc2626;}

        .status-box{display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:10px;}
        .status-pending{background:#fef9c3;color:#92400e;}
        .status-available{background:#dcfce7;color:#14532d;}
        .status-reject{background:#fee2e2;color:#7f1d1d;}

        .btn-back{display:inline-block;padding:9px 18px;background:white;color:#1E3A8A;border:1.5px solid #1E3A8A;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;}
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
    <div class="breadcrumb">
        <a href="clubHandler.php">Dashboard</a> &rsaquo; Create Club
    </div>

    <div class="page-header">
        <h2>Create a Club</h2>
        <p>Your club will be reviewed by administrators before going live.</p>
    </div>

    <?php if($message): ?>
    <div class="alert alert-<?= $messageType; ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3>Club Details</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label for="club_name">Club Name</label>
                    <input type="text" id="club_name" name="club_name" placeholder="e.g. Photography Club" required>
                </div>
                <div class="form-group">
                    <label for="club_description">Description</label>
                    <textarea id="club_description" name="club_description" rows="4" placeholder="What is the purpose of this club?" required></textarea>
                </div>
                <button class="btn-submit" type="submit" name="create">✨ Create Club</button>
            </form>
        </div>
    </div>

    <?php if($myClub):
        $statusMap = [
            "Pending"   => ["class"=>"status-pending",   "icon"=>"⏳"],
            "Available" => ["class"=>"status-available",  "icon"=>"✅"],
            "Reject"    => ["class"=>"status-reject",     "icon"=>"❌"],
            "Close"     => ["class"=>"status-close",      "icon"=>"🔒"],
        ];
        $s = $statusMap[$myClub['club_status']] ?? ["class"=>"status-pending","icon"=>"❓"];
    ?>
    <div class="card">
        <div class="card-header"><h3>Latest Club Status</h3></div>
        <div class="card-body">
            <div class="status-box <?= $s['class']; ?>">
                <div style="font-size:24px;"><?= $s['icon']; ?></div>
                <div>
                    <div style="font-weight:700;"><?= htmlspecialchars($myClub['club_name']) ?></div>
                    <div style="font-size:12px; opacity:0.8;">Status: <?= htmlspecialchars($myClub['club_status']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="action-bar">
        <a class="btn-back" href="clubHandler.php">← Back to Dashboard</a>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>