<?php
session_start();
require("../database.php");


if(!isset($_SESSION['userid']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'Admin';
$isAdmin  = true;


if(isset($_POST['action']) && isset($_POST['id'])){
    $id = intval($_POST['id']);
    switch($_POST['action']){
        case 'approve':
            $s = $con->prepare("UPDATE club SET club_status='Available' WHERE club_id=?");
            break;
        case 'reject':
            $s = $con->prepare("UPDATE club SET club_status='Reject' WHERE club_id=?");
            break;
        case 'delete':
            $s = $con->prepare("DELETE FROM club WHERE club_id=?");
            break;
        case 'close': 
            $s = $con->prepare("UPDATE club SET club_status='Close' WHERE club_id=?");
            break;
        case 'open':  
            $s = $con->prepare("UPDATE club SET club_status='Available' WHERE club_id=?");
            break;
    }
    if(isset($s)){
        $s->bind_param("i",$id); $s->execute();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$clubs = $con->query("SELECT club_id, club_name, club_description, club_status, club_CreateDate FROM club ORDER BY club_CreateDate DESC");
$clubRows = [];
while($r = $clubs->fetch_assoc()) $clubRows[] = $r;

$totalClubs   = count($clubRows);
$pendingClubs = count(array_filter($clubRows, fn($r) => $r['club_status'] === 'Pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Clubs - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>

*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}
header{display:flex;justify-content:space-between;align-items:center;padding:15px 40px;background:#1E3A8A;color:white;}
.logo a{font-size:20px;font-weight:600;color:white;text-decoration:none;}
nav{display:flex;align-items:center;gap:20px;}
nav a{color:white;text-decoration:none;font-size:14px;}
nav a:hover{opacity:0.8;}


.dropdown { position: relative; display: inline-block; }
.dropdown-content {
    display: none; position: absolute; right: 0; top: 35px;
    background-color: white; min-width: 160px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
    z-index: 1000; border-radius: 8px; overflow: hidden;
}
.dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; text-align: left;}
.dropdown-content a:hover { background-color: #f1f5f9; color: #1E3A8A; }
.dropdown:hover .dropdown-content { display: block; }

.user-menu{display:flex;align-items:center;gap:10px;}
.avatar-circle{width:35px;height:35px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:white;}

.page-wrapper{flex:1;max-width:1200px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.page-header{margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.page-header h2{font-size:22px;font-weight:700;color:#1E3A8A;}
.count-badge{font-size:12px;background:#e8edf8;color:#1E3A8A;padding:4px 12px;border-radius:20px;font-weight:500;}

.filter-bar{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.filter-bar input[type="text"]{flex:1;min-width:180px;padding:8px 14px;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;}

.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;}
.card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{padding:12px 20px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;border-bottom:1px solid #f0f0f0;}
td{padding:13px 20px;font-size:13px;color:#333;border-bottom:1px solid #f9f9f9;}

.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-available{background:#dcfce7;color:#16a34a;}
.badge-reject{background:#fee2e2;color:#dc2626;}
.badge-close{background:#f3f4f6;color:#6b7280;}


.btn-approve{padding:5px 12px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}
.btn-reject{padding:5px 12px;background:#f59e0b;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}
.btn-delete{padding:5px 12px;background:#dc2626;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}
.btn-edit{display:inline-block;padding:5px 12px;background:#1E3A8A;color:white;border-radius:6px;font-size:12px;text-decoration:none;}

.btn-toggle{padding:5px 12px;background:#64748b;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}

.btn-back{display:inline-block;padding:9px 18px;background:white;color:#1E3A8A;border:1.5px solid #1E3A8A;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;}
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
        <a href="admin_dashboard.php">Admin Dashboard</a> &rsaquo; Manage Clubs
    </div>

    <div class="page-header">
        <h2>Manage Clubs</h2>
        <span class="count-badge"><?php echo $totalClubs; ?> total &nbsp;·&nbsp; <span style="color:#D97706;"><?php echo $pendingClubs; ?> pending</span></span>
    </div>

    <div class="filter-bar">
        <span class="filter-label">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by name...">
        <select id="filterStatus">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Available">Available</option>
            <option value="Reject">Rejected</option>
            <option value="Close">Closed</option>
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
        <div class="card-header"><h3>All Clubs</h3></div>
        <div class="table-wrap">
            <table id="clubTable">
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="clubTbody">
                <?php foreach($clubRows as $row):
                    $badgeClass = 'badge-'.strtolower($row['club_status']);
                    $dateFormatted = date("d M Y", strtotime($row['club_CreateDate']));
                ?>
                <tr data-name="<?php echo strtolower($row['club_name']); ?>" data-status="<?php echo $row['club_status']; ?>" data-date="<?php echo $row['club_CreateDate']; ?>">
                    <td><strong><?php echo htmlspecialchars($row['club_name']); ?></strong></td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($row['club_description']); ?></td>
                    <td><?php echo $dateFormatted; ?></td>
                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $row['club_status']; ?></span></td>
                    <td>
                        <?php if($row['club_status'] === 'Pending'): ?>
                            <form style="display:inline;" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['club_id']; ?>"><input type="hidden" name="action" value="approve">
                                <button class="btn-approve">Approve</button>
                            </form>
                            <form style="display:inline;" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['club_id']; ?>"><input type="hidden" name="action" value="reject">
                                <button class="btn-reject">Reject</button>
                            </form>
                        <?php endif; ?>

                        <?php if($row['club_status'] === 'Available'): ?>
                            <form style="display:inline;" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['club_id']; ?>"><input type="hidden" name="action" value="close">
                                <button class="btn-toggle" style="background:#475569;">Close Club</button>
                            </form>
                        <?php elseif($row['club_status'] === 'Close'): ?>
                            <form style="display:inline;" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['club_id']; ?>"><input type="hidden" name="action" value="open">
                                <button class="btn-toggle" style="background:#0ea5e9;">Open Club</button>
                            </form>
                        <?php endif; ?>

                        <?php if($row['club_status'] !== 'Reject'): ?>
                            <a class="btn-edit" href="../clubtracker/club_manageMember.php?id=<?php echo $row['club_id']; ?>">Member</a>
                            <a class="btn-edit" href="../clubtracker/editClub.php?id=<?php echo $row['club_id']; ?>">Edit</a>
                        <?php endif; ?>
                        
                        <form style="display:inline;" method="POST" onsubmit="deleteClubPrompt(event, this)">
                            <input type="hidden" name="id" value="<?php echo $row['club_id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <a class="btn-back" href="admin_dashboard.php">← Back to Dashboard</a>
</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
const searchInput = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const sortBy = document.getElementById('sortBy');
const tbody = document.getElementById('clubTbody');

function applyFilters(){
    const search = searchInput.value.toLowerCase();
    const status = filterStatus.value;
    const sort = sortBy.value;
    let rows = Array.from(tbody.querySelectorAll('tr'));

    rows.forEach(row => {
        const name = row.dataset.name;
        const rowSt = row.dataset.status;
        row.style.display = (name.includes(search) && (!status || rowSt === status)) ? '' : 'none';
    });
}
searchInput.addEventListener('input', applyFilters);
filterStatus.addEventListener('change', applyFilters);


function deleteClubPrompt(event, form) {
    event.preventDefault(); // Stop the form from submitting immediately
    
    Swal.fire({
        title: 'Delete this club?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit(); // If they click yes, submit the form manually
        }
    });
}
</script>

</body>
</html>