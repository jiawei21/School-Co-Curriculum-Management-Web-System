<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: login.php");
    exit();
}

$userid  = intval($_SESSION['userid']);
$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');

$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$validTypes = ['Competition','Event','Service/Volunteer','Workshop','Sport'];
if($filterType && !in_array($filterType, $validTypes)) $filterType = '';


$flashMsg  = '';
$flashType = 'success';
if(isset($_SESSION['flash_message'])){
    $flashMsg  = $_SESSION['flash_message'];
    $flashType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}


if($filterType){
    if($isAdmin){
        $stmt = $con->prepare("SELECT e.*, u.username FROM event e JOIN user u ON e.UserId = u.id WHERE e.EventType=? ORDER BY e.EventDate ASC");
    } else {
        $stmt = $con->prepare("SELECT e.*, u.username FROM event e JOIN user u ON e.UserId = u.id WHERE e.EventType=? AND e.EventStatus='Approved' ORDER BY e.EventDate ASC");
    }
    $stmt->bind_param("s", $filterType);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    if($isAdmin){
        $events = $con->query("SELECT e.*, u.username FROM event e JOIN user u ON e.UserId = u.id ORDER BY e.EventDate ASC");
    } else {
        $events = $con->query("SELECT e.*, u.username FROM event e JOIN user u ON e.UserId = u.id WHERE e.EventStatus='Approved' ORDER BY e.EventDate ASC");
    }
}

$eventRows = [];
while($r = $events->fetch_assoc()) $eventRows[] = $r;
$totalEvents = count($eventRows);


$joinedResult = $con->query("SELECT eventid FROM event_participant WHERE userid=$userid");
$joinedIds = [];
while($jr = $joinedResult->fetch_assoc()) $joinedIds[] = intval($jr['eventid']);

$catConfig = [
    'Competition'       => ['icon'=>'🏆','color'=>'#ef4444','bg'=>'#fee2e2'],
    'Event'             => ['icon'=>'🎉','color'=>'#3b82f6','bg'=>'#dbeafe'],
    'Service/Volunteer' => ['icon'=>'🤝','color'=>'#22c55e','bg'=>'#dcfce7'],
    'Workshop'          => ['icon'=>'🛠️','color'=>'#f59e0b','bg'=>'#fef9c3'],
    'Sport'             => ['icon'=>'⚽','color'=>'#a855f7','bg'=>'#f3e8ff'],
];
$cur = $catConfig[$filterType] ?? ['icon'=>'📅','color'=>'#1E3A8A','bg'=>'#e8edf8'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $filterType ? htmlspecialchars($filterType,ENT_QUOTES,'UTF-8').' Events' : 'All Events'; ?> - Uni Event Tracker</title>
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
.user-menu{display:flex;align-items:center;gap:10px;}
.avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}

.page-wrapper{flex:1;max-width:1200px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:12px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}


.hero-strip{display:flex;align-items:center;gap:18px;padding:24px 28px;border-radius:14px;margin-bottom:28px;background:white;box-shadow:0 4px 20px rgba(0,0,0,0.07);border-left:5px solid <?php echo $cur['color']; ?>;}
.hero-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;background:<?php echo $cur['bg']; ?>;}
.hero-title{font-size:22px;font-weight:700;color:#1a1a2e;}
.hero-sub{font-size:13px;color:#888;margin-top:3px;}
.hero-count{margin-left:auto;font-size:13px;background:#e8edf8;color:#1E3A8A;padding:5px 14px;border-radius:20px;font-weight:600;white-space:nowrap;}


.filter-bar{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:14px 20px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.filter-bar input[type="text"]{flex:1;min-width:180px;padding:8px 14px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;transition:border 0.2s;}
.filter-bar input[type="text"]:focus{outline:none;border-color:#1E3A8A;}
.filter-bar input[type="date"]{padding:8px 10px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;background:white;}
.filter-bar input[type="date"]:focus{outline:none;border-color:#1E3A8A;}
.filter-bar select{padding:8px 12px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;background:white;cursor:pointer;}
.filter-bar select:focus{outline:none;border-color:#1E3A8A;}
.btn-reset{padding:8px 16px;background:#f4f6f9;color:#555;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;transition:all 0.2s;}
.btn-reset:hover{background:#e8edf8;color:#1E3A8A;border-color:#1E3A8A;}
.result-count{font-size:12px;color:#888;margin-left:auto;white-space:nowrap;}
.filter-sep{color:#dde3f0;font-size:18px;user-select:none;}


.event-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:20px;}
.event-card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;display:flex;flex-direction:column;transition:transform 0.2s,box-shadow 0.2s;cursor:pointer;}
.event-card:hover{transform:translateY(-4px);box-shadow:0 10px 30px rgba(0,0,0,0.12);}
.event-card-img{width:100%;height:160px;overflow:hidden;background:<?php echo $cur['bg']; ?>;display:flex;align-items:center;justify-content:center;font-size:42px;flex-shrink:0;}
.event-card-img img{width:100%;height:100%;object-fit:cover;}
.event-card-body{padding:16px 18px;flex:1;display:flex;flex-direction:column;gap:5px;}
.event-card-type{font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;display:inline-block;margin-bottom:3px;align-self:flex-start;}
.event-card-name{font-size:15px;font-weight:700;color:#1a1a2e;line-height:1.3;}
.event-card-desc{font-size:12px;color:#888;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-top:2px;}
.event-card-meta{font-size:12px;color:#555;display:flex;align-items:center;gap:5px;margin-top:3px;}
.event-card-footer{padding:12px 18px;border-top:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;gap:8px;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-rejected{background:#fee2e2;color:#dc2626;}
.badge-joined{background:#dbeafe;color:#1E3A8A;}


.btn-view{display:inline-block;padding:6px 14px;background:#1E3A8A;color:white;border-radius:6px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:background 0.2s;white-space:nowrap;font-family:'Poppins',sans-serif;}
.btn-view:hover{background:#163070;}


.empty-state{text-align:center;padding:60px 20px;color:#aaa;grid-column:1/-1;}
.empty-state .empty-icon{font-size:44px;margin-bottom:12px;}
.empty-state p{font-size:14px;}
mark{background:#fef08a;border-radius:3px;padding:0 2px;}


.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.show{display:flex;}
.modal-box{background:white;border-radius:16px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.25);animation:modalIn 0.2s ease;}
@keyframes modalIn{from{transform:scale(0.93);opacity:0;}to{transform:scale(1);opacity:1;}}

.modal-img{width:100%;height:200px;object-fit:cover;border-radius:16px 16px 0 0;}
.modal-img-placeholder{width:100%;height:200px;display:flex;align-items:center;justify-content:center;font-size:60px;border-radius:16px 16px 0 0;}

.modal-content{padding:24px 28px;}
.modal-type-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;display:inline-block;margin-bottom:10px;}
.modal-title{font-size:20px;font-weight:700;color:#1a1a2e;margin-bottom:14px;line-height:1.3;}

.modal-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
.modal-info-item{background:#f8faff;border-radius:8px;padding:10px 14px;}
.modal-info-label{font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;}
.modal-info-value{font-size:13px;color:#333;font-weight:500;}

.modal-desc-title{font-size:13px;font-weight:600;color:#555;margin-bottom:6px;}
.modal-desc-text{font-size:13px;color:#555;line-height:1.7;margin-bottom:20px;}

.modal-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:16px;border-top:1px solid #f0f0f0;}
.btn-close-modal{padding:9px 20px;background:#f1f5f9;color:#555;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-close-modal:hover{background:#e2e8f0;}
.btn-join{padding:10px 24px;background:#1E3A8A;color:white;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background 0.2s;flex:1;}
.btn-join:hover{background:#163070;}
.btn-unjoin{padding:10px 24px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background 0.2s;flex:1;}
.btn-unjoin:hover{background:#fecaca;}


.toast{position:fixed;top:24px;right:24px;z-index:99999;padding:14px 20px;border-radius:10px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;opacity:0;transform:translateY(-10px);transition:opacity 0.3s,transform 0.3s;pointer-events:none;min-width:260px;max-width:360px;}
.toast.show{opacity:1;transform:translateY(0);}
.toast-success{background:#dcfce7;color:#16a34a;border-left:4px solid #16a34a;}
.toast-warning{background:#fef9c3;color:#92400e;border-left:4px solid #D97706;}
.toast-error{background:#fee2e2;color:#dc2626;border-left:4px solid #dc2626;}

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

    <div class="breadcrumb">
        <a href="event_dashboard.php">Events</a> ›
        <?php echo $filterType ? htmlspecialchars($filterType,ENT_QUOTES,'UTF-8') : 'All'; ?>
    </div>

    <div class="hero-strip">
        <div class="hero-icon"><?php echo $cur['icon']; ?></div>
        <div>
            <div class="hero-title"><?php echo $filterType ? htmlspecialchars($filterType,ENT_QUOTES,'UTF-8') : 'All Events'; ?></div>
            <div class="hero-sub">Browse and join<?php echo $filterType ? ' '.$filterType : ''; ?> events.</div>
        </div>
        <span class="hero-count"><?php echo $totalEvents; ?> event<?php echo $totalEvents!=1?'s':''; ?></span>
    </div>


    <div class="filter-bar">
        <span style="font-size:14px;color:#aaa;">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by name...">
        <span class="filter-sep">|</span>
        <?php if($isAdmin): ?>
        <select id="filterStatus">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Ended">Ended</option>
        </select>
        <span class="filter-sep">|</span>
        <?php endif; ?>
        <input type="date" id="dateFrom" title="From">
        <span style="font-size:12px;color:#aaa;">→</span>
        <input type="date" id="dateTo" title="To">
        <span class="filter-sep">|</span>
        <select id="sortBy">
            <option value="date-asc">Date ↑</option>
            <option value="date-desc">Date ↓</option>
            <option value="name-asc">Name A→Z</option>
            <option value="name-desc">Name Z→A</option>
        </select>
        <button class="btn-reset" onclick="resetFilters()">✕ Reset</button>
        <span class="result-count" id="resultCount"></span>
    </div>

    
    <div class="event-grid" id="eventGrid">
    <?php if(empty($eventRows)): ?>
        <div class="empty-state">
            <div class="empty-icon"><?php echo $cur['icon']; ?></div>
            <p>No <?php echo $filterType ? htmlspecialchars($filterType,ENT_QUOTES,'UTF-8').' ' : ''; ?>events found.</p>
        </div>
    <?php else: ?>
        <?php foreach($eventRows as $row):
            $cat      = $catConfig[$row['EventType']] ?? $cur;
            $isJoined = in_array(intval($row['EventId']), $joinedIds);
            $isPast   = strtotime($row['EventDate'] . ' ' . $row['EventEndTime']) < time();
        ?>
        <div class="event-card"
            data-name="<?php echo htmlspecialchars(strtolower($row['EventName']),ENT_QUOTES,'UTF-8'); ?>"
            data-status="<?php echo htmlspecialchars($row['EventStatus'],ENT_QUOTES,'UTF-8'); ?>"
            data-date="<?php echo $row['EventDate']; ?>"
            onclick="openModal(<?php echo htmlspecialchars(json_encode([
                'id'        => $row['EventId'],
                'name'      => $row['EventName'],
                'type'      => $row['EventType'],
                'info'      => $row['EventInfo'],
                'date'      => date('d M Y', strtotime($row['EventDate'])),
                'start'     => date('h:i A', strtotime($row['EventStartTime'])),
                'end'       => date('h:i A', strtotime($row['EventEndTime'])),
                'block'     => $row['EventBlock'],
                'hall'      => $row['EventHall'],
                'image'     => $row['EventImage'] ?? '',
                'joined'    => $isJoined,
                'past'      => $isPast,
                'icon'      => $cat['icon'],
                'color'     => $cat['color'],
                'bg'        => $cat['bg'],
            ]), ENT_QUOTES, 'UTF-8'); ?>)"
        >
            <div class="event-card-img" style="background:<?php echo $cat['bg']; ?>">
                <?php if(!empty($row['EventImage'])): ?>
                <img src="../<?php echo htmlspecialchars($row['EventImage'],ENT_QUOTES,'UTF-8'); ?>" alt="event">
                <?php else: ?><?php echo $cat['icon']; ?><?php endif; ?>
            </div>
            <div class="event-card-body">
                <span class="event-card-type" style="background:<?php echo $cat['bg']; ?>;color:<?php echo $cat['color']; ?>">
                    <?php echo htmlspecialchars($row['EventType'],ENT_QUOTES,'UTF-8'); ?>
                </span>
                <div class="event-card-name col-name"><?php echo htmlspecialchars($row['EventName'],ENT_QUOTES,'UTF-8'); ?></div>
                <div class="event-card-desc"><?php echo htmlspecialchars($row['EventInfo'],ENT_QUOTES,'UTF-8'); ?></div>
                <div class="event-card-meta">📅 <?php echo date("d M Y",strtotime($row['EventDate'])); ?></div>
                <div class="event-card-meta">🕐 <?php echo date("h:i A",strtotime($row['EventStartTime'])); ?> – <?php echo date("h:i A",strtotime($row['EventEndTime'])); ?></div>
                <div class="event-card-meta">📍 Block <?php echo htmlspecialchars($row['EventBlock'],ENT_QUOTES,'UTF-8'); ?> – <?php echo htmlspecialchars($row['EventHall'],ENT_QUOTES,'UTF-8'); ?></div>
            </div>
            <div class="event-card-footer">
                <?php if($isJoined): ?>
                    <span class="badge badge-joined">✅ Joined</span>
                <?php elseif($isPast): ?>
                    <span class="badge" style="background:#f1f5f9;color:#aaa;">Ended</span>
                <?php else: ?>
                    <span class="badge badge-approved">Open</span>
                <?php endif; ?>
                <button class="btn-view">View Details</button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <div class="empty-state" id="noResults" style="display:none;">
        <div class="empty-icon">😕</div>
        <p>No events match your search.</p>
    </div>

</div>

<footer>© 2026 Uni Event Tracker</footer>


<div class="modal-overlay" id="eventModal" onclick="closeModalOutside(event)">
    <div class="modal-box">
        <div id="modalImgWrap"></div>
        <div class="modal-content">
            <span class="modal-type-badge" id="modalTypeBadge"></span>
            <div class="modal-title" id="modalTitle"></div>

            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <div class="modal-info-label">📅 Date</div>
                    <div class="modal-info-value" id="modalDate"></div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">🕐 Time</div>
                    <div class="modal-info-value" id="modalTime"></div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">📍 Location</div>
                    <div class="modal-info-value" id="modalLocation"></div>
                </div>
                <div class="modal-info-item">
                    <div class="modal-info-label">⏱️ Duration</div>
                    <div class="modal-info-value" id="modalDuration"></div>
                </div>
            </div>

            <div class="modal-desc-title">About this event</div>
            <div class="modal-desc-text" id="modalDesc"></div>

            <div class="modal-footer">
                <button class="btn-close-modal" onclick="closeModal()">Close</button>
                <form method="POST" action="event_join.php" id="joinForm" style="flex:1;display:flex;">
                    <input type="hidden" name="event_id" id="joinEventId">
                    <input type="hidden" name="action"   id="joinAction">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'],ENT_QUOTES,'UTF-8'); ?>">
                    <button type="submit" id="joinBtn" class="btn-join">Join Event</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="toast" id="toast">
    <span id="toastMsg"></span>
</div>

<script>

const searchInput  = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const sortBy       = document.getElementById('sortBy');
const dateFrom     = document.getElementById('dateFrom');
const dateTo       = document.getElementById('dateTo');
const grid         = document.getElementById('eventGrid');
const noResults    = document.getElementById('noResults');
const resultCount  = document.getElementById('resultCount');

function applyFilters(){
    const search = searchInput.value.toLowerCase().trim();
    const status = filterStatus ? filterStatus.value : '';
    const sort   = sortBy.value;
    const from   = dateFrom.value ? new Date(dateFrom.value) : null;
    const to     = dateTo.value   ? new Date(dateTo.value)   : null;

    let cards = Array.from(grid.querySelectorAll('.event-card'));
    cards.forEach(card => {
        const cardDate = new Date(card.dataset.date);
        const matchSearch = !search || card.dataset.name.includes(search);
        const matchStatus = !status || card.dataset.status === status;
        const matchFrom   = !from   || cardDate >= from;
        const matchTo     = !to     || cardDate <= to;
        card.style.display = (matchSearch && matchStatus && matchFrom && matchTo) ? '' : 'none';
    });

    if(sort){
        const visible = cards.filter(c => c.style.display !== 'none');
        visible.sort((a,b) => {
            if(sort==='name-asc')  return a.dataset.name.localeCompare(b.dataset.name);
            if(sort==='name-desc') return b.dataset.name.localeCompare(a.dataset.name);
            if(sort==='date-asc')  return new Date(a.dataset.date)-new Date(b.dataset.date);
            if(sort==='date-desc') return new Date(b.dataset.date)-new Date(a.dataset.date);
            return 0;
        });
        visible.forEach(c => grid.appendChild(c));
    }

    grid.querySelectorAll('.event-card .col-name').forEach(el => {
        const orig = el.getAttribute('data-orig') || el.textContent;
        el.setAttribute('data-orig', orig);
        if(search){
            const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');
            el.innerHTML = orig.replace(regex, '<mark>$1</mark>');
        } else {
            el.textContent = orig;
        }
    });

    const visible = cards.filter(c => c.style.display !== 'none').length;
    noResults.style.display = (visible===0 && cards.length>0) ? 'block' : 'none';
    resultCount.textContent = `Showing ${visible} of ${cards.length}`;
}

function resetFilters(){
    searchInput.value = '';
    if(filterStatus) filterStatus.value = '';
    dateFrom.value = ''; dateTo.value = '';
    sortBy.value = 'date-asc';
    applyFilters();
}

searchInput.addEventListener('input', applyFilters);
if(filterStatus) filterStatus.addEventListener('change', applyFilters);
sortBy.addEventListener('change', applyFilters);
dateFrom.addEventListener('change', applyFilters);
dateTo.addEventListener('change', applyFilters);
applyFilters();


function openModal(ev){
    document.getElementById('modalImgWrap').innerHTML = ev.image
        ? `<img class="modal-img" src="../${ev.image}" alt="event">`
        : `<div class="modal-img-placeholder" style="background:${ev.bg}">${ev.icon}</div>`;

    document.getElementById('modalTypeBadge').textContent  = ev.type;
    document.getElementById('modalTypeBadge').style.background = ev.bg;
    document.getElementById('modalTypeBadge').style.color      = ev.color;
    document.getElementById('modalTitle').textContent    = ev.name;
    document.getElementById('modalDate').textContent     = ev.date;
    document.getElementById('modalTime').textContent     = `${ev.start} – ${ev.end}`;
    document.getElementById('modalLocation').textContent = `Block ${ev.block} – ${ev.hall}`;
    document.getElementById('modalDesc').textContent     = ev.info || '—';

    
    const s = new Date(`2000-01-01 ${ev.start}`);
    const e = new Date(`2000-01-01 ${ev.end}`);
    const mins = (e - s) / 60000;
    const hrs  = Math.floor(mins / 60);
    const rem  = mins % 60;
    document.getElementById('modalDuration').textContent =
        hrs > 0 ? `${hrs}h ${rem > 0 ? rem + 'm' : ''}`.trim() : `${rem}m`;

    
    document.getElementById('joinEventId').value = ev.id;
    const btn = document.getElementById('joinBtn');
    if(ev.past){
        btn.textContent  = 'Event Ended';
        btn.disabled     = true;
        btn.className    = 'btn-join';
        btn.style.background = '#ccc';
        btn.style.cursor = 'not-allowed';
    } else if(ev.joined){
        btn.textContent  = '❌ Leave Event';
        btn.disabled     = false;
        btn.className    = 'btn-unjoin';
        btn.style.background = '';
        btn.style.cursor = 'pointer';
        document.getElementById('joinAction').value = 'unjoin';
    } else {
        btn.textContent  = '✅ Join Event';
        btn.disabled     = false;
        btn.className    = 'btn-join';
        btn.style.background = '';
        btn.style.cursor = 'pointer';
        document.getElementById('joinAction').value = 'join';
    }

    document.getElementById('eventModal').classList.add('show');
}

function closeModal(){
    document.getElementById('eventModal').classList.remove('show');
}

function closeModalOutside(e){
    if(e.target === document.getElementById('eventModal')) closeModal();
}


function showToast(msg, type='success'){
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.className = `toast toast-${type} show`;
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 3500);
}

<?php if($flashMsg): ?>
showToast(<?php echo json_encode($flashMsg); ?>, <?php echo json_encode($flashType); ?>);
<?php endif; ?>
</script>
</body>
</html>