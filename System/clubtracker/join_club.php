<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'User';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');
$message = "";
$messageType = "";


if(isset($_POST['join'])){
    $clubid = intval($_POST['clubid']);

    $check = $con->prepare("SELECT register_status FROM club_membership WHERE userid=? AND clubid=?");
    $check->bind_param("ii",$userid,$clubid);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $rowCheck = $res->fetch_assoc();
        if($rowCheck['register_status'] == 'Pending'){
            $message = "Your request is still pending.";
            $messageType = "warning";
        } elseif($rowCheck['register_status'] == 'Approved'){
            $message = "You are already a member of this club.";
            $messageType = "warning";
        } else {
            $date = date("Y-m-d H:i:s");
            $stmt = $con->prepare("UPDATE club_membership SET register_status='Pending', club_joinDate=? WHERE userid=? AND clubid=?");
            $stmt->bind_param("sii",$date,$userid,$clubid);
            $stmt->execute();
            $message = "Re-applied successfully! Waiting for approval.";
            $messageType = "success";
        }
    } else {
        $date = date("Y-m-d H:i:s");
        $stmt = $con->prepare("INSERT INTO club_membership (userid,clubid,club_role,club_joinDate,register_status) VALUES (?,?,'Member',?,'Pending')");
        $stmt->bind_param("iis",$userid,$clubid,$date);
        if($stmt->execute()){
            $message = "Request sent! Waiting for approval.";
            $messageType = "success";
        } else {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
    }
    // 注意：由于是同一页面，我们可以通过 Session 传递消息或直接显示
}


$clubs = $con->prepare("
SELECT c.club_id, c.club_name, c.club_description, c.club_CreateDate, cm.register_status
FROM club c
LEFT JOIN club_membership cm ON c.club_id = cm.clubid AND cm.userid = ?
WHERE c.club_status='Available'
ORDER BY c.club_CreateDate DESC
");
$clubs->bind_param("i",$userid);
$clubs->execute();
$result = $clubs->get_result();
$rows = [];
while($row = $result->fetch_assoc()) $rows[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join a Club - Uni Event Tracker</title>
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

        
        .page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
        .breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
        .breadcrumb a{color:#1E3A8A;text-decoration:none;}
        
        .page-header{margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
        .page-header h2{font-size:24px;font-weight:700;color:#1E3A8A;}
        .club-count{font-size:12px;background:#e8edf8;color:#1E3A8A;padding:4px 12px;border-radius:20px;font-weight:500;}

       
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;}
        .alert-success{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;}
        .alert-warning{background:#fef9c3;color:#92400e;border:1px solid #fde68a;}
        .alert-error{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;}

        
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;}
        .club-card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;display:flex;flex-direction:column;transition:0.2s;}
        .club-card:hover{transform:translateY(-4px);}

        .club-card-top{background:#1E3A8A;padding:20px;display:flex;align-items:center;gap:14px;}
        .club-initial{width:45px;height:45px;border-radius:10px;background:rgba(255,255,255,0.15);color:white;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0;}
        .club-card-name{font-size:15px;font-weight:700;color:white;line-height:1.2;}
        .club-card-date{font-size:11px;color:rgba(255,255,255,0.6);margin-top:2px;}

        .club-card-body{padding:20px;flex:1;}
        .club-card-desc{font-size:13px;color:#555;line-height:1.6;}
        .club-card-footer{padding:15px 20px;border-top:1px solid #f0f0f0;}

        .btn-status{display:block;width:100%;padding:10px;text-align:center;border:none;border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;}
        .btn-join{background:#22c55e;color:white;cursor:pointer;}
        .btn-join:hover{background:#16a34a;}
        .btn-pending{background:#fef3c7;color:#92400e;cursor:default;}
        .btn-joined{background:#dcfce7;color:#16a34a;cursor:default;}

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
        <a href="clubHandler.php">Dashboard</a> &rsaquo; Join a Club
    </div>

    <div class="page-header">
        <div>
            <h2>Join a Club</h2>
            <p>Find your interest and grow together.</p>
        </div>
        <span class="club-count"><?= count($rows) ?> clubs available</span>
    </div>

    <?php if($message): ?>
    <div class="alert alert-<?= $messageType; ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <div class="grid">
    <?php if(empty($rows)): ?>
        <div style="text-align:center; padding:50px; background:white; border-radius:15px; grid-column:1/-1;">
            <p style="color:#888;">No clubs available to join right now.</p>
        </div>
    <?php else: ?>
        <?php foreach($rows as $row):
            $status  = $row['register_status'] ?? "";
            $initial = strtoupper(mb_substr($row['club_name'], 0, 1));
            $dateFormatted = date("d M Y", strtotime($row['club_CreateDate']));
        ?>
        <div class="club-card">
            <div class="club-card-top">
                <div class="club-initial"><?= $initial ?></div>
                <div>
                    <div class="club-card-name"><?= htmlspecialchars($row['club_name']) ?></div>
                    <div class="club-card-date">Created <?= $dateFormatted ?></div>
                </div>
            </div>

            <div class="club-card-body">
                <p class="club-card-desc"><?= htmlspecialchars($row['club_description']) ?></p>
            </div>

            <div class="club-card-footer">
                <?php if($status == "Pending"): ?>
                    <button class="btn-status btn-pending" disabled>⏳ Pending Approval</button>
                <?php elseif($status == "Approved"): ?>
                    <button class="btn-status btn-joined" disabled>✅ Already Joined</button>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="clubid" value="<?= $row['club_id']; ?>">
                        <button class="btn-status btn-join" name="join">Join Club</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <div class="action-bar">
        <a class="btn-back" href="clubHandler.php">← Back to Dashboard</a>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>