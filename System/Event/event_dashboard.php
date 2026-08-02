<?php
session_start();
require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events - Uni Event Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="design.css">
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
.avatar{width:35px;height:35px;border-radius:50%;}


.page-wrapper{flex:1;max-width:1000px;margin:40px auto;padding:0 20px;width:100%;}
.page-header{margin-bottom:36px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.page-header h2{font-size:24px;font-weight:700;color:#1E3A8A;}
.page-header p{font-size:13px;color:#888;margin-top:4px;}
.btn-create{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:#1E3A8A;color:white;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:background 0.2s;white-space:nowrap;}
.btn-create:hover{background:#163070;}


.category-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:700px){.category-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:480px){.category-grid{grid-template-columns:1fr;}}

.cat-card{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:14px;padding:36px 20px;
    background:white;border-radius:14px;
    box-shadow:0 4px 20px rgba(0,0,0,0.07);
    text-decoration:none;
    transition:transform 0.2s, box-shadow 0.2s;
    border-top:4px solid transparent;
}
.cat-card:hover{transform:translateY(-5px);box-shadow:0 12px 32px rgba(0,0,0,0.13);}

.cat-card.competition{border-top-color:#ef4444;}
.cat-card.event      {border-top-color:#3b82f6;}
.cat-card.service    {border-top-color:#22c55e;}
.cat-card.workshop   {border-top-color:#f59e0b;}
.cat-card.sport      {border-top-color:#a855f7;}

.cat-icon{
    width:64px;height:64px;border-radius:16px;
    display:flex;align-items:center;justify-content:center;
    font-size:30px;flex-shrink:0;
}
.competition .cat-icon{background:#fee2e2;}
.event       .cat-icon{background:#dbeafe;}
.service     .cat-icon{background:#dcfce7;}
.workshop    .cat-icon{background:#fef9c3;}
.sport       .cat-icon{background:#f3e8ff;}

.cat-name{font-size:16px;font-weight:700;color:#1a1a2e;text-align:center;}
.cat-desc{font-size:12px;color:#aaa;text-align:center;line-height:1.5;}


footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}
</style>
</head>
<body>

<!-- NAVBAR -->
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
        <div>
            <h2>Events</h2>
            <p>Select a category to browse events.</p>
        </div>
        <a class="btn-create" href="eventHandler.php">➕ Create Event</a>
    </div>

    <div class="category-grid">

        <a class="cat-card competition" href="event_search.php?type=Competition">
            <div class="cat-icon">🏆</div>
            <div class="cat-name">Competition</div>
            <div class="cat-desc">Compete and challenge yourself</div>
        </a>

        <a class="cat-card event" href="event_search.php?type=Event">
            <div class="cat-icon">🎉</div>
            <div class="cat-name">Event</div>
            <div class="cat-desc">General events and gatherings</div>
        </a>

        <a class="cat-card service" href="event_search.php?type=Service%2FVolunteer">
            <div class="cat-icon">🤝</div>
            <div class="cat-name">Service / Volunteer</div>
            <div class="cat-desc">Give back to the community</div>
        </a>

        <a class="cat-card workshop" href="event_search.php?type=Workshop">
            <div class="cat-icon">🛠️</div>
            <div class="cat-name">Workshop</div>
            <div class="cat-desc">Learn new skills and knowledge</div>
        </a>

        <a class="cat-card sport" href="event_search.php?type=Sport">
            <div class="cat-icon">⚽</div>
            <div class="cat-name">Sport</div>
            <div class="cat-desc">Sports and physical activities</div>
        </a>

        <a class="cat-card workshop" href="joined_event.php?type=Workshop">
            <div class="cat-icon">🎟️</div>
            <div class="cat-name">Joined event</div>
            <div class="cat-desc">event join</div>
        </a>

        <a class="cat-card sport" href="my_events.php?type=My_Events">
            <div class="cat-icon">📋</div>
            <div class="cat-name">My Events</div>
            <div class="cat-desc">created and details events</div>
        </a>



    </div>
</div>


<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>