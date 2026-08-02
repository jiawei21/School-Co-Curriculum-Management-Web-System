<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? '';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');


$stmt = $con->prepare("
    SELECT
        e.EventId,
        e.EventName,
        e.EventType,
        e.EventInfo,
        e.EventDate,
        e.EventStartTime,
        e.EventEndTime,
        e.EventBlock,
        e.EventHall,
        e.EventImage,
        e.EventStatus,
        p.join_date,
        TIMESTAMPDIFF(MINUTE, e.EventStartTime, e.EventEndTime) AS duration_mins
    FROM event_participant p
    JOIN event e ON e.EventId = p.eventid
    WHERE p.userid = ?
    ORDER BY e.EventDate DESC
");
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();

$upcoming = [];
$past     = [];
$now = time();

while($r = $result->fetch_assoc()){
    $eventEnd = strtotime($r['EventDate'] . ' ' . $r['EventEndTime']);
    if($eventEnd > $now){
        $upcoming[] = $r;
    } else {
        $past[] = $r;
    }
}


usort($upcoming, fn($a,$b) => strcmp($a['EventDate'], $b['EventDate']));

usort($past, fn($a,$b) => strcmp($b['EventDate'], $a['EventDate']));

$catConfig = [
    'Competition'       => ['icon'=>'🏆','color'=>'#ef4444','bg'=>'#fee2e2'],
    'Event'             => ['icon'=>'🎉','color'=>'#3b82f6','bg'=>'#dbeafe'],
    'Service/Volunteer' => ['icon'=>'🤝','color'=>'#22c55e','bg'=>'#dcfce7'],
    'Workshop'          => ['icon'=>'🛠️','color'=>'#f59e0b','bg'=>'#fef9c3'],
    'Sport'             => ['icon'=>'⚽','color'=>'#a855f7','bg'=>'#f3e8ff'],
];

function formatDuration($mins){
    if($mins <= 0) return '—';
    $h = floor($mins / 60);
    $m = $mins % 60;
    return $h > 0 ? "{$h}h" . ($m > 0 ? " {$m}m" : '') : "{$m}m";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Joined Events – Uni Event Tracker</title>
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
.page-title{margin-bottom:28px;}
.page-title h2{font-size:26px;font-weight:700;color:#1E3A8A;}
.page-title p{font-size:13px;color:#888;margin-top:5px;}


.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:32px;}
.stat-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);text-align:center;border-left:4px solid var(--c,#1E3A8A);}
.stat-card .val{font-size:30px;font-weight:700;color:var(--c,#1E3A8A);}
.stat-card .lbl{font-size:12px;color:#888;margin-top:4px;}


.tabs{display:flex;gap:4px;margin-bottom:24px;background:white;border-radius:10px;padding:5px;box-shadow:0 2px 12px rgba(0,0,0,0.06);width:fit-content;}
.tab{padding:8px 24px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;color:#888;transition:all 0.2s;border:none;background:none;font-family:'Poppins',sans-serif;}
.tab.active{background:#1E3A8A;color:white;}
.tab:hover:not(.active){background:#f0f4ff;color:#1E3A8A;}


.section{display:none;}
.section.active{display:block;}
.section-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
.section-header h3{font-size:17px;font-weight:700;color:#1E3A8A;}
.section-count{background:#e8edf8;color:#1E3A8A;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;}


.event-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;}


.event-card{background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;display:flex;flex-direction:column;transition:transform 0.2s,box-shadow 0.2s;}
.event-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,0.11);}

.event-card-img{width:100%;height:140px;display:flex;align-items:center;justify-content:center;font-size:44px;flex-shrink:0;}
.event-card-img img{width:100%;height:100%;object-fit:cover;}

.event-card-body{padding:16px 18px;flex:1;display:flex;flex-direction:column;gap:6px;}

.type-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;align-self:flex-start;}

.event-name{font-size:15px;font-weight:700;color:#1a1a2e;line-height:1.3;}
.event-desc{font-size:12px;color:#888;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

.meta-row{display:flex;align-items:center;gap:5px;font-size:12px;color:#555;}

.event-card-footer{padding:12px 18px;border-top:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:8px;}


.countdown{font-size:12px;font-weight:600;color:#1E3A8A;background:#e8edf8;padding:4px 10px;border-radius:20px;}
.countdown.soon{background:#fef9c3;color:#92400e;}
.countdown.today{background:#dcfce7;color:#16a34a;}


.event-card.past .event-card-img{filter:grayscale(40%);}
.hours-earned{font-size:12px;font-weight:600;color:#16a34a;background:#dcfce7;padding:4px 10px;border-radius:20px;}


.empty-state{text-align:center;padding:60px 20px;color:#aaa;background:white;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.07);}
.empty-state .icon{font-size:48px;margin-bottom:14px;}
.empty-state p{font-size:14px;line-height:1.8;}
.empty-state a{color:#1E3A8A;font-weight:600;}

footer{text-align:center;padding:20px;background:#1E3A8A;color:white;font-size:13px;margin-top:auto;}

@media(max-width:600px){
    header{padding:12px 20px;}
    .event-grid{grid-template-columns:1fr;}
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
        My Joined Events
    </div>

    <div class="page-title">
        <h2>🎟️ My Joined Events</h2>
        <p>Track your upcoming and past event participation.</p>
    </div>

    <?php
    $totalHours = 0;
    foreach($past as $r) $totalHours += round($r['duration_mins'] / 60, 2);
    ?>
    <div class="stats-row">
        <div class="stat-card" style="--c:#1E3A8A">
            <div class="val"><?= count($upcoming) + count($past) ?></div>
            <div class="lbl">Total Joined</div>
        </div>
        <div class="stat-card" style="--c:#22c55e">
            <div class="val"><?= count($upcoming) ?></div>
            <div class="lbl">Upcoming</div>
        </div>
        <div class="stat-card" style="--c:#888">
            <div class="val"><?= count($past) ?></div>
            <div class="lbl">Past</div>
        </div>
        <div class="stat-card" style="--c:#f59e0b">
            <div class="val"><?= $totalHours ?>h</div>
            <div class="lbl">Hours Earned</div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="switchTab('upcoming', this)">
            🗓️ Upcoming <span style="margin-left:4px;background:#1E3A8A;color:white;border-radius:20px;padding:1px 7px;font-size:11px"><?= count($upcoming) ?></span>
        </button>
        <button class="tab" onclick="switchTab('past', this)">
            📁 Past <span style="margin-left:4px;background:#e8edf8;color:#555;border-radius:20px;padding:1px 7px;font-size:11px"><?= count($past) ?></span>
        </button>
    </div>

    <div class="section active" id="tab-upcoming">
        <div class="section-header">
            <h3>🗓️ Upcoming Events</h3>
            <span class="section-count"><?= count($upcoming) ?></span>
        </div>

        <?php if(empty($upcoming)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>You have no upcoming events.<br>
               <a href="event_dashboard.php">Browse events to join!</a></p>
        </div>
        <?php else: ?>
        <div class="event-grid">
        <?php foreach($upcoming as $r):
            $cat      = $catConfig[$r['EventType']] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
           $daysLeft = (int)(($eventEnd - $now) / 86400);
            if($daysLeft === 0)      { $cdClass='today';  $cdText='Today!'; }
            elseif($daysLeft === 1)  { $cdClass='soon';   $cdText='Tomorrow'; }
            elseif($daysLeft <= 7)  { $cdClass='soon';   $cdText="In {$daysLeft} days"; }
            else                    { $cdClass='';        $cdText="In {$daysLeft} days"; }
        ?>
        <div class="event-card">
            <div class="event-card-img" style="background:<?= $cat['bg'] ?>">
                <?php if(!empty($r['EventImage'])): ?>
                <img src="<?= htmlspecialchars($r['EventImage'],ENT_QUOTES,'UTF-8') ?>" alt="event">
                <?php else: ?><?= $cat['icon'] ?><?php endif; ?>
            </div>
            <div class="event-card-body">
                <span class="type-badge" style="background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>">
                    <?= $cat['icon'] ?> <?= htmlspecialchars($r['EventType'],ENT_QUOTES,'UTF-8') ?>
                </span>
                <div class="event-name"><?= htmlspecialchars($r['EventName'],ENT_QUOTES,'UTF-8') ?></div>
                <div class="event-desc"><?= htmlspecialchars($r['EventInfo'],ENT_QUOTES,'UTF-8') ?></div>
                <div class="meta-row">📅 <?= date('d M Y', strtotime($r['EventDate'])) ?></div>
                <div class="meta-row">🕐 <?= date('h:i A', strtotime($r['EventStartTime'])) ?> – <?= date('h:i A', strtotime($r['EventEndTime'])) ?></div>
                <div class="meta-row">📍 Block <?= htmlspecialchars($r['EventBlock'],ENT_QUOTES,'UTF-8') ?> – <?= htmlspecialchars($r['EventHall'],ENT_QUOTES,'UTF-8') ?></div>
                <div class="meta-row">⏱️ Duration: <?= formatDuration($r['duration_mins']) ?></div>
            </div>
            <div class="event-card-footer">
                <span class="countdown <?= $cdClass ?>"><?= $cdText ?></span>

                <form method="POST" action="event_join.php" onsubmit="return confirm('Leave this event?')">
                    <input type="hidden" name="event_id" value="<?= $r['EventId'] ?>">
                    <input type="hidden" name="action"   value="unjoin">
                    <input type="hidden" name="redirect" value="my_joined_events.php">
                    <button type="submit" style="padding:5px 12px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">Leave</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="section" id="tab-past">
        <div class="section-header">
            <h3>📁 Past Events</h3>
            <span class="section-count"><?= count($past) ?></span>
        </div>

        <?php if(empty($past)): ?>
        <div class="empty-state">
            <div class="icon">🕰️</div>
            <p>No past events yet.<br>Your completed events will appear here.</p>
        </div>
        <?php else: ?>
        <div class="event-grid">
        <?php foreach($past as $r):
            $cat   = $catConfig[$r['EventType']] ?? ['icon'=>'📌','color'=>'#888','bg'=>'#f1f5f9'];
            $hours = round($r['duration_mins'] / 60, 2);
            $isService = (stripos($r['EventType'], 'service') !== false || stripos($r['EventType'], 'volunteer') !== false);
        ?>
        <div class="event-card past">
            <div class="event-card-img" style="background:<?= $cat['bg'] ?>">
                <?php if(!empty($r['EventImage'])): ?>
                <img src="<?= htmlspecialchars($r['EventImage'],ENT_QUOTES,'UTF-8') ?>" alt="event">
                <?php else: ?><?= $cat['icon'] ?><?php endif; ?>
            </div>
            <div class="event-card-body">
                <span class="type-badge" style="background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>">
                    <?= $cat['icon'] ?> <?= htmlspecialchars($r['EventType'],ENT_QUOTES,'UTF-8') ?>
                </span>
                <div class="event-name"><?= htmlspecialchars($r['EventName'],ENT_QUOTES,'UTF-8') ?></div>
                <div class="event-desc"><?= htmlspecialchars($r['EventInfo'],ENT_QUOTES,'UTF-8') ?></div>
                <div class="meta-row">📅 <?= date('d M Y', strtotime($r['EventDate'])) ?></div>
                <div class="meta-row">🕐 <?= date('h:i A', strtotime($r['EventStartTime'])) ?> – <?= date('h:i A', strtotime($r['EventEndTime'])) ?></div>
                <div class="meta-row">📍 Block <?= htmlspecialchars($r['EventBlock'],ENT_QUOTES,'UTF-8') ?> – <?= htmlspecialchars($r['EventHall'],ENT_QUOTES,'UTF-8') ?></div>
            </div>
            <div class="event-card-footer">
                <span class="hours-earned">
                    ⏱️ <?= $hours ?>h earned<?= $isService ? '' : ' + pts' ?>
                </span>
                <span style="font-size:11px;color:#aaa;">Completed</span>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
function switchTab(id, btn){
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
</script>
</body>
</html>