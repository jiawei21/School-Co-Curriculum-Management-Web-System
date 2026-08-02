<?php
session_start();
require("../database.php");

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = intval($_SESSION['userid']);
$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');


$userRes = $con->query("SELECT * FROM user WHERE id = $userid");
$user = $userRes->fetch_assoc();


$clubCount = $con->query("SELECT COUNT(*) FROM club_membership WHERE userid = $userid AND register_status = 'Approved'")->fetch_row()[0];
$meritRes = $con->query("SELECT SUM(hours) FROM merit WHERE userid = $userid AND status = 'approved'")->fetch_row()[0];
$totalMerit = round($meritRes ?? 0, 1);
$achCount = $con->query("SELECT COUNT(*) FROM achievement WHERE userid = $userid")->fetch_row()[0];

$myClubs = $con->query("
    SELECT c.club_name, cm.club_role 
    FROM club_membership cm 
    JOIN club c ON cm.clubid = c.club_id 
    WHERE cm.userid = $userid AND cm.register_status = 'Approved'
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;color:#333;min-height:100vh;display:flex;flex-direction:column;}
        
        
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

        
        .container{max-width:900px;margin:40px auto;padding:0 20px;flex:1;}

        .profile-card{background:white;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;margin-bottom:25px;}
        .profile-cover{height:120px;background:linear-gradient(135deg, #1E3A8A 0%, #3b82f6 100%);}
        .profile-content{padding:0 30px 30px;position:relative;text-align:center;}
        .avatar-wrapper{width:110px;height:110px;border-radius:50%;background:white;padding:5px;margin:-55px auto 15px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
        .avatar-circle-large{width:100%;height:100%;border-radius:50%;background:#e8edf8;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:700;color:#1E3A8A;text-transform:uppercase;}
        
        .user-info h2{font-size:24px;color:#1E3A8A;margin-bottom:5px;}
        .user-info p{font-size:14px;color:#888;margin-bottom:20px;}

        .stats-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:15px;margin-bottom:30px;}
        .stat-box{background:#f8faff;padding:15px;border-radius:12px;text-align:center;border:1px solid #eef2ff;}
        .stat-box .val{display:block;font-size:22px;font-weight:700;color:#1E3A8A;}
        .stat-box .lbl{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;}

        .section-title{font-size:16px;font-weight:700;color:#1E3A8A;margin-bottom:15px;display:flex;align-items:center;gap:10px;}
        .grid-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
        @media(max-width:700px){.grid-row{grid-template-columns:1fr;}}

        .info-box{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.04);}
        .list-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f5f5f5;}
        .list-item:last-child{border-bottom:none;}
        .list-item span.name{font-size:14px;font-weight:500;}
        .list-item span.role{font-size:11px;background:#e8edf8;color:#1E3A8A;padding:2px 8px;border-radius:10px;}

        .btn-edit{display:inline-block;margin-top:20px;padding:8px 20px;border:1.5px solid #1E3A8A;border-radius:8px;color:#1E3A8A;text-decoration:none;font-size:13px;font-weight:600;transition:0.2s;}
        .btn-edit:hover{background:#1E3A8A;color:white;}
        
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

<div class="container">
    <div class="profile-card">
        <div class="profile-cover"></div>
        <div class="profile-content">
            <div class="avatar-wrapper">
                <div class="avatar-circle-large"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            </div>
            <div class="user-info">
                <h2><?= htmlspecialchars($user['username']) ?></h2>
                <p>📧 <?= htmlspecialchars($user['email']) ?></p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <span class="val"><?= $clubCount ?></span>
                        <span class="lbl">Clubs Joined</span>
                    </div>
                    <div class="stat-box">
                        <span class="val"><?= $totalMerit ?>h</span>
                        <span class="lbl">Contribution</span>
                    </div>
                    <div class="stat-box">
                        <span class="val"><?= $achCount ?></span>
                        <span class="lbl">Achievements</span>
                    </div>
                </div>

                <a href="edit_profile.php" class="btn-edit">⚙️ Edit Profile</a>
            </div>
        </div>
    </div>

    <div class="grid-row">
        <div class="info-box">
            <h3 class="section-title">🏫 My Clubs</h3>
            <?php if($myClubs->num_rows > 0): ?>
                <?php while($c = $myClubs->fetch_assoc()): ?>
                <div class="list-item">
                    <span class="name"><?= htmlspecialchars($c['club_name']) ?></span>
                    <span class="role"><?= $c['club_role'] ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="font-size:13px; color:#aaa;">No clubs joined yet.</p>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <h3 class="section-title">🚀 Quick Access</h3>
            <div class="list-item">
                <a href="../merit/merit_dashboard.php" style="text-decoration:none; color:#333; font-size:14px;">View Merit Records</a>
                <span>→</span>
            </div>
            <div class="list-item">
                <a href="../achievement/achievement_dashboard.php" style="text-decoration:none; color:#333; font-size:14px;">View Certificates</a>
                <span>→</span>
            </div>
            <div class="list-item">
                <a href="../logout.php" style="text-decoration:none; color:#dc2626; font-size:14px; font-weight:600;">Logout</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>