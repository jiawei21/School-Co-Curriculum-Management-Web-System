<?php
session_start();
require('database.php');


if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'User';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');


$clubCount = $con->query("SELECT COUNT(*) FROM club_membership WHERE userid = $userid AND register_status = 'Approved'")->fetch_row()[0];

$meritRes = $con->query("SELECT SUM(hours) FROM merit WHERE userid = $userid AND status = 'approved'")->fetch_row()[0];
$totalMerit = round($meritRes ?? 0, 1);

$achCount = $con->query("SELECT COUNT(*) FROM achievement WHERE userid = $userid")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;color:#333;display:flex;flex-direction:column;min-height:100vh;}
        
        
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
        .dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; }
        .dropdown-content a:hover { background-color: #f1f5f9; color: #1E3A8A; }
        .dropdown:hover .dropdown-content { display: block; }

        
        .hero{background:linear-gradient(135deg, #1E3A8A 0%, #3b82f6 100%); color:white; padding:60px 40px; text-align:center;}
        .hero h1{font-size:32px; font-weight:700; margin-bottom:10px;}
        .hero p{font-size:16px; opacity:0.9;}

       
        .page-wrapper{flex:1; max-width:1100px; margin: -40px auto 40px; padding:0 20px; width:100%;}

      
        .stats-row{display:grid;grid-template-columns:repeat(3, 1fr);gap:20px;margin-bottom:30px;}
        .stat-card{background:white;border-radius:15px;padding:25px;box-shadow:0 10px 30px rgba(0,0,0,0.08);text-align:center;transition: transform 0.3s;}
        .stat-card:hover{transform: translateY(-5px);}
        .stat-card .val{display:block; font-size:32px; font-weight:700; color:#1E3A8A;}
        .stat-card .lbl{font-size:13px; color:#888; margin-top:5px; font-weight:500;}

      
        .menu-grid{display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px;}
        .menu-card{background:white; border-radius:15px; padding:30px; text-decoration:none; color:inherit; box-shadow:0 4px 15px rgba(0,0,0,0.05); display:flex; flex-direction:column; align-items:center; transition:0.2s;}
        .menu-card:hover{background:#f8faff; border:1.5px solid #1E3A8A;}
        .menu-icon{font-size:40px; margin-bottom:15px;}
        .menu-card h3{font-size:18px; color:#1E3A8A; margin-bottom:8px;}
        .menu-card p{font-size:13px; color:#777; text-align:center;}

        footer{text-align:center; padding:20px; background:#1E3A8A; color:white; font-size:13px; margin-top:auto;}
        
        @media(max-width:700px){
            .stats-row{grid-template-columns: 1fr;}
            header{padding:15px 20px;}
            nav a:not(.user-menu a){display:none;} 
        }
    </style>
</head>
<body>

<header>
    <div class="logo"><a href="../index.php">Uni Event Tracker</a></div>
    <nav>
        <a href="Event/event_dashboard.php">Event</a>
        <a href="clubtracker/clubHandler.php">Club</a>
        <a href="merit/merit_dashboard.php">Merit</a>
        <a href="achievement/achievement_dashboard.php">Achievement</a>
         <?php if(isset($_SESSION['username'])){ ?>
        <div class="user-menu">
            <div class="avatar-circle" <a href="user/userprofile.php"><?php echo strtoupper(mb_substr($_SESSION['username'], 0, 1)); ?></div>
            <a href="user/userprofile.php"><span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span><a>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){ ?>
            <a href="Admin/admin_dashboard.php">Admin Panel</a>
            <?php } ?>
            <a href="logout.php">Logout</a>
        </div>
        <?php } else { ?>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
        <?php } ?>
    </nav>
</header>

<div class="hero">
    <h1>Welcome back, <?= htmlspecialchars($username) ?>!</h1>
    <p>Track your university life, contributions, and honors all in one place.</p>
</div>

<div class="page-wrapper">
    <div class="stats-row">
        <div class="stat-card">
            <span class="val"><?= $clubCount ?></span>
            <span class="lbl">Clubs Joined</span>
        </div>
        <div class="stat-card">
            <span class="val"><?= $totalMerit ?>h</span>
            <span class="lbl">Merit Hours</span>
        </div>
        <div class="stat-card">
            <span class="val"><?= $achCount ?></span>
            <span class="lbl">Achievements</span>
        </div>
    </div>

    <div class="menu-grid">
        <a href="Event/event_dashboard.php" class="menu-card">
            <div class="menu-icon">📅</div>
            <h3>Events</h3>
            <p>Explore and join upcoming university events.</p>
        </a>
        <a href="clubtracker/clubHandler.php" class="menu-card">
            <div class="menu-icon">🏫</div>
            <h3>Clubs</h3>
            <p>Manage your club memberships and roles.</p>
        </a>
        <a href="merit/merit_dashboard.php" class="menu-card">
            <div class="menu-icon">⭐</div>
            <h3>Merit Records</h3>
            <p>Track your contribution and volunteer hours.</p>
        </a>
        <a href="achievement/achievement_dashboard.php" class="menu-card">
            <div class="menu-icon">🏆</div>
            <h3>Achievements</h3>
            <p>View your certificates and awards.</p>
        </a>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>