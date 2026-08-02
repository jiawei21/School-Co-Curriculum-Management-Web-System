<?php
session_start();
require("../database.php");

if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid = $_SESSION['userid'];
$username = $_SESSION['username'] ?? 'User'; 
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');

$clubId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($clubId==0){
    die("Invalid club ID");
}


$stmt = $con->prepare("SELECT club_id, club_name, club_description, club_status FROM club WHERE club_id=?");
$stmt->bind_param("i",$clubId);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
if(!$club){
    die("Club not found");
}


if($club['club_status'] === 'Reject'){
    echo "<script>
        alert('Access Denied: This club has been rejected by the administrator.');
        window.location.href = 'clubHandler.php';
    </script>";
    exit();
}


$stmt3 = $con->prepare("SELECT club_role, register_status FROM club_membership WHERE userid=? AND clubid=?");
$stmt3->bind_param("ii",$userid,$clubId);
$stmt3->execute();
$self = $stmt3->get_result()->fetch_assoc();
$isChair = ($self && $self['club_role'] === 'Chairperson');


if(isset($_POST['action']) && isset($_POST['membership_id'])){
    $mid = intval($_POST['membership_id']);
    $action = $_POST['action'];

    if($isChair){
        if($action == "approve"){
            $stmt = $con->prepare("UPDATE club_membership SET register_status='Approved' WHERE membership_Id=?");
        } else if($action == "reject"){
            $stmt = $con->prepare("UPDATE club_membership SET register_status='Rejected' WHERE membership_Id=?");
        }
        if(isset($stmt)){
            $stmt->bind_param("i",$mid);
            $stmt->execute();
        }
        
        header("Location: club.php?id=".$clubId);
        exit();
    }
}


$stmt2 = $con->prepare("
SELECT cm.membership_Id, cm.club_role, cm.register_status, u.username
FROM club_membership cm
JOIN user u ON cm.userid = u.id
WHERE cm.clubid=?
ORDER BY cm.register_status ASC, cm.club_role ASC
");
$stmt2->bind_param("i",$clubId);
$stmt2->execute();
$members = $stmt2->get_result();
$rows = [];
while($row = $members->fetch_assoc()) $rows[] = $row;

$pendingCount = count(array_filter($rows, fn($r) => $r['register_status'] === 'Pending'));
$approvedCount = count(array_filter($rows, fn($r) => $r['register_status'] === 'Approved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($club['club_name'], ENT_QUOTES, 'UTF-8'); ?> - Club Detail</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../design.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f4f6f9;min-height:100vh;display:flex;flex-direction:column;}


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
.dropdown-content a { color: #333; padding: 12px 16px; text-decoration: none; display: block; font-size: 13px; text-align: left;}
.dropdown-content a:hover { background-color: #f1f5f9; color: #1E3A8A; }
.dropdown:hover .dropdown-content { display: block; }


.page-wrapper{flex:1;max-width:960px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}
.page-header{margin-bottom:24px;}
.page-header h2{font-size:22px;font-weight:700;color:#1E3A8A;margin-bottom:6px;}

.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
.card-body{padding:20px 24px;}
.info-row{display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;font-size:14px;color:#444;}
.info-label{font-weight:600;color:#1E3A8A;min-width:100px;}

.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;}
.badge-active,.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-pending{background:#fef3c7;color:#D97706;}
.badge-rejected,.badge-inactive{background:#fee2e2;color:#dc2626;}

.member-list{list-style:none;padding:0;}
.member-item{display:flex;align-items:center;flex-wrap:wrap;gap:10px;padding:13px 24px;border-bottom:1px solid #f9f9f9;}
.member-avatar{width:36px;height:36px;border-radius:50%;background:#1E3A8A;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;}
.member-info{flex:1;min-width:120px;}
.member-name{font-size:14px;font-weight:600;color:#1a1a2e;}
.member-role-badge{display:inline-block;font-size:11px;padding:2px 8px;border-radius:20px;background:#e8edf8;color:#1E3A8A;font-weight:500;}

.btn-approve{padding:5px 12px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}
.btn-reject{padding:5px 12px;background:#dc2626;color:white;border:none;border-radius:6px;font-size:12px;cursor:pointer;}

.chair-actions{display:flex;gap:10px;flex-wrap:wrap;padding:20px 24px;}
.btn-action{display:inline-block;padding:9px 18px;background:#1E3A8A;color:white;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;}

.action-bar{display:flex;gap:10px;margin-top:10px;}
.btn-back{display:inline-block;padding:9px 18px;background:white;color:#1E3A8A;border:1.5px solid #1E3A8A;border-radius:7px;text-decoration:none;font-size:13px;font-weight:500;}

.management-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
@media(max-width: 600px) {
    .management-grid { grid-template-columns: 1fr; }
}

.manage-btn {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
    border: 1.5px solid transparent;
}

.manage-btn .icon {
    font-size: 24px;
    background: white;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.manage-btn .text {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.manage-btn strong {
    font-size: 14px;
    display: block;
}

.manage-btn small {
    font-size: 11px;
    opacity: 0.8;
}

/* 颜色区分 */
.btn-edit {
    background: #eff6ff;
    color: #1E3A8A;
}
.btn-edit:hover {
    background: #dbeafe;
    border-color: #1E3A8A;
}

.btn-members {
    background: #fefce8;
    color: #92400e;
}
.btn-members:hover {
    background: #fef9c3;
    border-color: #D97706;
}
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
            <div class="avatar-circle"><?php echo strtoupper(mb_substr($_SESSION['username'], 0, 1)); ?></div>
            <a href="../user/userprofile.php"><span><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span></a>
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
        <a href="clubHandler.php">Dashboard</a> &rsaquo;
        <?php echo htmlspecialchars($club['club_name'], ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <div class="page-header">
        <h2><?php echo htmlspecialchars($club['club_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Club Info</h3>
            <?php $statusClass = 'badge-' . strtolower($club['club_status']); ?>
            <span class="badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($club['club_status'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="info-row">
                <span class="info-label">Description</span>
                <span><?php echo htmlspecialchars($club['club_description'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Your Role</span>
                <span><?php echo htmlspecialchars($self['club_role'] ?? 'Member (Pending)', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Join Status</span>
                <?php
                    $joinStatus = $self['register_status'] ?? 'Pending';
                    $joinClass = 'badge-' . strtolower($joinStatus);
                ?>
                <span class="badge <?php echo htmlspecialchars($joinClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($joinStatus, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Members</h3>
            <span class="member-count">
                <?php echo $approvedCount; ?> approved
                <?php if($pendingCount > 0): ?>
                &nbsp;·&nbsp; <span style="color:#D97706;"><?php echo $pendingCount; ?> pending</span>
                <?php endif; ?>
            </span>
        </div>
        <ul class="member-list">
        <?php foreach($rows as $row):
            $initial = strtoupper(mb_substr($row['username'], 0, 1));
        ?>
            <li class="member-item">
                <div class="member-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="member-info">
                    <div class="member-name"><?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <span class="member-role-badge"><?php echo htmlspecialchars($row['club_role'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <?php
                    $rs = strtolower($row['register_status']);
                    $badgeClass = $rs === 'approved' ? 'badge-approved' : ($rs === 'pending' ? 'badge-pending' : 'badge-rejected');
                ?>
                <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars($row['register_status'], ENT_QUOTES, 'UTF-8'); ?>
                </span>

                <?php if($isChair && $row['register_status'] === 'Pending'): ?>
                <form method="POST" style="display:inline;margin-left:4px;">
                    <input type="hidden" name="membership_id" value="<?php echo $row['membership_Id']; ?>">
                    <button class="btn-approve" name="action" value="approve">Approve</button>
                    <button class="btn-reject"  name="action" value="reject">Reject</button>
                </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>

  <?php if($isChair): ?>
<div class="card" style="border-top: 4px solid #f59e0b;">
    <div class="card-header">
        <h3>🛠️ Club Management Tool</h3>
    </div>
    <div class="card-body" style="padding: 24px;">
        <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
            As the Chairperson, you have full control over the club details and its members.
        </p>
        <div class="management-grid">
            <a class="manage-btn btn-edit" href="editClub.php?id=<?php echo $clubId; ?>">
                <span class="icon">✏️</span>
                <div class="text">
                    <strong>Edit Details</strong>
                    <small>Update name & description</small>
                </div>
            </a>
            <a class="manage-btn btn-members" href="club_manageMember.php?id=<?php echo $clubId; ?>">
                <span class="icon">👥</span>
                <div class="text">
                    <strong>Manage Members</strong>
                    <small>Roles, kicks & transfers</small>
                </div>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
    <div class="action-bar">
        <a class="btn-back" href="clubHandler.php">← Back to Dashboard</a>
    </div>

</div>

<footer>
    © 2026 Uni Event Tracker
</footer>

</body>
</html>