<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    switch($_POST['action']){
        case 'approve':
            $s = $con->prepare("UPDATE event SET EventStatus='Approved' WHERE EventId=?");
            $s->bind_param("i",$id); $s->execute(); break;
        case 'reject':
            $s = $con->prepare("UPDATE event SET EventStatus='Rejected' WHERE EventId=?");
            $s->bind_param("i",$id); $s->execute(); break;
        case 'delete':
            $s = $con->prepare("DELETE FROM event WHERE EventId=?");
            $s->bind_param("i",$id); $s->execute(); break;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$events = $con->query("SELECT EventId, EventName, EventType, EventDate, EventStatus FROM event ORDER BY EventDate DESC");
$eventRows = [];
while($r = $events->fetch_assoc()) $eventRows[] = $r;

$totalEvents   = count($eventRows);
$pendingEvents = count(array_filter($eventRows, fn($r) => $r['EventStatus'] === 'Pending'));


$eventTypes = array_unique(array_column($eventRows, 'EventType'));
sort($eventTypes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Events - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
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
.avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}

.page-wrapper{flex:1;max-width:1200px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}
.page-header{margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.page-header h2{font-size:22px;font-weight:700;color:#1E3A8A;}
.count-badge{font-size:12px;background:#e8edf8;color:#1E3A8A;padding:4px 12px;border-radius:20px;font-weight:500;}


.filter-bar{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.filter-bar input[type="text"]{
    flex:1;min-width:180px;padding:8px 14px;border:1.5px solid #dde3f0;border-radius:8px;
    font-family:'Poppins',sans-serif;font-size:13px;color:#333;transition:border 0.2s;
}
.filter-bar input[type="text"]:focus{outline:none;border-color:#1E3A8A;}
.filter-bar select{
    padding:8px 12px;border:1.5px solid #dde3f0;border-radius:8px;
    font-family:'Poppins',sans-serif;font-size:13px;color:#333;background:white;cursor:pointer;transition:border 0.2s;
}
.filter-bar select:focus{outline:none;border-color:#1E3A8A;}
.btn-reset{padding:8px 16px;background:#f4f6f9;color:#555;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;transition:all 0.2s;}
.btn-reset:hover{background:#e8edf8;color:#1E3A8A;border-color:#1E3A8A;}
.filter-label{font-size:12px;color:#888;font-weight:500;white-space:nowrap;}
.result-count{font-size:12px;color:#888;margin-left:auto;white-space:nowrap;}


.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{padding:12px 20px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #f0f0f0;white-space:nowrap;cursor:pointer;user-select:none;}
th:hover{color:#1E3A8A;}
th .sort-arrow{margin-left:4px;opacity:0.4;font-size:10px;}
th.sorted .sort-arrow{opacity:1;color:#1E3A8A;}
td{padding:13px 20px;font-size:13px;color:#333;border-bottom:1px solid #f9f9f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafbff;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-rejected{background:#fee2e2;color:#dc2626;}
.action-form{display:inline;}
.btn-approve{padding:5px 12px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:12px;font-family:'Poppins',sans-serif;cursor:pointer;transition:background 0.2s;margin-right:4px;}
.btn-approve:hover{background:#15803d;}
.btn-reject{padding:5px 12px;background:#f59e0b;color:white;border:none;border-radius:6px;font-size:12px;font-family:'Poppins',sans-serif;cursor:pointer;transition:background 0.2s;margin-right:4px;}
.btn-reject:hover{background:#d97706;}
.btn-delete{padding:5px 12px;background:#dc2626;color:white;border:none;border-radius:6px;font-size:12px;font-family:'Poppins',sans-serif;cursor:pointer;transition:background 0.2s;margin-right:4px;}
.btn-delete:hover{background:#b91c1c;}
.btn-edit{display:inline-block;padding:5px 12px;background:#1E3A8A;color:white;border-radius:6px;font-size:12px;text-decoration:none;transition:background 0.2s;margin-right:4px;}
.btn-edit:hover{background:#163070;}
.empty-row td{text-align:center;padding:40px;color:#aaa;font-size:13px;}
.no-results{text-align:center;padding:40px;color:#aaa;font-size:13px;display:none;}
mark{background:#fef08a;border-radius:3px;padding:0 2px;}

.btn-back{display:inline-block;padding:9px 18px;background:white;color:#1E3A8A;border:1.5px solid #1E3A8A;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;transition:all 0.2s;}
.btn-back:hover{background:#1E3A8A;color:white;}
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
        <a href="admin.php">Admin Dashboard</a> &rsaquo; Manage Events
    </div>

    <div class="page-header">
        <h2>Manage Events</h2>
        <span class="count-badge"><?php echo $totalEvents; ?> total &nbsp;·&nbsp; <span style="color:#D97706;"><?php echo $pendingEvents; ?> pending</span></span>
    </div>

    
    <div class="filter-bar">
        <span class="filter-label">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by name...">

        <select id="filterType">
            <option value="">All Types</option>
            <?php foreach($eventTypes as $type): ?>
            <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select id="filterStatus">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
        </select>

        <select id="sortBy">
            <option value="">Sort by...</option>
            <option value="name-asc">Name A → Z</option>
            <option value="name-desc">Name Z → A</option>
            <option value="date-asc">Date (Oldest first)</option>
            <option value="date-desc">Date (Newest first)</option>
        </select>

        <button class="btn-reset" onclick="resetFilters()">✕ Reset</button>
        <span class="result-count" id="resultCount"></span>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Events</h3>
        </div>
        <div class="table-wrap">
            <table id="eventTable">
                <thead>
                    <tr>
                        <th data-col="name">Event Name <span class="sort-arrow">↕</span></th>
                        <th data-col="type">Type <span class="sort-arrow">↕</span></th>
                        <th data-col="date">Date <span class="sort-arrow">↕</span></th>
                        <th data-col="status">Status <span class="sort-arrow">↕</span></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventTbody">
                <?php if(empty($eventRows)): ?>
                    <tr class="empty-row"><td colspan="5">No events found.</td></tr>
                <?php else: ?>
                    <?php foreach($eventRows as $row):
                        $badgeClass = 'badge-'.strtolower($row['EventStatus']);
                    ?>
                    <tr
                        data-name="<?php echo htmlspecialchars(strtolower($row['EventName']), ENT_QUOTES, 'UTF-8'); ?>"
                        data-type="<?php echo htmlspecialchars($row['EventType'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-status="<?php echo htmlspecialchars($row['EventStatus'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-date="<?php echo $row['EventDate']; ?>"
                    >
                        <td class="col-name"><strong><?php echo htmlspecialchars($row['EventName'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['EventType'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo date("d M Y", strtotime($row['EventDate'])); ?></td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['EventStatus'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <?php if($row['EventStatus'] === 'Pending'): ?>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['EventId']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn-approve">Approve</button>
                            </form>
                            <form class="action-form" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['EventId']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn-reject">Reject</button>
                            </form>
                            <?php endif; ?>
                            <a class="btn-edit" href="../event/eventHandler.php?id=<?php echo $row['EventId']; ?>">Edit</a>
                            <form class="action-form" method="POST" onsubmit="return confirm('Delete this event? This cannot be undone.')">
                                <input type="hidden" name="id" value="<?php echo $row['EventId']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="no-results" id="noResults">😕 No events match your search.</div>
        </div>
    </div>

    <a class="btn-back" href="admin_dashboard.php">← Back to Dashboard</a>

</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
const searchInput  = document.getElementById('searchInput');
const filterType   = document.getElementById('filterType');
const filterStatus = document.getElementById('filterStatus');
const sortBy       = document.getElementById('sortBy');
const tbody        = document.getElementById('eventTbody');
const noResults    = document.getElementById('noResults');
const resultCount  = document.getElementById('resultCount');

function applyFilters(){
    const search = searchInput.value.toLowerCase().trim();
    const type   = filterType.value;
    const status = filterStatus.value;
    const sort   = sortBy.value;

    let rows = Array.from(tbody.querySelectorAll('tr[data-name]'));

    
    rows.forEach(row => {
        const matchSearch = !search || row.dataset.name.includes(search);
        const matchType   = !type   || row.dataset.type === type;
        const matchStatus = !status || row.dataset.status === status;
        row.style.display = (matchSearch && matchType && matchStatus) ? '' : 'none';
    });

    
    if(sort){
        const visible = rows.filter(r => r.style.display !== 'none');
        visible.sort((a, b) => {
            if(sort === 'name-asc')  return a.dataset.name.localeCompare(b.dataset.name);
            if(sort === 'name-desc') return b.dataset.name.localeCompare(a.dataset.name);
            if(sort === 'date-asc')  return new Date(a.dataset.date) - new Date(b.dataset.date);
            if(sort === 'date-desc') return new Date(b.dataset.date) - new Date(a.dataset.date);
            return 0;
        });
        visible.forEach(r => tbody.appendChild(r));
    }

    
    tbody.querySelectorAll('tr[data-name] .col-name strong').forEach(el => {
        const orig = el.getAttribute('data-orig') || el.textContent;
        el.setAttribute('data-orig', orig);
        if(search){
            const regex = new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');
            el.innerHTML = orig.replace(regex, '<mark>$1</mark>');
        } else {
            el.textContent = orig;
        }
    });

    
    const visible = rows.filter(r => r.style.display !== 'none').length;
    noResults.style.display = visible === 0 ? 'block' : 'none';
    resultCount.textContent = `Showing ${visible} of ${rows.length}`;
}

function resetFilters(){
    searchInput.value  = '';
    filterType.value   = '';
    filterStatus.value = '';
    sortBy.value       = '';
    applyFilters();
}

searchInput.addEventListener('input', applyFilters);
filterType.addEventListener('change', applyFilters);
filterStatus.addEventListener('change', applyFilters);
sortBy.addEventListener('change', applyFilters);

applyFilters();
</script>

</body>
</html>