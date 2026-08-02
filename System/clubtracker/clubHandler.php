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


$stmt = $con->prepare("
    SELECT c.club_id, c.club_name, c.club_status, cm.club_role, cm.register_status
    FROM club_membership cm
    JOIN club c ON cm.clubid = c.club_id
    WHERE cm.userid = ?
    ORDER BY c.club_CreateDate DESC
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$clubs = [];
while($row = $result->fetch_assoc()) $clubs[] = $row;


$myClub = null;
foreach($clubs as $club) {
    if($club['club_status'] === 'Pending') {
        $myClub = $club;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}

        
        header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
        .logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
        nav{display:flex;align-items:center;gap:20px;}
        nav a{color:white;text-decoration:none;font-size:14px;}
        nav a:hover{opacity:0.8;}

        .user-menu{display:flex;align-items:center;gap:10px;position:relative;}
        .avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}

        .dropdown{position:relative;display:inline-block;}
        .dropdown-content{display:none;position:absolute;right:0;top:35px;background-color:white;min-width:160px;box-shadow:0px 8px 16px rgba(0,0,0,0.2);z-index:1000;border-radius:8px;overflow:hidden;}
        .dropdown-content a{color:#333;padding:12px 16px;text-decoration:none;display:block;font-size:13px;text-align:left;}
        .dropdown-content a:hover{background-color:#f1f5f9;color:#1E3A8A;}
        .dropdown:hover .dropdown-content{display:block;}

        
        .page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
        .page-header{margin-bottom:28px;}
        .page-header h2{font-size:24px;font-weight:700;color:#1E3A8A;}
        .page-header p{font-size:13px;color:#888;margin-top:4px;}

        
        .grid{display:grid;grid-template-columns:2fr 1fr;gap:24px;}
        @media(max-width:900px){.grid{grid-template-columns:1fr;}}

        
        .card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:24px;}
        .card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
        .card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
        .club-count{font-size:12px;background:#e8edf8;color:#1E3A8A;padding:3px 10px;border-radius:20px;font-weight:500;}
        .card-body{padding:20px 24px;}

        
        .status-box{display:flex;align-items:center;gap:14px;padding:16px 20px;border-radius:10px;}
        .status-pending{background:#fef9c3;color:#92400e;}
        .status-available{background:#dcfce7;color:#14532d;}
        .status-reject{background:#fee2e2;color:#7f1d1d;}
        .status-close{background:#f3f4f6;color:#374151;}

        
        .club-list{list-style:none;padding:0;}
        .club-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f5f5f5;text-decoration:none;transition:background 0.15s;}
        .club-item:last-child{border-bottom:none;}
        .club-item:hover .club-item-name{color:#163070;text-decoration:underline;}

        .club-icon{width:40px;height:40px;border-radius:10px;background:#1E3A8A;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;}
        .club-item-info{flex:1;}
        .club-item-name{font-size:14px;font-weight:600;color:#1E3A8A;}
        .club-item-meta{font-size:12px;color:#888;margin-top:2px;}

        
        .badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
        .badge-approved{background:#dcfce7;color:#16a34a;}
        .badge-pending{background:#fef3c7;color:#D97706;}
        .badge-rejected{background:#fee2e2;color:#dc2626;}
        .badge-active, .badge-available{background:#e0f2fe;color:#0369a1;}
        .badge-reject{background:#fee2e2;color:#dc2626;}

        
        .action-box{display:flex;flex-direction:column;gap:20px;}
        .action-card{background:white;border-radius:12px;padding:25px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.07);}
        .action-icon{font-size:32px;margin-bottom:10px;}
        .btn-action{display:block;width:100%;padding:10px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:white;transition:0.2s;}
        .btn-join{background:#22c55e;}
        .btn-create{background:#a855f7;}

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
            <div class="avatar-circle"><?php echo strtoupper(mb_substr($_SESSION['username'], 0, 1)); ?></div>
            <a href="../user/userprofile.php"><span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span></a>
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
        <h2>Club Dashboard</h2>
        <p>Manage your memberships and explore new communities.</p>
    </div>

    <div class="grid">
        <div class="left-panel">

            <?php
            
            if($myClub):
                $statusMap = [
                    "Pending"   => ["class" => "status-pending",   "icon" => "⏳"],
                    "Available" => ["class" => "status-available",  "icon" => "✅"],
                    "Reject"    => ["class" => "status-reject",     "icon" => "❌"],
                    "Close"     => ["class" => "status-close",      "icon" => "🔒"],
                ];
                $s = $statusMap[$myClub['club_status']] ?? ["class" => "status-pending", "icon" => "❓"];
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

            <div class="card">
                <div class="card-header">
                    <h3>My Clubs</h3>
                    <span class="club-count"><?= count($clubs) ?> clubs</span>
                </div>
                <div class="card-body">
                    <?php if(empty($clubs)): ?>
                        <div style="text-align:center; padding:40px; color:#aaa;">
                            <p>You haven't joined any clubs yet.</p>
                        </div>
                    <?php else: ?>
                        <ul class="club-list">
                        <?php foreach($clubs as $row):
                            $initial = strtoupper(mb_substr($row['club_name'], 0, 1));
                            $isRejected = ($row['club_status'] === 'Reject');

                            $tag         = $isRejected ? 'div' : 'a';
                            $href        = $isRejected ? '' : 'href="club.php?id=' . $row['club_id'] . '"';
                            $disabledStyle = $isRejected ? 'style="opacity:0.5; cursor:not-allowed; background:#fafafa;"' : '';
                            $iconStyle   = $isRejected ? 'style="background:#94a3b8;"' : '';

                            // ── DYNAMIC BADGE LOGIC ──
                            // If user is Chairperson, show the Club's status as the main badge.
                            // If user is a normal member, show their Membership register status as the main badge.
                            if (strtolower($row['club_role']) === 'chairperson') {
                                $badgeStatus = strtolower($row['club_status']);
                                $badgeLabel  = htmlspecialchars($row['club_status']);
                                $metaText    = 'Membership: ' . htmlspecialchars($row['register_status']);
                            } else {
                                $badgeStatus = strtolower($row['register_status']);
                                $badgeLabel  = htmlspecialchars($row['register_status']);
                                $metaText    = 'Club: ' . htmlspecialchars($row['club_status']);
                            }
                        ?>
                            <li>
                                <<?= $tag ?> class="club-item" <?= $href ?> <?= $disabledStyle ?>>
                                    <div class="club-icon" <?= $iconStyle ?>><?= $initial ?></div>
                                    <div class="club-item-info">
                                        <div class="club-item-name" <?= $isRejected ? 'style="color:#666; text-decoration:none;"' : '' ?>>
                                            <?= htmlspecialchars($row['club_name']) ?>
                                        </div>
                                        <div class="club-item-meta">
                                            <?= htmlspecialchars($row['club_role']) ?>
                                            <?php if(!$isRejected): ?>
                                                · <?= $metaText ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if(!$isRejected): ?>
                                    <span class="badge badge-<?= $badgeStatus ?>">
                                        <?= $badgeLabel ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-reject">Rejected</span>
                                    <?php endif; ?>
                                </<?= $tag ?>>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="action-box">
            <div class="action-card">
                <div class="action-icon">🤝</div>
                <h4 style="margin-bottom:5px; color:#1E3A8A;">Join a Club</h4>
                <p style="font-size:12px; color:#888; margin-bottom:15px;">Discover and join existing student communities.</p>
                <a href="join_club.php" class="btn-action btn-join">Browse Clubs</a>
            </div>

            <div class="action-card">
                <div class="action-icon">✨</div>
                <h4 style="margin-bottom:5px; color:#1E3A8A;">Create a Club</h4>
                <p style="font-size:12px; color:#888; margin-bottom:15px;">Have a new idea? Start your own club today.</p>
                <a href="create_club.php" class="btn-action btn-create">Start a Club</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>