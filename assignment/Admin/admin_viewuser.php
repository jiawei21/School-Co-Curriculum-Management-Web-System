<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php"); exit();
}

$viewId = intval($_GET['id'] ?? 0);
if(!$viewId){ header("Location: admin_users.php"); exit(); }


$stmt = $con->prepare("SELECT * FROM user WHERE id=? AND role='student'");
$stmt->bind_param("i", $viewId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if(!$user){ header("Location: admin_users.php"); exit(); }


$clubs = $con->query("
    SELECT c.club_name, c.club_status, cm.club_role AS member_role, cm.register_status
    FROM club_membership cm
    JOIN club c ON c.club_id = cm.clubid
    WHERE cm.userid = $viewId
    ORDER BY c.club_CreateDate DESC
");
$clubRows = [];
while($r = $clubs->fetch_assoc()) $clubRows[] = $r;


$merits = $con->query("
    SELECT m.*, e.EventType
    FROM merit m
    LEFT JOIN event e ON e.EventId = m.event_id
    WHERE m.userid = $viewId
    ORDER BY m.merit_date DESC
");
$meritRows = [];
$totalHours = 0;
while($r = $merits->fetch_assoc()){
    $totalHours += $r['hours'];
    $meritRows[] = $r;
}
$totalHours = round($totalHours, 2);


$meritStats = $con->query("
    SELECT e.EventType AS category, SUM(m.hours) AS hours, COUNT(*) AS cnt
    FROM merit m
    LEFT JOIN event e ON e.EventId = m.event_id
    WHERE m.userid = $viewId
    GROUP BY e.EventType
");
$meritByCategory = [];
while($r = $meritStats->fetch_assoc()) $meritByCategory[$r['category']] = $r;


$achievements = $con->query("
    SELECT a.*, e.EventName
    FROM achievement a
    LEFT JOIN event e ON e.EventId = a.event_id
    WHERE a.userid = $viewId
    ORDER BY a.issued_date DESC
");
$achRows = [];
while($r = $achievements->fetch_assoc()) $achRows[] = $r;


$events = $con->query("
    SELECT e.EventName, e.EventType, e.EventDate, e.EventStatus, p.join_date
    FROM event_participant p
    JOIN event e ON e.EventId = p.eventid
    WHERE p.userid = $viewId
    ORDER BY e.EventDate DESC
");
$eventRows = [];
while($r = $events->fetch_assoc()) $eventRows[] = $r;

$catConfig = [
    'Competition'       => ['icon'=>'🏆','color'=>'#ef4444','bg'=>'#fee2e2'],
    'Event'             => ['icon'=>'🎉','color'=>'#3b82f6','bg'=>'#dbeafe'],
    'Service/Volunteer' => ['icon'=>'🤝','color'=>'#22c55e','bg'=>'#dcfce7'],
    'Workshop'          => ['icon'=>'🛠️','color'=>'#f59e0b','bg'=>'#fef9c3'],
    'Sport'             => ['icon'=>'⚽','color'=>'#a855f7','bg'=>'#f3e8ff'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View User – <?= htmlspecialchars($user['username'],ENT_QUOTES,'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}
header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
.logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
nav{display:flex;align-items:center;gap:20px;}
nav a{color:white;text-decoration:none;font-size:14px;}
nav a:hover{opacity:0.8;}
.user-menu{display:flex;align-items:center;gap:10px;}
.avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}
.page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:16px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}


.profile-header{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;}
.profile-avatar{width:72px;height:72px;border-radius:50%;background:#1E3A8A;color:white;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;flex-shrink:0;}
.profile-info{flex:1;}
.profile-name{font-size:22px;font-weight:700;color:#1a1a2e;}
.profile-email{font-size:13px;color:#888;margin-top:3px;}
.profile-meta{font-size:12px;color:#aaa;margin-top:5px;}
.profile-stats{display:flex;gap:20px;flex-wrap:wrap;margin-left:auto;}
.pstat{text-align:center;padding:12px 18px;background:#f8faff;border-radius:10px;}
.pstat .val{font-size:22px;font-weight:700;color:#1E3A8A;}
.pstat .lbl{font-size:11px;color:#888;margin-top:2px;}


.tabs{display:flex;gap:4px;margin-bottom:20px;background:white;border-radius:10px;padding:5px;box-shadow:0 2px 12px rgba(0,0,0,0.06);width:fit-content;flex-wrap:wrap;}
.tab{padding:8px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;color:#888;border:none;background:none;font-family:'Poppins',sans-serif;transition:all 0.2s;}
.tab.active{background:#1E3A8A;color:white;}
.tab:hover:not(.active){background:#f0f4ff;color:#1E3A8A;}


.section{display:none;}
.section.active{display:block;}


.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:14px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:14px;font-weight:700;color:#1E3A8A;}
.card-header .sub{font-size:12px;color:#888;}
table{width:100%;border-collapse:collapse;}
th{padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #f0f0f0;white-space:nowrap;}
td{padding:11px 16px;font-size:13px;color:#333;border-bottom:1px solid #f9f9f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafbff;}


.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-available,.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-reject,.badge-rejected{background:#fee2e2;color:#dc2626;}
.badge-ended{background:#e2e8f0;color:#64748b;}
.badge-cert{background:#fef9c3;color:#92400e;}
.badge-award{background:#fee2e2;color:#dc2626;}


.empty-box{text-align:center;padding:40px;color:#aaa;}
.empty-box .icon{font-size:36px;margin-bottom:10px;}
.empty-box p{font-size:13px;}


.cat-pills{display:flex;gap:10px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid #f0f0f0;}
.cat-pill{display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;font-size:12px;font-weight:600;background:var(--bg);color:var(--c);}
.cat-pill .hrs{font-size:16px;font-weight:700;}

.btn-back{display:inline-block;padding:9px 18px;background:white;color:#1E3A8A;border:1.5px solid #1E3A8A;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;transition:all 0.2s;}
.btn-back:hover{background:#1E3A8A;color:white;}
footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}
@media(max-width:700px){.profile-header{flex-direction:column;align-items:flex-start;}.profile-stats{margin-left:0;}}
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
        <a href="admin_dashboard.php">Admin Dashboard</a> ›
        <a href="admin_users.php">Manage Users</a> ›
        <?= htmlspecialchars($user['username'],ENT_QUOTES,'UTF-8') ?>
    </div>

    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="profile-avatar"><?= strtoupper(mb_substr($user['username'],0,1)) ?></div>
        <div class="profile-info">
            <div class="profile-name"><?= htmlspecialchars($user['username'],ENT_QUOTES,'UTF-8') ?></div>
            <div class="profile-email">✉️ <?= htmlspecialchars($user['email'],ENT_QUOTES,'UTF-8') ?></div>
            <div class="profile-meta">📅 Registered: <?= date('d M Y', strtotime($user['register_date'])) ?></div>
        </div>
        <div class="profile-stats">
            <div class="pstat"><div class="val"><?= count($clubRows) ?></div><div class="lbl">Clubs</div></div>
            <div class="pstat"><div class="val"><?= count($eventRows) ?></div><div class="lbl">Events</div></div>
            <div class="pstat"><div class="val"><?= $totalHours ?>h</div><div class="lbl">Merit Hours</div></div>
            <div class="pstat"><div class="val"><?= count($achRows) ?></div><div class="lbl">Achievements</div></div>
        </div>
    </div>

   
    <div class="tabs">
        <button class="tab active" onclick="switchTab('clubs',this)">🏫 Clubs (<?= count($clubRows) ?>)</button>
        <button class="tab" onclick="switchTab('events',this)">📅 Events (<?= count($eventRows) ?>)</button>
        <button class="tab" onclick="switchTab('merit',this)">⭐ Merit (<?= $totalHours ?>h)</button>
        <button class="tab" onclick="switchTab('achievement',this)">🏆 Achievements (<?= count($achRows) ?>)</button>
    </div>

  
    <div class="section active" id="tab-clubs">
        <div class="card">
            <div class="card-header">
                <h3>🏫 Club Memberships</h3>
                <span class="sub"><?= count($clubRows) ?> club<?= count($clubRows)!=1?'s':'' ?></span>
            </div>
            <?php if(empty($clubRows)): ?>
            <div class="empty-box"><div class="icon">🏫</div><p>Not a member of any club.</p></div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Club Name</th><th>Position / Role</th><th>Membership Status</th><th>Club Status</th></tr></thead>
                <tbody>
                <?php foreach($clubRows as $i => $r): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($r['club_name'],ENT_QUOTES,'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($r['member_role'] ?? 'Member',ENT_QUOTES,'UTF-8') ?></td>
                    <td>
                        <?php
                        $rs = strtolower($r['register_status'] ?? '');
                        $rsClass = $rs==='approved'?'badge-approved':($rs==='pending'?'badge-pending':'badge-rejected');
                        ?>
                        <span class="badge <?= $rsClass ?>"><?= htmlspecialchars($r['register_status'],ENT_QUOTES,'UTF-8') ?></span>
                    </td>
                    <td><span class="badge badge-<?= strtolower($r['club_status']) ?>"><?= $r['club_status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="section" id="tab-events">
        <div class="card">
            <div class="card-header">
                <h3>📅 Events Joined</h3>
                <span class="sub"><?= count($eventRows) ?> event<?= count($eventRows)!=1?'s':'' ?></span>
            </div>
            <?php if(empty($eventRows)): ?>
            <div class="empty-box"><div class="icon">📅</div><p>No events joined yet.</p></div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Event Name</th><th>Type</th><th>Date</th><th>Status</th><th>Joined At</th></tr></thead>
                <tbody>
                <?php foreach($eventRows as $i => $r):
                    $cat = $catConfig[$r['EventType']] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($r['EventName'],ENT_QUOTES,'UTF-8') ?></strong></td>
                    <td>
                        <span class="badge" style="background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>">
                            <?= $cat['icon'] ?> <?= htmlspecialchars($r['EventType'],ENT_QUOTES,'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($r['EventDate'])) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['EventStatus']) ?>"><?= $r['EventStatus'] ?></span></td>
                    <td style="color:#888"><?= date('d M Y', strtotime($r['join_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="section" id="tab-merit">
        <div class="card">
            <div class="card-header">
                <h3>⭐ Merit Records</h3>
                <span class="sub">Total: <strong><?= $totalHours ?>h</strong></span>
            </div>
            <!-- Category summary -->
            <?php if(!empty($meritByCategory)): ?>
            <div class="cat-pills">
                <?php foreach($meritByCategory as $cat => $data):
                    $cfg = $catConfig[$cat] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
                ?>
                <div class="cat-pill" style="--c:<?= $cfg['color'] ?>;--bg:<?= $cfg['bg'] ?>">
                    <?= $cfg['icon'] ?> <?= $cat ?>
                    <span class="hrs"><?= round($data['hours'],1) ?>h</span>
                    <span style="font-size:10px;opacity:0.7"><?= $data['cnt'] ?> records</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if(empty($meritRows)): ?>
            <div class="empty-box"><div class="icon">⭐</div><p>No merit records yet.</p></div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Activity</th><th>Category</th><th>Date</th><th>Hours</th></tr></thead>
                <tbody>
                <?php foreach($meritRows as $i => $r):
                    $cat = $r['EventType'] ?? '—';
                    $cfg = $catConfig[$cat] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
                ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($r['activity_name'],ENT_QUOTES,'UTF-8') ?></strong></td>
                    <td>
                        <span class="badge" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>">
                            <?= $cfg['icon'] ?> <?= htmlspecialchars($cat,ENT_QUOTES,'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($r['merit_date'])) ?></td>
                    <td><strong style="color:#1E3A8A"><?= $r['hours'] ?>h</strong></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="section" id="tab-achievement">
        <div class="card">
            <div class="card-header">
                <h3>🏆 Achievements</h3>
                <span class="sub"><?= count($achRows) ?> record<?= count($achRows)!=1?'s':'' ?></span>
            </div>
            <?php if(empty($achRows)): ?>
            <div class="empty-box"><div class="icon">🏆</div><p>No achievements yet.</p></div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Title</th><th>Type</th><th>Event</th><th>Award Level</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($achRows as $i => $r): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($r['title'],ENT_QUOTES,'UTF-8') ?></strong></td>
                    <td>
                        <span class="badge badge-<?= strtolower($r['type']) ?>">
                            <?= $r['type']==='Certificate'?'📜':'🏆' ?> <?= $r['type'] ?>
                        </span>
                    </td>
                    <td style="color:#888"><?= htmlspecialchars($r['EventName'] ?? '—',ENT_QUOTES,'UTF-8') ?></td>
                    <td>
                        <?php if($r['award_level']): ?>
                        <span class="badge" style="background:#f3e8ff;color:#7c3aed"><?= htmlspecialchars($r['award_level'],ENT_QUOTES,'UTF-8') ?></span>
                        <?php else: ?>
                        <span style="color:#aaa;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $as = $r['award_status'];
                        $asClass = ['None'=>'badge-ended','Pending'=>'badge-pending','Approved'=>'badge-approved','Rejected'=>'badge-rejected'][$as] ?? '';
                        $asLabel = ['None'=>'—','Pending'=>'⏳ Pending','Approved'=>'✅ Approved','Rejected'=>'❌ Rejected'][$as] ?? $as;
                        ?>
                        <?php if($as !== 'None'): ?>
                        <span class="badge <?= $asClass ?>"><?= $asLabel ?></span>
                        <?php else: ?><span style="color:#aaa;font-size:12px">—</span><?php endif; ?>
                    </td>
                    <td><?= date('d M Y', strtotime($r['issued_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <a class="btn-back" href="admin_users.php">← Back to Users</a>
</div>

<footer>© 2026 Uni Event Tracker</footer>
<script>
function switchTab(id, btn){
    document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    btn.classList.add('active');
}
</script>
</body>
</html>