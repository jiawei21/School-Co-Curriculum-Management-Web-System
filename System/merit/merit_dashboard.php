<?php
session_start();
require("../database.php");

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? '';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');


$con->query("
    UPDATE event
    SET EventStatus = 'Ended'
    WHERE EventStatus = 'Approved'
      AND (
          EventDate < CURDATE()
          OR (EventDate = CURDATE() AND EventEndTime <= CURTIME())
      )
");


if (isset($_POST['delete'])) {
    $mid = intval($_POST['merit_id']);
    $con->query("DELETE FROM merit WHERE merit_id=$mid AND userid=$userid");
    header("Location: merit_dashboard.php?deleted=1");
    exit();
}

$flashMsg  = '';
$flashType = '';
if (isset($_GET['deleted'])) { $flashMsg = '🗑️ Record deleted.'; $flashType = 'danger'; }

// ── Totals (only approved hours count) ───────────────────────────────────────
$totRow     = $con->query("SELECT SUM(hours) AS total_hours FROM merit WHERE userid=$userid AND status='approved'")->fetch_assoc();
$totalHours = round($totRow['total_hours'] ?? 0, 2);


$records = $con->query("
    SELECT
        m.merit_id,
        m.activity_name,
        m.hours,
        m.merit_date,
        m.event_id,
        m.status,
        m.merit_description,
        m.graded_at,
        e.EventType,
        e.EventStartTime,
        e.EventEndTime,
        grader.username AS graded_by_name
    FROM merit m
    LEFT JOIN event e ON e.EventId = m.event_id
    LEFT JOIN user grader ON grader.id = m.graded_by
    WHERE m.userid = $userid
    ORDER BY m.merit_date DESC
");

$catConfig = [
    'Competition'       => ['border'=>'#ef4444','bg'=>'#fee2e2','emoji'=>'🏆'],
    'Event'             => ['border'=>'#3b82f6','bg'=>'#dbeafe','emoji'=>'🎉'],
    'Service/Volunteer' => ['border'=>'#22c55e','bg'=>'#dcfce7','emoji'=>'🤝'],
    'Workshop'          => ['border'=>'#f59e0b','bg'=>'#fef9c3','emoji'=>'🛠️'],
    'Sport'             => ['border'=>'#a855f7','bg'=>'#f3e8ff','emoji'=>'⚽'],
];

$catTotals = [];
foreach ($catConfig as $cat => $_) $catTotals[$cat] = ['hours'=>0,'count'=>0];

$allRows = [];
while ($row = $records->fetch_assoc()) {
    $cat = $row['EventType'] ?? 'Event';
    // Only count approved hours in category totals
    if ($row['status'] === 'approved' && isset($catTotals[$cat])) {
        $catTotals[$cat]['hours'] += $row['hours'];
        $catTotals[$cat]['count']++;
    }
    $allRows[] = $row;
}


$myEndedEvents = [];
$evRes = $con->query("
    SELECT EventId, EventName, EventType
    FROM event
    WHERE (UserId = $userid " . ($isAdmin ? "OR 1=1" : "") . ")
      AND EventStatus = 'Ended'
    ORDER BY EventDate DESC
    LIMIT 30
");
if ($evRes) while ($ev = $evRes->fetch_assoc()) $myEndedEvents[$ev['EventId']] = $ev;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Merit Tracker – Uni Event Tracker</title>
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
.page-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
.page-title h2{font-size:26px;font-weight:700;color:#1E3A8A;}

.flash{padding:12px 18px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;}
.flash.danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}


.organiser-panel{background:#fff;border-radius:14px;padding:20px 24px;margin-bottom:28px;box-shadow:0 4px 20px rgba(0,0,0,.07);border-left:4px solid #1E3A8A;}
.organiser-panel h3{font-size:14px;font-weight:700;color:#1E3A8A;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.event-grade-list{display:flex;flex-direction:column;gap:10px;}
.event-grade-item{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:10px;background:#f8faff;border:1px solid #e2e8f0;}
.event-grade-item .ev-name{font-size:13px;font-weight:600;color:#1a1a2e;}
.event-grade-item .ev-type{font-size:11px;color:#888;margin-top:2px;}
.btn-grade{padding:7px 18px;background:#1E3A8A;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;text-decoration:none;display:inline-block;}
.btn-grade:hover{background:#162d6e;}

.summary-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:32px;}
.sum-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #1E3A8A;text-align:center;}
.sum-card .val{font-size:30px;font-weight:700;color:#1E3A8A;}
.sum-card .lbl{font-size:12px;color:#888;margin-top:4px;}
.sum-card.cat{border-left-color:var(--c);}
.sum-card.cat .val{color:var(--c);font-size:22px;}
.sum-card.cat .sub{font-size:11px;color:#aaa;margin-top:4px;}

.table-card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;}
.table-card-header{padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.table-card-header h3{font-size:15px;font-weight:700;color:#1E3A8A;}
table{width:100%;border-collapse:collapse;}
thead{background:#1E3A8A;color:white;}
thead th{padding:13px 16px;text-align:left;font-size:13px;font-weight:600;}
tbody tr{border-bottom:1px solid #f1f5f9;transition:background 0.15s;}
tbody tr:hover{background:#f8faff;}
tbody td{padding:12px 16px;font-size:13px;color:#333;vertical-align:middle;}
tbody tr:last-child{border-bottom:none;}

.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-cat{background:var(--bg);color:var(--c);}
.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-rejected{background:#fee2e2;color:#dc2626;}
.badge-pending{background:#fef9c3;color:#d97706;}

.tooltip-wrap{position:relative;cursor:help;}
.tooltip-wrap .tip{
    display:none;position:absolute;bottom:130%;left:50%;transform:translateX(-50%);
    background:#1a1a2e;color:#fff;font-size:11px;padding:6px 10px;border-radius:7px;
    white-space:nowrap;z-index:9;box-shadow:0 4px 12px rgba(0,0,0,.2);
}
.tooltip-wrap:hover .tip{display:block;}

.btn-delete{padding:5px 12px;background:#fee2e2;color:#dc2626;border-radius:6px;font-size:12px;font-weight:600;border:none;cursor:pointer;font-family:'Poppins',sans-serif;transition:background 0.2s;}
.btn-delete:hover{background:#fecaca;}
.btn-delete:disabled{opacity:.4;cursor:not-allowed;}

.empty-state{text-align:center;padding:60px 20px;color:#aaa;}
.empty-state .icon{font-size:52px;margin-bottom:14px;}
.empty-state p{font-size:14px;line-height:1.8;}
.empty-state a{color:#1E3A8A;font-weight:600;}

footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}

@media(max-width:768px){
    header{padding:12px 20px;}
    .summary-row{grid-template-columns:repeat(2,1fr);}
    thead th:nth-child(4),tbody td:nth-child(4),
    thead th:nth-child(6),tbody td:nth-child(6){display:none;}
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

    <div class="breadcrumb"><a href="../index.php">Home</a> › Merit Tracker</div>
    <div class="page-title"><h2>⭐ My Contribution Hours</h2></div>

    <?php if ($flashMsg): ?>
    <div class="flash danger"><?= $flashMsg ?></div>
    <?php endif; ?>

    
    <?php if (!empty($myEndedEvents)): ?>
    <div class="organiser-panel">
        <h3>🎓 Grade Participants <span style="font-size:11px;background:#e8edf8;color:#1E3A8A;padding:2px 8px;border-radius:10px;font-weight:600">Organiser / Admin</span></h3>
        <div class="event-grade-list">
            <?php foreach ($myEndedEvents as $ev): ?>
            <div class="event-grade-item">
                <div>
                    <div class="ev-name"><?= htmlspecialchars($ev['EventName'],ENT_QUOTES,'UTF-8') ?></div>
                    <div class="ev-type"><?= htmlspecialchars($ev['EventType'],ENT_QUOTES,'UTF-8') ?></div>
                </div>
                <a href="meritHandler.php?event_id=<?= $ev['EventId'] ?>" class="btn-grade">✏️ Grade Merit</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="summary-row">
        <div class="sum-card">
            <div class="val"><?= $totalHours ?></div>
            <div class="lbl">Approved Hours</div>
        </div>
        <?php foreach ($catConfig as $cat => $meta):
            $h = round($catTotals[$cat]['hours'], 2);
            $n = $catTotals[$cat]['count'];
        ?>
        <div class="sum-card cat" style="--c:<?= $meta['border'] ?>">
            <div class="val"><?= $meta['emoji'] ?> <?= $n ?></div>
            <div class="lbl"><?= $cat ?></div>
            <div class="sub"><?= $h ?> hrs</div>
        </div>
        <?php endforeach; ?>
    </div>

    
    <div class="table-card">
        <div class="table-card-header">
            <h3>📋 Merit Records</h3>
            <span style="font-size:12px;color:#888"><?= count($allRows) ?> record<?= count($allRows)!=1?'s':'' ?></span>
        </div>
        <?php if (empty($allRows)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No merit records yet.<br>
               <a href="../Event/event_dashboard.php">Join events</a> to earn contribution hours!</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Activity</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Hours</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allRows as $i => $row):
                $cat    = $row['EventType'] ?? 'Event';
                $meta   = $catConfig[$cat] ?? ['border'=>'#94a3b8','bg'=>'#f1f5f9','emoji'=>'📌'];
                $status = $row['status'] ?? 'approved'; // legacy rows default approved
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($row['activity_name'],ENT_QUOTES,'UTF-8') ?></strong></td>
                <td>
                    <span class="badge badge-cat" style="--bg:<?= $meta['bg'] ?>;--c:<?= $meta['border'] ?>;background:<?= $meta['bg'] ?>;color:<?= $meta['border'] ?>">
                        <?= $meta['emoji'] ?> <?= htmlspecialchars($cat,ENT_QUOTES,'UTF-8') ?>
                    </span>
                </td>
                <td><?= date('d M Y', strtotime($row['merit_date'])) ?></td>
                <td>
                    <?php if ($status === 'approved'): ?>
                    <strong style="color:#1E3A8A"><?= $row['hours'] ?>h</strong>
                    <?php elseif ($status === 'rejected'): ?>
                    <span style="color:#ccc;text-decoration:line-through">0h</span>
                    <?php else: ?>
                    <span style="color:#d97706">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status === 'approved'): ?>
                    <span class="badge badge-approved">✅ Approved</span>
                    <?php elseif ($status === 'rejected'): ?>
                    <span class="badge badge-rejected">🚫 Rejected</span>
                    <?php else: ?>
                    <span class="badge badge-pending">⏳ Pending</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['merit_description'])): ?>
                    <div class="tooltip-wrap">
                        <span style="font-size:12px;color:#888;cursor:help">💬 <?= mb_strimwidth(htmlspecialchars($row['merit_description'],ENT_QUOTES,'UTF-8'), 0, 28, '…') ?></span>
                        <div class="tip"><?= htmlspecialchars($row['merit_description'],ENT_QUOTES,'UTF-8') ?></div>
                    </div>
                    <?php else: ?>
                    <span style="color:#ddd;font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($status !== 'rejected'): ?>
                    <form method="POST" onsubmit="return confirm('Delete this record?')">
                        <input type="hidden" name="merit_id" value="<?= $row['merit_id'] ?>">
                        <button class="btn-delete" name="delete">Delete</button>
                    </form>
                    <?php else: ?>
                    <span style="color:#ccc;font-size:12px">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>