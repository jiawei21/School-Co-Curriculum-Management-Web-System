<?php
session_start();
require('../database.php');
require('auto_achievement.php'); 

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? '';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');


$newAchievements = autoGenerateAchievement($con, $userid);


if (isset($_POST['update_notes'])) {
    $aid   = intval($_POST['achievement_id']);
    $notes = $con->real_escape_string(trim($_POST['notes']));
    $con->query("UPDATE achievement SET notes='$notes' WHERE achievement_id=$aid AND userid=$userid");
    header("Location: achievement_dashboard.php?updated=1");
    exit();
}


if (isset($_POST['delete'])) {
    $aid = intval($_POST['achievement_id']);
    $con->query("DELETE FROM achievement WHERE achievement_id=$aid AND userid=$userid AND award_status != 'Approved'");
    header("Location: achievement_dashboard.php?deleted=1");
    exit();
}


$records = $con->query("
    SELECT a.*, e.EventName 
    FROM achievement a
    LEFT JOIN event e ON e.EventId = a.event_id
    WHERE a.userid = $userid
    ORDER BY a.issued_date DESC
");

$allRows = [];
$totalCerts = 0; $totalAwards = 0;
while ($r = $records->fetch_assoc()) {
    if ($r['type'] === 'Certificate') $totalCerts++;
    if ($r['type'] === 'Award' || ($r['type'] === 'Certificate' && $r['award_status'] === 'Approved')) $totalAwards++;
    $allRows[] = $r;
}

$typeConfig = [
    'Certificate' => ['icon'=>'📜','color'=>'#f59e0b','bg'=>'#fef9c3'],
    'Award'       => ['icon'=>'🏆','color'=>'#ef4444','bg'=>'#fee2e2'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Achievements - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;color:#333;display:flex;flex-direction:column;min-height:100vh;}
        
        /* ── NAVBAR (Matched with Index) ── */
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

        
        .page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
        .page-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
        .page-title h2{font-size:26px;font-weight:700;color:#1E3A8A;}
        
        .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:28px;}
        .stat-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;border-left:4px solid var(--c,#1E3A8A);}
        .stat-card .val{font-size:30px;font-weight:700;color:var(--c,#1E3A8A);}
        .stat-card .lbl{font-size:12px;color:#888;margin-top:4px;}

        .achievement-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;}
        .ach-card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;border-top:4px solid var(--c);display:flex;flex-direction:column;}
        
        .ach-card-header{padding:18px 20px 12px;display:flex;align-items:center;gap:12px;}
        .ach-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;background:var(--bg);}
        .ach-title{font-size:14px;font-weight:700;color:#1a1a2e;line-height:1.2;}
        .ach-type{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--c);margin-bottom:2px;}

        .ach-card-body{padding:0 20px 16px;flex:1;}
        .ach-meta{font-size:12px;color:#666;line-height:1.6;margin-bottom:10px;}
        
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;margin-top:5px;}
        .badge-pending{background:#fef3c7;color:#D97706;}
        .badge-approved{background:#dcfce7;color:#16a34a;}
        .badge-rejected{background:#fee2e2;color:#dc2626;}

        .btn-view{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:6px 12px;background:#eef2ff;color:#1E3A8A;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;}
        
        .note-area{margin-top:12px;padding-top:12px;border-top:1px dashed #eee;display:flex;gap:5px;}
        .note-input{flex:1;border:1px solid #ddd;padding:5px 8px;border-radius:6px;font-size:12px;font-family:inherit;}

        .ach-card-footer{padding:12px 20px;background:#fcfcfc;border-top:1px solid #f1f1f1;display:flex;justify-content:flex-end;align-items:center;}
        .btn-delete{background:none;border:none;color:#dc2626;font-size:12px;font-weight:600;cursor:pointer;padding:5px;}

        .btn-add{background:#1E3A8A;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;}
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
    <div class="page-title">
        <h2>🏆 My Achievements</h2>
        <a href="achievement_upload.php" class="btn-add">+ Add External Award</a>
    </div>

    <div class="stats-row">
        <div class="stat-card" style="--c:#1E3A8A"><div class="val"><?= count($allRows) ?></div><div class="lbl">Total Achievements</div></div>
        <div class="stat-card" style="--c:#f59e0b"><div class="val">📜 <?= $totalCerts ?></div><div class="lbl">Certificates</div></div>
        <div class="stat-card" style="--c:#ef4444"><div class="val">🏆 <?= $totalAwards ?></div><div class="lbl">Awards</div></div>
    </div>

    <?php if (empty($allRows)): ?>
        <div style="text-align:center; padding:60px; background:white; border-radius:15px; color:#888;">No records found. Start joining events!</div>
    <?php else: ?>
        <div class="achievement-grid">
            <?php foreach ($allRows as $row): 
                $tc = $typeConfig[$row['type']] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
            ?>
            <div class="ach-card" style="--c:<?= $tc['color'] ?>;--bg:<?= $tc['bg'] ?>">
                <div class="ach-card-header">
                    <div class="ach-icon"><?= $tc['icon'] ?></div>
                    <div>
                        <div class="ach-type"><?= $row['type'] ?></div>
                        <div class="ach-title"><?= htmlspecialchars($row['title']) ?></div>
                    </div>
                </div>
                
                <div class="ach-card-body">
                    <div class="ach-meta">
                        📅 <strong>Date:</strong> <?= date('d M Y', strtotime($row['issued_date'])) ?><br>
                        <?php if($row['EventName']): ?>🎪 <strong>Event:</strong> <?= htmlspecialchars($row['EventName']) ?><?php endif; ?>
                    </div>

                    <?php if($row['award_status'] !== 'None'): ?>
                        <span class="badge badge-<?= strtolower($row['award_status']) ?>">
                            <?= $row['award_status'] ?> <?= $row['award_level'] ? "· ".$row['award_level'] : "" ?>
                        </span>
                    <?php endif; ?>

                    <?php if(!empty($row['file_path'])): ?>
                        <br><a href="../uploads/certificates/<?= $row['file_path'] ?>" target="_blank" class="btn-view">📄 View Certificate</a>
                    <?php endif; ?>

                    <form method="POST" class="note-area">
                        <input type="hidden" name="achievement_id" value="<?= $row['achievement_id'] ?>">
                        <input type="text" name="notes" class="note-input" value="<?= htmlspecialchars($row['notes'] ?? '') ?>" placeholder="Personal note...">
                        <button name="update_notes" style="background:none; border:none; color:#1E3A8A; font-weight:600; font-size:11px; cursor:pointer;">Save</button>
                    </form>
                </div>

                <div class="ach-card-footer">
                    <?php if($row['award_status'] !== 'Approved'): ?>
                        <form method="POST" onsubmit="return confirm('Delete this record?')">
                            <input type="hidden" name="achievement_id" value="<?= $row['achievement_id'] ?>">
                            <button name="delete" class="btn-delete">🗑️ Delete</button>
                        </form>
                    <?php else: ?>
                        <span style="font-size:11px; color:#16a34a; font-weight:600;">🔒 Verified Record</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>© 2026 Uni Event Tracker</footer>
</body>
</html>