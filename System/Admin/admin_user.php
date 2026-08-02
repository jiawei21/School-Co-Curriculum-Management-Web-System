<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php"); exit();
}

$totalUsers  = $con->query("SELECT COUNT(*) FROM user WHERE role='student'")->fetch_row()[0];
$totalAdmins = $con->query("SELECT COUNT(*) FROM user WHERE role='admin'")->fetch_row()[0];

$users = $con->query("
    SELECT
        u.id, u.username, u.email, u.role, u.register_date,
        COUNT(DISTINCT cm.clubid)        AS clubs_joined,
        COUNT(DISTINCT ep.eventid)       AS events_joined,
        COUNT(DISTINCT m.merit_id)       AS merit_count,
        IFNULL(SUM(m.hours), 0)          AS total_hours,
        COUNT(DISTINCT a.achievement_id) AS achievement_count
    FROM user u
    LEFT JOIN club_membership cm       ON cm.userid = u.id AND cm.register_status = 'Approved'
    LEFT JOIN event_participant ep     ON ep.userid = u.id
    LEFT JOIN merit m                  ON m.userid  = u.id
    LEFT JOIN achievement a            ON a.userid  = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.register_date DESC
");
$userRows = [];
while($r = $users->fetch_assoc()) $userRows[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
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
.breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}
.page-header{margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.page-header h2{font-size:22px;font-weight:700;color:#1E3A8A;}
.count-badge{font-size:12px;background:#e8edf8;color:#1E3A8A;padding:4px 12px;border-radius:20px;font-weight:500;}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:20px;}
.stat-card{background:white;border-radius:12px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;align-items:center;gap:14px;}
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.stat-icon.blue{background:#e8edf8;} .stat-icon.green{background:#dcfce7;}
.stat-value{font-size:20px;font-weight:700;color:#1E3A8A;}
.stat-label{font-size:11px;color:#888;margin-top:2px;}
.filter-bar{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:14px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.filter-bar input[type="text"]{flex:1;min-width:180px;padding:8px 14px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;}
.filter-bar input[type="text"]:focus{outline:none;border-color:#1E3A8A;}
.filter-bar select{padding:8px 12px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;background:white;cursor:pointer;}
.filter-bar select:focus{outline:none;border-color:#1E3A8A;}
.btn-reset{padding:8px 16px;background:#f4f6f9;color:#555;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;}
.btn-reset:hover{background:#e8edf8;color:#1E3A8A;border-color:#1E3A8A;}
.result-count{font-size:12px;color:#888;margin-left:auto;}
.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;}
.card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #f0f0f0;white-space:nowrap;}
td{padding:12px 16px;font-size:13px;color:#333;border-bottom:1px solid #f9f9f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafbff;}
.user-avatar{width:34px;height:34px;border-radius:50%;background:#dbeafe;color:#1E3A8A;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;margin-right:8px;flex-shrink:0;}
.user-cell{display:flex;align-items:center;}
.mini-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.pill-blue{background:#dbeafe;color:#1E3A8A;}
.pill-green{background:#dcfce7;color:#16a34a;}
.pill-purple{background:#f3e8ff;color:#7c3aed;}
.btn-view{display:inline-block;padding:5px 14px;background:#1E3A8A;color:white;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;transition:background 0.2s;}
.btn-view:hover{background:#163070;}
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
    <div class="breadcrumb"><a href="admin_dashboard.php">Admin Dashboard</a> › Manage Users</div>
    <div class="page-header">
        <h2>👥 Manage Users</h2>
        <span class="count-badge"><?= $totalUsers ?> students &nbsp;·&nbsp; <?= $totalAdmins ?> admins</span>
    </div>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-icon blue">👨‍🎓</div><div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Students</div></div></div>
        <div class="stat-card"><div class="stat-icon green">🛡️</div><div><div class="stat-value"><?= $totalAdmins ?></div><div class="stat-label">Admins</div></div></div>
    </div>

    <div class="filter-bar">
        <span style="font-size:14px;color:#aaa;">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by name or email...">
        <select id="sortBy">
            <option value="">Sort by...</option>
            <option value="name-asc">Name A → Z</option>
            <option value="name-desc">Name Z → A</option>
            <option value="date-desc">Newest first</option>
            <option value="date-asc">Oldest first</option>
            <option value="hours-desc">Most Hours</option>
        </select>
        <button class="btn-reset" onclick="resetFilters()">✕ Reset</button>
        <span class="result-count" id="resultCount"></span>
    </div>

    <div class="card">
        <div class="card-header"><h3>All Students</h3></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Clubs</th>
                        <th>Events Joined</th>
                        <th>Merit Hours</th>
                        <th>Achievements</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTbody">
                <?php if(empty($userRows)): ?>
                    <tr class="empty-row"><td colspan="8">No students found.</td></tr>
                <?php else: ?>
                <?php foreach($userRows as $i => $row): ?>
                <tr data-name="<?= htmlspecialchars(strtolower($row['username']),ENT_QUOTES,'UTF-8') ?>"
                    data-email="<?= htmlspecialchars(strtolower($row['email']),ENT_QUOTES,'UTF-8') ?>"
                    data-date="<?= $row['register_date'] ?>"
                    data-hours="<?= $row['total_hours'] ?>">
                    <td><?= $i+1 ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar"><?= strtoupper(mb_substr($row['username'],0,1)) ?></div>
                            <strong class="col-name"><?= htmlspecialchars($row['username'],ENT_QUOTES,'UTF-8') ?></strong>
                        </div>
                    </td>
                    <td style="color:#888"><?= htmlspecialchars($row['email'],ENT_QUOTES,'UTF-8') ?></td>
                    <td><?= date('d M Y', strtotime($row['register_date'])) ?></td>
                    <td><span class="mini-pill pill-green">🏫 <?= $row['clubs_joined'] ?></span></td>
                    <td><span class="mini-pill pill-blue">📅 <?= $row['events_joined'] ?></span></td>
                    <td><span class="mini-pill pill-green">⏱️ <?= round($row['total_hours'],1) ?>h</span></td>
                    <td><span class="mini-pill pill-purple">🏆 <?= $row['achievement_count'] ?></span></td>
                    <td><a class="btn-view" href="admin_viewuser.php?id=<?= $row['id'] ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="no-results" id="noResults">😕 No users match your search.</div>
        </div>
    </div>
    <a class="btn-back" href="admin_dashboard.php">← Back to Dashboard</a>
</div>
<footer>© 2026 Uni Event Tracker</footer>
<script>
const searchInput=document.getElementById('searchInput'),sortBy=document.getElementById('sortBy'),tbody=document.getElementById('userTbody'),noResults=document.getElementById('noResults'),resultCount=document.getElementById('resultCount');
function applyFilters(){
    const search=searchInput.value.toLowerCase().trim(),sort=sortBy.value;
    let rows=Array.from(tbody.querySelectorAll('tr[data-name]'));
    rows.forEach(r=>{r.style.display=(!search||r.dataset.name.includes(search)||r.dataset.email.includes(search))?'':'none';});
    if(sort){const vis=rows.filter(r=>r.style.display!=='none');vis.sort((a,b)=>{if(sort==='name-asc')return a.dataset.name.localeCompare(b.dataset.name);if(sort==='name-desc')return b.dataset.name.localeCompare(a.dataset.name);if(sort==='date-asc')return new Date(a.dataset.date)-new Date(b.dataset.date);if(sort==='date-desc')return new Date(b.dataset.date)-new Date(a.dataset.date);if(sort==='hours-desc')return parseFloat(b.dataset.hours)-parseFloat(a.dataset.hours);return 0;});vis.forEach(r=>tbody.appendChild(r));}
    tbody.querySelectorAll('tr[data-name] .col-name').forEach(el=>{const orig=el.getAttribute('data-orig')||el.textContent;el.setAttribute('data-orig',orig);if(search){const rx=new RegExp(`(${search.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');el.innerHTML=orig.replace(rx,'<mark>$1</mark>');}else{el.textContent=orig;}});
    const vis=rows.filter(r=>r.style.display!=='none').length;noResults.style.display=(vis===0&&rows.length>0)?'block':'none';resultCount.textContent=`Showing ${vis} of ${rows.length}`;
}
function resetFilters(){searchInput.value='';sortBy.value='';applyFilters();}
searchInput.addEventListener('input',applyFilters);sortBy.addEventListener('change',applyFilters);applyFilters();
</script>
</body>
</html>