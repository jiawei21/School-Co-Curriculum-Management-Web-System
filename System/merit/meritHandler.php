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


$eventId = intval($_GET['event_id'] ?? 0);
if (!$eventId) {
    header("Location: ../Event/event_dashboard.php");
    exit();
}


$evRes = $con->query("SELECT * FROM event WHERE EventId = $eventId LIMIT 1");
if (!$evRes || $evRes->num_rows === 0) {
    header("Location: ../Event/event_dashboard.php");
    exit();
}
$event = $evRes->fetch_assoc();

$isOwner = (intval($event['CreatedBy'] ?? $event['userid'] ?? 0) === $userid);
if (!$isOwner && !$isAdmin) {
    header("Location: ../Event/event_dashboard.php");
    exit();
}


$con->query("
    UPDATE event
    SET EventStatus = 'Ended'
    WHERE EventId = $eventId
      AND EventStatus = 'Approved'
      AND (
          EventDate < CURDATE()
          OR (EventDate = CURDATE() AND EventEndTime <= CURTIME())
      )
");
// Re-fetch
$event = $con->query("SELECT * FROM event WHERE EventId = $eventId LIMIT 1")->fetch_assoc();

$eventEnded = (strtolower($event['EventStatus']) === 'ended');


function calcHours($s, $e) {
    $sec = strtotime($e) - strtotime($s);
    return ($sec > 0) ? round($sec / 3600, 2) : 0;
}
$defaultHours = calcHours($event['EventStartTime'], $event['EventEndTime']);


$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $eventEnded) {
    $action = $_POST['action'] ?? '';

    if ($action === 'bulk') {
        
        $participants = $_POST['participants'] ?? [];
        $approved = 0; $rejected = 0;

        foreach ($participants as $pUid => $data) {
            $pUid   = intval($pUid);
            $pAct   = $data['action'] ?? 'skip';
            $pHours = round(floatval($data['hours'] ?? $defaultHours), 2);
            $pDesc  = $con->real_escape_string(trim($data['description'] ?? ''));

            if ($pAct === 'skip') continue;

            if ($pAct === 'approve') {
                if ($pHours <= 0) $pHours = $defaultHours;

                // Upsert merit
                $exists = $con->query("
                    SELECT merit_id FROM merit
                    WHERE userid = $pUid AND event_id = $eventId LIMIT 1
                ")->fetch_assoc();

                if ($exists) {
                    $con->query("
                        UPDATE merit SET
                            hours             = $pHours,
                            merit_description = '$pDesc',
                            status            = 'approved',
                            graded_by         = $userid,
                            graded_at         = NOW()
                        WHERE merit_id = {$exists['merit_id']}
                    ");
                } else {
                    $actName = $con->real_escape_string($event['EventName']);
                    $mDate   = $event['EventDate'];
                    $con->query("
                        INSERT INTO merit
                            (userid, event_id, activity_name, hours, merit_date, merit_description, status, graded_by, graded_at)
                        VALUES
                            ($pUid, $eventId, '$actName', $pHours, '$mDate', '$pDesc', 'approved', $userid, NOW())
                    ");
                }
                $con->query("UPDATE event_participant SET merit_created=1 WHERE userid=$pUid AND eventid=$eventId");
                $approved++;

            } elseif ($pAct === 'reject') {
                if (empty($pDesc)) $pDesc = 'Rejected by organiser.';

                $exists = $con->query("
                    SELECT merit_id FROM merit
                    WHERE userid = $pUid AND event_id = $eventId LIMIT 1
                ")->fetch_assoc();

                if ($exists) {
                    $con->query("
                        UPDATE merit SET
                            hours             = 0,
                            merit_description = '$pDesc',
                            status            = 'rejected',
                            graded_by         = $userid,
                            graded_at         = NOW()
                        WHERE merit_id = {$exists['merit_id']}
                    ");
                } else {
                    $actName = $con->real_escape_string($event['EventName']);
                    $mDate   = $event['EventDate'];
                    $con->query("
                        INSERT INTO merit
                            (userid, event_id, activity_name, hours, merit_date, merit_description, status, graded_by, graded_at)
                        VALUES
                            ($pUid, $eventId, '$actName', 0, '$mDate', '$pDesc', 'rejected', $userid, NOW())
                    ");
                }
                $con->query("UPDATE event_participant SET merit_created=1 WHERE userid=$pUid AND eventid=$eventId");
                $rejected++;
            }
        }

        $flash     = "✅ Done! Approved: $approved  |  Rejected: $rejected";
        $flashType = 'success';

    } elseif ($action === 'single') {
        $pUid   = intval($_POST['target_userid']);
        $pAct   = $_POST['single_action'] ?? 'approve';
        $pHours = round(floatval($_POST['single_hours'] ?? $defaultHours), 2);
        $pDesc  = $con->real_escape_string(trim($_POST['single_description'] ?? ''));

        if ($pAct === 'approve') {
            if ($pHours <= 0) $pHours = $defaultHours;
            $exists = $con->query("SELECT merit_id FROM merit WHERE userid=$pUid AND event_id=$eventId LIMIT 1")->fetch_assoc();
            if ($exists) {
                $con->query("UPDATE merit SET hours=$pHours, merit_description='$pDesc', status='approved', graded_by=$userid, graded_at=NOW() WHERE merit_id={$exists['merit_id']}");
            } else {
                $actName = $con->real_escape_string($event['EventName']);
                $mDate   = $event['EventDate'];
                $con->query("INSERT INTO merit (userid,event_id,activity_name,hours,merit_date,merit_description,status,graded_by,graded_at) VALUES ($pUid,$eventId,'$actName',$pHours,'$mDate','$pDesc','approved',$userid,NOW())");
            }
            $con->query("UPDATE event_participant SET merit_created=1 WHERE userid=$pUid AND eventid=$eventId");
            $flash = '✅ Merit approved.'; $flashType = 'success';
        } else {
            if (empty($pDesc)) $pDesc = 'Rejected by organiser.';
            $exists = $con->query("SELECT merit_id FROM merit WHERE userid=$pUid AND event_id=$eventId LIMIT 1")->fetch_assoc();
            if ($exists) {
                $con->query("UPDATE merit SET hours=0, merit_description='$pDesc', status='rejected', graded_by=$userid, graded_at=NOW() WHERE merit_id={$exists['merit_id']}");
            } else {
                $actName = $con->real_escape_string($event['EventName']);
                $mDate   = $event['EventDate'];
                $con->query("INSERT INTO merit (userid,event_id,activity_name,hours,merit_date,merit_description,status,graded_by,graded_at) VALUES ($pUid,$eventId,'$actName',0,'$mDate','$pDesc','rejected',$userid,NOW())");
            }
            $con->query("UPDATE event_participant SET merit_created=1 WHERE userid=$pUid AND eventid=$eventId");
            $flash = '🚫 Merit rejected.'; $flashType = 'danger';
        }
    }
}


$participants = [];
$pRes = $con->query("
    SELECT
        u.id,
        u.username,
        u.email,
        ep.id          AS participant_id,
        ep.merit_created,
        m.merit_id,
        m.hours,
        m.status       AS merit_status,
        m.merit_description
    FROM event_participant ep
    JOIN user u ON u.id  = ep.userid
    LEFT JOIN merit m ON m.userid = u.id AND m.event_id = $eventId
    WHERE ep.eventid = $eventId
    ORDER BY u.username ASC
");
while ($row = $pRes->fetch_assoc()) $participants[] = $row;


$totalP   = count($participants);
$gradedP  = 0; $approvedP = 0; $rejectedP = 0; $pendingP = 0;
foreach ($participants as $p) {
    if ($p['merit_status'] === 'approved')      { $gradedP++; $approvedP++; }
    elseif ($p['merit_status'] === 'rejected')  { $gradedP++; $rejectedP++; }
    else $pendingP++;
}

$catConfig = [
    'Competition'       => ['border'=>'#ef4444','bg'=>'#fee2e2','emoji'=>'🏆'],
    'Event'             => ['border'=>'#3b82f6','bg'=>'#dbeafe','emoji'=>'🎉'],
    'Service/Volunteer' => ['border'=>'#22c55e','bg'=>'#dcfce7','emoji'=>'🤝'],
    'Workshop'          => ['border'=>'#f59e0b','bg'=>'#fef9c3','emoji'=>'🛠️'],
    'Sport'             => ['border'=>'#a855f7','bg'=>'#f3e8ff','emoji'=>'⚽'],
];
$evMeta = $catConfig[$event['EventType']] ?? ['border'=>'#1E3A8A','bg'=>'#e8edf8','emoji'=>'📌'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Grade Merit – <?= htmlspecialchars($event['EventName'],ENT_QUOTES,'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
*{box-sizing:border-box;margin:0;padding:0}
:root{
    --navy:#1E3A8A;--navy-dark:#162d6e;--navy-light:#e8edf8;
    --green:#16a34a;--green-bg:#dcfce7;
    --red:#dc2626;--red-bg:#fee2e2;
    --amber:#d97706;--amber-bg:#fef9c3;
    --gray:#64748b;--border:#e2e8f0;
    --card:#fff;--bg:#f4f6f9;
}
body{font-family:'Poppins',sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;color:#1a1a2e;}


header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
.logo a{font-size:20px;font-weight:700;color:#fff;text-decoration:none;}
nav{display:flex;align-items:center;gap:20px;}
nav a{color:#fff;text-decoration:none;font-size:13px;opacity:.85;}
nav a:hover{opacity:1;}
.avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;}


.page{max-width:1060px;margin:36px auto;padding:0 20px;flex:1;}
.breadcrumb{font-size:12px;color:#94a3b8;margin-bottom:18px;}
.breadcrumb a{color:var(--navy);text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}


.event-hero{
    background:var(--card);border-radius:16px;padding:24px 28px;
    border-left:6px solid var(--ev-color, var(--navy));
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    display:flex;align-items:flex-start;gap:20px;margin-bottom:28px;
}
.event-hero-icon{font-size:40px;flex-shrink:0;line-height:1;}
.event-hero-body{flex:1;}
.event-hero-body h1{font-size:22px;font-weight:700;color:var(--navy);margin-bottom:6px;}
.event-meta{display:flex;gap:18px;flex-wrap:wrap;margin-top:8px;}
.event-meta span{font-size:12px;color:var(--gray);display:flex;align-items:center;gap:5px;}
.status-chip{
    display:inline-flex;align-items:center;gap:5px;padding:4px 12px;
    border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
}
.chip-ended{background:#f1f5f9;color:#475569;}
.chip-active{background:#dcfce7;color:#15803d;}
.chip-pending{background:var(--amber-bg);color:var(--amber);}


.not-ended-wall{
    background:var(--amber-bg);border:2px dashed var(--amber);
    border-radius:14px;padding:40px 32px;text-align:center;margin-top:20px;
}
.not-ended-wall .wall-icon{font-size:52px;margin-bottom:12px;}
.not-ended-wall h2{font-size:20px;font-weight:700;color:var(--amber);margin-bottom:8px;}
.not-ended-wall p{font-size:13px;color:#78350f;line-height:1.7;}


.flash{padding:13px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.flash.success{background:var(--green-bg);color:var(--green);border:1px solid #bbf7d0;}
.flash.danger{background:var(--red-bg);color:var(--red);border:1px solid #fca5a5;}


.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.stat-card{background:var(--card);border-radius:12px;padding:18px 16px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.05);border-top:3px solid var(--sc, var(--navy));}
.stat-card .sv{font-size:28px;font-weight:700;color:var(--sc, var(--navy));}
.stat-card .sl{font-size:11px;color:var(--gray);margin-top:4px;font-weight:500;}


.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;}
.toolbar h3{font-size:16px;font-weight:700;color:var(--navy);}
.toolbar-actions{display:flex;gap:10px;}
.btn{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:'Poppins',sans-serif;transition:.15s;}
.btn-primary{background:var(--navy);color:#fff;}
.btn-primary:hover{background:var(--navy-dark);}
.btn-green{background:var(--green);color:#fff;}
.btn-green:hover{background:#15803d;}
.btn-outline{background:#fff;color:var(--navy);border:1.5px solid var(--navy);}
.btn-outline:hover{background:var(--navy-light);}


.table-card{background:var(--card);border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead{background:var(--navy);color:#fff;}
thead th{padding:13px 16px;text-align:left;font-size:12px;font-weight:600;letter-spacing:.03em;}
tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s;}
tbody tr:hover{background:#f8faff;}
tbody td{padding:10px 16px;font-size:13px;color:#333;vertical-align:middle;}
tbody tr:last-child{border-bottom:none;}


.row-action-group{display:flex;gap:6px;align-items:center;}
.pill-approve,.pill-reject,.pill-skip{
    padding:5px 14px;border-radius:20px;font-size:11px;font-weight:700;border:2px solid transparent;cursor:pointer;font-family:'Poppins',sans-serif;transition:.15s;white-space:nowrap;
}
.pill-approve{background:var(--green-bg);color:var(--green);border-color:transparent;}
.pill-approve.selected,.pill-approve:hover{background:var(--green);color:#fff;border-color:var(--green);}
.pill-reject{background:var(--red-bg);color:var(--red);}
.pill-reject.selected,.pill-reject:hover{background:var(--red);color:#fff;border-color:var(--red);}
.pill-skip{background:#f1f5f9;color:var(--gray);}
.pill-skip.selected,.pill-skip:hover{background:var(--gray);color:#fff;}

input.hours-input{
    width:70px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;
    font-size:13px;font-family:'Poppins',sans-serif;text-align:center;color:var(--navy);font-weight:600;
}
input.hours-input:focus{outline:none;border-color:var(--navy);}
input.desc-input{
    width:100%;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;
    font-size:12px;font-family:'Poppins',sans-serif;color:#333;
}
input.desc-input:focus{outline:none;border-color:var(--navy);}
input.desc-input.reject-required{border-color:var(--red);background:var(--red-bg);}


.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-approved{background:var(--green-bg);color:var(--green);}
.badge-rejected{background:var(--red-bg);color:var(--red);}
.badge-pending{background:var(--amber-bg);color:var(--amber);}


.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:#fff;border-radius:16px;padding:30px 28px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.18);animation:popIn .2s ease both;}
@keyframes popIn{from{transform:scale(.92);opacity:0}to{transform:scale(1);opacity:1}}
.modal h3{font-size:17px;font-weight:700;color:var(--navy);margin-bottom:20px;}
.modal label{display:block;font-size:12px;font-weight:600;color:var(--gray);margin-bottom:5px;margin-top:14px;}
.modal input,.modal textarea{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'Poppins',sans-serif;}
.modal input:focus,.modal textarea:focus{outline:none;border-color:var(--navy);}
.modal textarea{resize:vertical;min-height:80px;}
.modal-actions{display:flex;gap:10px;margin-top:22px;}
.modal-actions .btn{flex:1;}


.empty{text-align:center;padding:60px 20px;color:#aaa;}
.empty .icon{font-size:50px;margin-bottom:12px;}

footer{text-align:center;padding:18px;background:var(--navy);color:#fff;font-size:12px;margin-top:auto;}

@media(max-width:768px){
    header{padding:12px 16px;}
    .stats-row{grid-template-columns:repeat(2,1fr);}
    .event-hero{flex-direction:column;gap:12px;}
    thead th:nth-child(5),tbody td:nth-child(5){display:none;}
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

<div class="page">

    <div class="breadcrumb">
        <a href="../index.php">Home</a> ›
        <a href="../Event/event_dashboard.php">Events</a> ›
        <a href="merit_dashboard.php">Merit</a> ›
        Grade Merit
    </div>

    
    <div class="event-hero" style="--ev-color:<?= $evMeta['border'] ?>">
        <div class="event-hero-icon"><?= $evMeta['emoji'] ?></div>
        <div class="event-hero-body">
            <h1><?= htmlspecialchars($event['EventName'],ENT_QUOTES,'UTF-8') ?></h1>
            <div style="margin-top:6px">
                <?php if ($eventEnded): ?>
                <span class="status-chip chip-ended">🏁 Ended</span>
                <?php else: ?>
                <span class="status-chip chip-active">🟢 <?= htmlspecialchars($event['EventStatus'],ENT_QUOTES,'UTF-8') ?></span>
                <?php endif; ?>
                <span class="status-chip" style="background:<?= $evMeta['bg'] ?>;color:<?= $evMeta['border'] ?>;margin-left:6px">
                    <?= htmlspecialchars($event['EventType'],ENT_QUOTES,'UTF-8') ?>
                </span>
            </div>
            <div class="event-meta">
                <span>📅 <?= date('d M Y', strtotime($event['EventDate'])) ?></span>
                <span>🕐 <?= date('g:i A', strtotime($event['EventStartTime'])) ?> – <?= date('g:i A', strtotime($event['EventEndTime'])) ?></span>
                <span>⏱️ Default <?= $defaultHours ?>h per participant</span>
                <span>👥 <?= $totalP ?> registered</span>
            </div>
        </div>
    </div>

    <?php if (!$eventEnded): ?>
    <!-- Locked wall -->
    <div class="not-ended-wall">
        <div class="wall-icon">🔒</div>
        <h2>Event Has Not Ended Yet</h2>
        <p>
            You can only grade merit <strong>after</strong> the event has ended.<br>
            This event ends on <strong><?= date('d M Y, g:i A', strtotime($event['EventDate'].' '.$event['EventEndTime'])) ?></strong>.<br>
            Come back after the event to assign contribution hours to participants.
        </p>
    </div>

    <?php else: ?>

    <?php if ($flash): ?>
    <div class="flash <?= $flashType ?>"><?= $flash ?></div>
    <?php endif; ?>

    
    <div class="stats-row">
        <div class="stat-card" style="--sc:var(--navy)">
            <div class="sv"><?= $totalP ?></div>
            <div class="sl">Total Registered</div>
        </div>
        <div class="stat-card" style="--sc:var(--green)">
            <div class="sv"><?= $approvedP ?></div>
            <div class="sl">Approved</div>
        </div>
        <div class="stat-card" style="--sc:var(--red)">
            <div class="sv"><?= $rejectedP ?></div>
            <div class="sl">Rejected</div>
        </div>
        <div class="stat-card" style="--sc:var(--amber)">
            <div class="sv"><?= $pendingP ?></div>
            <div class="sl">Not Graded Yet</div>
        </div>
    </div>

    
    <?php if (!empty($participants)): ?>
    <form method="POST" id="bulkForm">
        <input type="hidden" name="action" value="bulk">

        <div class="toolbar">
            <h3>👥 Participants</h3>
            <div class="toolbar-actions">
                <button type="button" class="btn btn-outline" onclick="setAll('approve')">✅ Approve All</button>
                <button type="button" class="btn btn-outline" onclick="setAll('reject')">🚫 Reject All</button>
                <button type="submit" class="btn btn-primary" onclick="return confirmBulk()">💾 Save All Grades</button>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this)" title="Select all"></th>
                        <th>Participant</th>
                        <th>Current Status</th>
                        <th>Hours</th>
                        <th>Reason / Note</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($participants as $p):
                    $st = $p['merit_status'] ?? 'pending';
                    $initHours = $p['hours'] > 0 ? $p['hours'] : $defaultHours;
                    $initDesc  = htmlspecialchars($p['merit_description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $uid = $p['id'];
                ?>
                <tr id="row-<?= $uid ?>">
                    <td><input type="checkbox" name="sel[]" value="<?= $uid ?>" class="row-check"></td>
                    <td>
                        <div style="font-weight:600;color:#1a1a2e"><?= htmlspecialchars($p['username'],ENT_QUOTES,'UTF-8') ?></div>
                        <div style="font-size:11px;color:var(--gray)"><?= htmlspecialchars($p['email'],ENT_QUOTES,'UTF-8') ?></div>
                    </td>
                    <td>
                        <?php if ($st === 'approved'): ?>
                        <span class="badge badge-approved">✅ <?= $p['hours'] ?>h</span>
                        <?php elseif ($st === 'rejected'): ?>
                        <span class="badge badge-rejected">🚫 Rejected</span>
                        <?php else: ?>
                        <span class="badge badge-pending">⏳ Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="number" class="hours-input"
                               name="participants[<?= $uid ?>][hours]"
                               value="<?= $initHours ?>" min="0.1" max="24" step="0.5"
                               id="hrs-<?= $uid ?>">
                    </td>
                    <td>
                        <input type="text" class="desc-input"
                               name="participants[<?= $uid ?>][description]"
                               value="<?= $initDesc ?>"
                               placeholder="Optional note (required if reject)"
                               id="desc-<?= $uid ?>">
                    </td>
                    <td>
                        <div class="row-action-group">
                            <button type="button" class="pill-approve" data-uid="<?= $uid ?>" data-act="approve" onclick="pickAction(<?= $uid ?>,'approve')">✅ Approve</button>
                            <button type="button" class="pill-reject"  data-uid="<?= $uid ?>" data-act="reject"  onclick="pickAction(<?= $uid ?>,'reject')">🚫 Reject</button>
                            <button type="button" class="pill-skip"    data-uid="<?= $uid ?>" data-act="skip"    onclick="pickAction(<?= $uid ?>,'skip')">— Skip</button>
                        </div>
                        <input type="hidden" name="participants[<?= $uid ?>][action]" id="act-<?= $uid ?>" value="skip">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php else: ?>
    <div class="empty">
        <div class="icon">👤</div>
        <p>No participants have registered for this event.</p>
    </div>
    <?php endif; ?>

    <?php endif; // eventEnded ?>

</div>


<div class="modal-overlay" id="quickModal">
    <div class="modal">
        <h3 id="modal-title">Grade Participant</h3>
        <form method="POST" id="quickForm">
            <input type="hidden" name="action" value="single">
            <input type="hidden" name="target_userid" id="modal-uid">
            <label>Action</label>
            <div style="display:flex;gap:8px;margin-bottom:4px">
                <button type="button" class="pill-approve" id="qApprove" onclick="setQuickAction('approve')">✅ Approve</button>
                <button type="button" class="pill-reject"  id="qReject"  onclick="setQuickAction('reject')">🚫 Reject</button>
            </div>
            <input type="hidden" name="single_action" id="q-action" value="approve">
            <label>Contribution Hours</label>
            <input type="number" name="single_hours" id="q-hours" min="0.1" max="24" step="0.5" value="<?= $defaultHours ?>">
            <label>Reason / Note <span id="q-desc-label" style="color:var(--red);display:none">*required</span></label>
            <textarea name="single_description" id="q-desc" placeholder="Optional (required if reject)"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="q-submit">Save Grade</button>
            </div>
        </form>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
const defaultHours = <?= json_encode($defaultHours) ?>;


function pickAction(uid, act) {
    document.getElementById('act-' + uid).value = act;
    ['approve','reject','skip'].forEach(a => {
        const btn = document.querySelector(`[data-uid="${uid}"][data-act="${a}"]`);
        if (btn) btn.classList.toggle('selected', a === act);
    });
    const hrsEl  = document.getElementById('hrs-'  + uid);
    const descEl = document.getElementById('desc-' + uid);
    const row    = document.getElementById('row-'  + uid);

    if (act === 'skip') {
        row.style.opacity = '.45';
        descEl.classList.remove('reject-required');
        descEl.placeholder = 'Optional note';
    } else if (act === 'reject') {
        row.style.opacity = '1';
        descEl.classList.add('reject-required');
        descEl.placeholder = 'Reason for rejection (required)';
        descEl.focus();
    } else {
        row.style.opacity = '1';
        descEl.classList.remove('reject-required');
        descEl.placeholder = 'Optional note';
    }
}

function setAll(act) {
    document.querySelectorAll('[data-act]').forEach(btn => {
        const uid = btn.dataset.uid;
        if (btn.dataset.act === act) pickAction(uid, act);
    });
}

function confirmBulk() {
  
    let missing = 0;
    document.querySelectorAll('.reject-required').forEach(el => {
        if (!el.value.trim()) { el.style.borderColor = '#dc2626'; missing++; }
        else el.style.borderColor = '';
    });
    if (missing) {
        alert('Please fill in the rejection reason for highlighted rows.');
        return false;
    }
    const acts = [...document.querySelectorAll('[id^="act-"]')];
    const approved = acts.filter(i=>i.value==='approve').length;
    const rejected = acts.filter(i=>i.value==='reject').length;
    const skipped  = acts.filter(i=>i.value==='skip').length;
    if (approved + rejected === 0) {
        alert('No action selected. Please approve or reject at least one participant.');
        return false;
    }
    return confirm(`Save grades?\n✅ Approve: ${approved}\n🚫 Reject: ${rejected}\n— Skip: ${skipped}`);
}


function toggleCheckAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
}


function openModal(uid, name, currentHrs, currentDesc) {
    document.getElementById('modal-uid').value   = uid;
    document.getElementById('modal-title').textContent = '📋 Grade: ' + name;
    document.getElementById('q-hours').value     = currentHrs || defaultHours;
    document.getElementById('q-desc').value      = currentDesc || '';
    setQuickAction('approve');
    document.getElementById('quickModal').classList.add('open');
}
function closeModal() {
    document.getElementById('quickModal').classList.remove('open');
}
function setQuickAction(act) {
    document.getElementById('q-action').value = act;
    document.getElementById('qApprove').classList.toggle('selected', act === 'approve');
    document.getElementById('qReject').classList.toggle('selected',  act === 'reject');
    document.getElementById('q-desc-label').style.display = act === 'reject' ? 'inline' : 'none';
    document.getElementById('q-submit').textContent = act === 'approve' ? '✅ Approve' : '🚫 Reject';
}
document.getElementById('quickModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});


document.querySelectorAll('tbody tr').forEach(tr => {
    tr.addEventListener('dblclick', function() {
        const uid  = this.id.replace('row-','');
        const name = this.querySelector('td:nth-child(2) div').textContent;
        const hrs  = document.getElementById('hrs-' + uid)?.value;
        const desc = document.getElementById('desc-' + uid)?.value;
        openModal(uid, name, hrs, desc);
    });
});
</script>
</body>
</html>