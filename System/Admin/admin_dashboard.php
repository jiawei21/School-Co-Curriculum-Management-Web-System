<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit();
}


$totalClubs    = $con->query("SELECT COUNT(*) FROM club")->fetch_row()[0];
$pendingClubs  = $con->query("SELECT COUNT(*) FROM club WHERE club_status='Pending'")->fetch_row()[0];
$totalEvents   = $con->query("SELECT COUNT(*) FROM event")->fetch_row()[0];
$pendingEvents = $con->query("SELECT COUNT(*) FROM event WHERE EventStatus='Pending'")->fetch_row()[0];
$pendingAchievements = $con->query("SELECT COUNT(*) FROM achievement WHERE award_status='Pending'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}

header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
.logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
nav{display:flex;align-items:center;gap:20px;}
nav a{color:white;text-decoration:none;font-size:14px;}
nav a:hover{opacity:0.8;}
.dropdown{position:relative;}
.dropdown-content{display:none;position:absolute;background:white;top:30px;border-radius:6px;box-shadow:0 5px 15px rgba(0,0,0,0.2);z-index:999;min-width:160px;}
.dropdown-content a{display:block;padding:10px 15px;color:#1E3A8A;font-size:13px;}
.dropdown-content a:hover{background:#f0f4ff;}
.dropdown:hover .dropdown-content{display:block;}
.user-menu{display:flex;align-items:center;gap:10px;}
.avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}


.page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
.page-header{margin-bottom:28px;}
.page-header h2{font-size:24px;font-weight:700;color:#1E3A8A;}
.page-header p{font-size:13px;color:#888;margin-top:4px;}


.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:36px;}
@media(max-width:800px){.stats-grid{grid-template-columns:1fr 1fr;}}
.stat-card{background:white;border-radius:12px;padding:20px 24px;box-shadow:0 4px 20px rgba(0,0,0,0.07);display:flex;align-items:center;gap:16px;}
.stat-icon{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.stat-icon.blue{background:#e8edf8;}
.stat-icon.yellow{background:#fef9c3;}
.stat-icon.green{background:#dcfce7;}
.stat-icon.red{background:#fee2e2;}
.stat-value{font-size:22px;font-weight:700;color:#1E3A8A;}
.stat-label{font-size:12px;color:#888;margin-top:2px;}


.section-title{font-size:15px;font-weight:600;color:#1E3A8A;margin-bottom:16px;}


.main-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
@media(max-width:700px){.main-grid{grid-template-columns:1fr;}}

.main-card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;text-decoration:none;display:flex;flex-direction:column;transition:transform 0.2s,box-shadow 0.2s;}
.main-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(0,0,0,0.12);}

.main-card-top{padding:32px 30px 24px;display:flex;align-items:center;gap:18px;}
.main-card-top.club-top{background:linear-gradient(135deg,#1E3A8A 0%,#2d55c8 100%);}
.main-card-top.event-top{background:linear-gradient(135deg,#7c3aed 0%,#a855f7 100%);}
.main-card-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;}
.main-card-title{font-size:20px;font-weight:700;color:white;}
.main-card-sub{font-size:13px;color:rgba(255,255,255,0.75);margin-top:4px;}

.main-card-body{padding:20px 30px 28px;display:flex;align-items:center;justify-content:space-between;flex:1;}
.main-card-stats{display:flex;gap:24px;}
.mini-stat-value{font-size:20px;font-weight:700;color:#1E3A8A;}
.mini-stat-label{font-size:11px;color:#888;margin-top:2px;}
.mini-stat.pending .mini-stat-value{color:#D97706;}

.main-card-arrow{font-size:22px;color:#ccc;}
.main-card:hover .main-card-arrow{color:#1E3A8A;}


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

    <div class="page-header">
        <h2>Admin Dashboard</h2>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">🏫</div>
            <div>
                <div class="stat-value"><?php echo $totalClubs; ?></div>
                <div class="stat-label">Total Clubs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div>
                <div class="stat-value"><?php echo $pendingClubs; ?></div>
                <div class="stat-label">Clubs Pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📅</div>
            <div>
                <div class="stat-value"><?php echo $totalEvents; ?></div>
                <div class="stat-label">Total Events</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⏳</div>
            <div>
                <div class="stat-value"><?php echo $pendingEvents; ?></div>
                <div class="stat-label">Events Pending</div>
            </div>
        </div>

        <div class="stat-card">
    <div class="stat-icon red">🏆</div>
    <div>
        <div class="stat-value"><?php echo $pendingAchievements; ?></div>
        <div class="stat-label">Awards Pending</div>
    </div>
</div>
    </div>

    
    <div class="section-title">Manage</div>
    <div class="main-grid">

        <!-- CLUBS -->
        <a class="main-card" href="admin_club.php">
            <div class="main-card-top club-top">
                <div class="main-card-icon">🏫</div>
                <div>
                    <div class="main-card-title">Manage Clubs</div>
                    <div class="main-card-sub">Approve, reject, edit and delete clubs</div>
                </div>
            </div>
            <div class="main-card-body">
                <div class="main-card-stats">
                    <div class="mini-stat">
                        <div class="mini-stat-value"><?php echo $totalClubs; ?></div>
                        <div class="mini-stat-label">Total</div>
                    </div>
                    <div class="mini-stat pending">
                        <div class="mini-stat-value"><?php echo $pendingClubs; ?></div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                </div>
                <div class="main-card-arrow">→</div>
            </div>
        </a>

        
        <a class="main-card" href="admin_event.php">
            <div class="main-card-top event-top">
                <div class="main-card-icon">📅</div>
                <div>
                    <div class="main-card-title">Manage Events</div>
                    <div class="main-card-sub">Approve, reject, edit and delete events</div>
                </div>
            </div>
            <div class="main-card-body">
                <div class="main-card-stats">
                    <div class="mini-stat">
                        <div class="mini-stat-value"><?php echo $totalEvents; ?></div>
                        <div class="mini-stat-label">Total</div>
                    </div>
                    <div class="mini-stat pending">
                        <div class="mini-stat-value"><?php echo $pendingEvents; ?></div>
                        <div class="mini-stat-label">Pending</div>
                    </div>
                </div>
                <div class="main-card-arrow">→</div>
            </div>
        </a>

        <a class="main-card" href="admin_achievement.php">
    <div class="main-card-top" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
        <div class="main-card-icon">🏆</div>
        <div>
            <div class="main-card-title">Approve Achievements</div>
            <div class="main-card-sub">Review and approve external awards</div>
        </div>
    </div>
    <div class="main-card-body">
        <div class="main-card-stats">
            <div class="mini-stat pending">
                <div class="mini-stat-value"><?php echo $pendingAchievements; ?></div>
                <div class="mini-stat-label">Pending Approval</div>
            </div>
        </div>
        <div class="main-card-arrow">→</div>
    </div>
</a>

  <a class="main-card" href="admin_user.php">
    <div class="main-card-top" style="background: linear-gradient(135deg, #f5260b 0%, #d97706 100%);">
        <div class="main-card-icon">🏆</div>
        <div>
            <div class="main-card-title">Manage User</div>
            <div class="main-card-sub">Review Student Information</div>
        </div>
    </div>
    <div class="main-card-body">
        <div class="main-card-stats">
            <div class="mini-stat pending">
                <div class="mini-stat-label">Students</div>
            </div>
        </div>
        <div class="main-card-arrow">→</div>
    </div>
</a>

    </div>
</div>


<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>