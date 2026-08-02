<?php
session_start();
require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid  = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? '';
$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');

if($isAdmin){
    $events = $con->query("
        SELECT e.*,
               u.username AS creator,
               COUNT(p.id) AS participant_count
        FROM event e
        LEFT JOIN user u ON e.UserId = u.id
        LEFT JOIN event_participant p ON p.eventid = e.EventId
        GROUP BY e.EventId
        ORDER BY e.EventDate DESC
    ");
} else {
    $stmt = $con->prepare("
        SELECT e.*,
               u.username AS creator,
               COUNT(p.id) AS participant_count
        FROM event e
        LEFT JOIN user u ON e.UserId = u.id
        LEFT JOIN event_participant p ON p.eventid = e.EventId
        WHERE e.UserId = ?
        GROUP BY e.EventId
        ORDER BY e.EventDate DESC
    ");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $events = $stmt->get_result();
}

$eventRows = [];
while($r = $events->fetch_assoc()) $eventRows[] = $r;

$viewEventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$viewEvent   = null;
$participants = [];

if($viewEventId > 0){
    if($isAdmin){
        $stmt = $con->prepare("SELECT * FROM event WHERE EventId=?");
        $stmt->bind_param("i", $viewEventId);
    } else {
        $stmt = $con->prepare("SELECT * FROM event WHERE EventId=? AND UserId=?");
        $stmt->bind_param("ii", $viewEventId, $userid);
    }
    $stmt->execute();
    $viewEvent = $stmt->get_result()->fetch_assoc();

    if($viewEvent){
        $pStmt = $con->prepare("
            SELECT u.username, u.email, p.join_date
            FROM event_participant p
            JOIN user u ON u.id = p.userid
            WHERE p.eventid = ?
            ORDER BY p.join_date ASC
        ");
        $pStmt->bind_param("i", $viewEventId);
        $pStmt->execute();
        $pResult = $pStmt->get_result();
        while($pr = $pResult->fetch_assoc()) $participants[] = $pr;
    }
}

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
<title>My Events – Uni Event Tracker</title>
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
.page-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.page-title h2{font-size:26px;font-weight:700;color:#1E3A8A;}


.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;border-left:4px solid #1E3A8A;}
.stat-card .val{font-size:30px;font-weight:700;color:#1E3A8A;}
.stat-card .lbl{font-size:12px;color:#888;margin-top:4px;}
.stat-card.c{border-left-color:var(--c);}
.stat-card.c .val{color:var(--c);font-size:22px;}


.card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:28px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:15px;font-weight:700;color:#1E3A8A;}
table{width:100%;border-collapse:collapse;}
thead{background:#1E3A8A;color:white;}
thead th{padding:13px 16px;text-align:left;font-size:13px;font-weight:600;}
tbody tr{border-bottom:1px solid #f1f5f9;transition:background 0.15s;}
tbody tr:hover{background:#f8faff;}
tbody td{padding:12px 16px;font-size:13px;color:#333;vertical-align:middle;}
tbody tr:last-child{border-bottom:none;}

.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-rejected{background:#fee2e2;color:#dc2626;}


.btn-participants{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;background:#e8edf8;color:#1E3A8A;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;transition:background 0.2s;border:none;cursor:pointer;font-family:'Poppins',sans-serif;}
.btn-participants:hover{background:#dbeafe;}
.btn-participants .count{background:#1E3A8A;color:white;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;}


.participant-panel{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;}
.panel-header{padding:18px 24px;background:#1E3A8A;color:white;display:flex;align-items:center;justify-content:space-between;}
.panel-header h3{font-size:15px;font-weight:700;}
.panel-header .close-btn{background:rgba(255,255,255,0.2);border:none;color:white;width:28px;height:28px;border-radius:50%;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;}
.panel-header .close-btn:hover{background:rgba(255,255,255,0.3);}
.panel-event-info{padding:16px 24px;background:#f8faff;border-bottom:1px solid #e8edf8;display:flex;gap:20px;flex-wrap:wrap;}
.panel-event-info span{font-size:13px;color:#555;}
.panel-event-info strong{color:#1E3A8A;}

.participant-table{width:100%;border-collapse:collapse;}
.participant-table thead{background:#f1f5f9;}
.participant-table thead th{padding:11px 20px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.05em;}
.participant-table tbody tr{border-bottom:1px solid #f8f8f8;transition:background 0.15s;}
.participant-table tbody tr:hover{background:#f8faff;}
.participant-table tbody td{padding:12px 20px;font-size:13px;color:#333;}
.participant-table tbody tr:last-child{border-bottom:none;}

.avatar-sm{width:32px;height:32px;border-radius:50%;background:#dbeafe;color:#1E3A8A;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;margin-right:8px;flex-shrink:0;}
.name-cell{display:flex;align-items:center;}

.empty-participants{text-align:center;padding:40px 20px;color:#aaa;}
.empty-participants .icon{font-size:36px;margin-bottom:10px;}


.type-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;}

footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}

@media(max-width:700px){
    header{padding:12px 20px;}
    thead th:nth-child(3),tbody td:nth-child(3){display:none;}
}
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
        <a href="../index.php">Home</a> ›
        <a href="event_dashboard.php">Events</a> ›
        My Events
    </div>

    <div class="page-title">
        <h2>📋 My Created Events</h2>
        <a href="eventHandler.php" style="background:#1E3A8A;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">➕ Create Event</a>
    </div>

    <?php
    $totalEvents = count($eventRows);
    $totalParticipants = array_sum(array_column($eventRows, 'participant_count'));
    $statusCount = ['Pending'=>0,'Approved'=>0,'Rejected'=>0];
    foreach($eventRows as $r) {
        if(isset($statusCount[$r['EventStatus']])) $statusCount[$r['EventStatus']]++;
    }
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="val"><?= $totalEvents ?></div>
            <div class="lbl">Total Events</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= $totalParticipants ?></div>
            <div class="lbl">Total Participants</div>
        </div>
        <div class="stat-card c" style="--c:#f59e0b">
            <div class="val"><?= $statusCount['Pending'] ?></div>
            <div class="lbl">Pending</div>
        </div>
        <div class="stat-card c" style="--c:#22c55e">
            <div class="val"><?= $statusCount['Approved'] ?></div>
            <div class="lbl">Approved</div>
        </div>
        <div class="stat-card c" style="--c:#ef4444">
            <div class="val"><?= $statusCount['Rejected'] ?></div>
            <div class="lbl">Rejected</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>📅 Events You Created</h3>
            <span style="font-size:12px;color:#888;"><?= $totalEvents ?> event<?= $totalEvents!=1?'s':'' ?></span>
        </div>
        <?php if(empty($eventRows)): ?>
        <div style="text-align:center;padding:50px;color:#aaa;">
            <div style="font-size:40px;margin-bottom:12px">📭</div>
            <p style="font-size:14px">You haven't created any events yet.<br>
            <a href="eventHandler.php" style="color:#1E3A8A;font-weight:600">Create your first event!</a></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event Name</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Participants</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($eventRows as $i => $row):
                $cat = $catConfig[$row['EventType']] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
                $isActive = ($viewEventId === intval($row['EventId']));
            ?>
            <tr style="<?= $isActive ? 'background:#f0f4ff;' : '' ?>">
                <td><?= $i+1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($row['EventName'],ENT_QUOTES,'UTF-8') ?></strong>
                    <div style="font-size:11px;color:#aaa;margin-top:2px">
                        📍 Block <?= htmlspecialchars($row['EventBlock'],ENT_QUOTES,'UTF-8') ?> – <?= htmlspecialchars($row['EventHall'],ENT_QUOTES,'UTF-8') ?>
                    </div>
                </td>
                <td>
                    <span class="type-badge" style="background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>">
                        <?= $cat['icon'] ?> <?= htmlspecialchars($row['EventType'],ENT_QUOTES,'UTF-8') ?>
                    </span>
                </td>
                <td>
                    <?= date('d M Y', strtotime($row['EventDate'])) ?>
                    <div style="font-size:11px;color:#aaa">
                        <?= date('h:i A', strtotime($row['EventStartTime'])) ?> – <?= date('h:i A', strtotime($row['EventEndTime'])) ?>
                    </div>
                </td>
                <td><span class="badge badge-<?= strtolower($row['EventStatus']) ?>"><?= $row['EventStatus'] ?></span></td>
                <td>
                    <a class="btn-participants" href="my_events.php?event_id=<?= $row['EventId'] ?>#participants">
                        👥 View
                        <span class="count"><?= $row['participant_count'] ?></span>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if($viewEvent): ?>
    <div class="participant-panel" id="participants">
        <div class="panel-header">
            <h3>👥 Participants — <?= htmlspecialchars($viewEvent['EventName'],ENT_QUOTES,'UTF-8') ?></h3>
            <a href="my_events.php" class="close-btn">✕</a>
        </div>
        <div class="panel-event-info">
            <span>📅 <strong><?= date('d M Y', strtotime($viewEvent['EventDate'])) ?></strong></span>
            <span>🕐 <strong><?= date('h:i A', strtotime($viewEvent['EventStartTime'])) ?> – <?= date('h:i A', strtotime($viewEvent['EventEndTime'])) ?></strong></span>
            <span>📍 <strong>Block <?= htmlspecialchars($viewEvent['EventBlock'],ENT_QUOTES,'UTF-8') ?> – <?= htmlspecialchars($viewEvent['EventHall'],ENT_QUOTES,'UTF-8') ?></strong></span>
            <span>👥 <strong><?= count($participants) ?> participant<?= count($participants)!=1?'s':'' ?></strong></span>
        </div>

        <?php if(empty($participants)): ?>
        <div class="empty-participants">
            <div class="icon">🙁</div>
            <p style="font-size:14px">No one has joined this event yet.</p>
        </div>
        <?php else: ?>
        <table class="participant-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined At</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($participants as $j => $p): ?>
            <tr>
                <td><?= $j+1 ?></td>
                <td>
                    <div class="name-cell">
                        <div class="avatar-sm"><?= strtoupper(mb_substr($p['username'],0,1)) ?></div>
                        <strong><?= htmlspecialchars($p['username'],ENT_QUOTES,'UTF-8') ?></strong>
                    </div>
                </td>
                <td><?= htmlspecialchars($p['email'],ENT_QUOTES,'UTF-8') ?></td>
                <td style="color:#888"><?= date('d M Y, h:i A', strtotime($p['join_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
<?php if($viewEventId > 0): ?>
window.addEventListener('load', () => {
    const el = document.getElementById('participants');
    if(el) el.scrollIntoView({behavior:'smooth', block:'start'});
});
<?php endif; ?>
</script>
</body>
</html>