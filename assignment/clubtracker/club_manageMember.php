<?php
session_start();
require("../database.php");


if(!isset($_SESSION['userid'])){
    header("Location: ../login.php");
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'User';
$isAdmin  = (strtolower($_SESSION['role'] ?? '') === 'admin');

$club_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($club_id == 0) die("Invalid Club ID");


$stmt = $con->prepare("
    SELECT c.club_name, cm.club_role 
    FROM club c
    LEFT JOIN club_membership cm ON cm.clubid = c.club_id AND cm.userid = ?
    WHERE c.club_id = ?
");
$stmt->bind_param("ii", $userid, $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if(!$club) die("Club not found.");


$isChair = ($club['club_role'] === 'Chairperson');
if(!$isChair && !$isAdmin) {
    die("Access Denied. Only the Chairperson or Admin can access this panel.");
}


if(isset($_POST['ajax_action'])){
    header('Content-Type: application/json');
    $mid = intval($_POST['membership_id']);
    $action = $_POST['action'];

    $stmtCheck = $con->prepare("SELECT userid FROM club_membership WHERE membership_Id=?");
    $stmtCheck->bind_param("i", $mid);
    $stmtCheck->execute();
    $target = $stmtCheck->get_result()->fetch_assoc();

    if(!$target) {
        echo json_encode(["status"=>"error","message"=>"Member record not found."]);
        exit();
    }

    if($action == "kick"){
        if($target['userid'] == $userid) {
            echo json_encode(["status"=>"error","message"=>"You cannot kick yourself!"]);
            exit();
        }
        $del = $con->prepare("DELETE FROM club_membership WHERE membership_Id=?");
        $del->bind_param("i", $mid);
        $del->execute();
        echo json_encode(["status"=>"success","message"=>"Member removed."]);
        exit();
    }

    if($action == "set_role"){
        $newRole = $_POST['new_role'];
        if($newRole === 'Chairperson'){
            $demote = $con->prepare("UPDATE club_membership SET club_role='Member' WHERE clubid=? AND club_role='Chairperson'");
            $demote->bind_param("i", $club_id);
            $demote->execute();

            $promote = $con->prepare("UPDATE club_membership SET club_role='Chairperson' WHERE membership_Id=?");
            $promote->bind_param("i", $mid);
            $promote->execute();

            echo json_encode(["status"=>"transfer","message"=>"Transfer complete!"]);
            exit();
        } else {
            $updRole = $con->prepare("UPDATE club_membership SET club_role=? WHERE membership_Id=?");
            $updRole->bind_param("si", $newRole, $mid);
            $updRole->execute();
            echo json_encode(["status"=>"success","message"=>"Role updated!"]);
            exit();
        }
    }
}


$memberStmt = $con->prepare("
    SELECT cm.membership_Id, cm.userid, cm.club_role, u.username
    FROM club_membership cm
    JOIN user u ON cm.userid = u.id
    WHERE cm.clubid = ? AND cm.register_status = 'Approved'
    ORDER BY FIELD(cm.club_role, 'Chairperson', 'Vice Chairperson', 'Secretary', 'Treasurer') ASC, u.username ASC
");
$memberStmt->bind_param("i", $club_id);
$memberStmt->execute();
$memberRes = $memberStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Members - Uni Event Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;color:#333;line-height:1.6;display:flex;flex-direction:column;min-height:100vh;}
        
        
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

        
        .page-wrapper{flex:1;max-width:900px;margin:40px auto;padding:0 20px;width:100%;}
        .breadcrumb{font-size:13px;color:#888;margin-bottom:15px;}
        .breadcrumb a{color:#1E3A8A;text-decoration:none;}
        
        .page-header{margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;}
        h2{color:#1E3A8A;font-size:24px; font-weight:700;}
        
        .card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.06);padding:20px;}
        
        .member-item{display:flex;align-items:center;justify-content:space-between;padding:15px;border-bottom:1px solid #f1f1f1;}
        .member-item:last-child{border-bottom:none;}
        
        .member-info strong{font-size:15px; color:#1a1a2e;}
        .member-role{font-size:11px;background:#e8edf8;color:#1E3A8A;padding:3px 10px;border-radius:20px;margin-left:8px;font-weight:600;}
        
        select{padding:8px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:13px; outline:none;}
        .btn-kick{background:#fee2e2;color:#dc2626;border:none;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;}
        
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
        <a href="clubHandler.php">Club Dashboard</a> &rsaquo; 
        <a href="clubDetail.php?id=<?= $club_id ?>"><?= htmlspecialchars($club['club_name']) ?></a> &rsaquo; 
        Manage Members
    </div>

    <div class="page-header">
        <h2>👥 Member Management</h2>
    </div>

    <div class="card">
        <div class="member-list">
            <?php while($m = $memberRes->fetch_assoc()): ?>
            <div class="member-item" id="row-<?= $m['membership_Id'] ?>">
                <div class="member-info">
                    <strong><?= htmlspecialchars($m['username']) ?></strong>
                    <span class="member-role"><?= $m['club_role'] ?></span>
                    <?php if($m['userid'] == $userid): ?> <small style="color:#888;">(You)</small> <?php endif; ?>
                </div>

                <?php if($m['userid'] != $userid): ?>
                <div style="display:flex;gap:12px;align-items:center;">
                    <select class="role-selector" data-mid="<?= $m['membership_Id'] ?>">
                        <option value="Member" <?= $m['club_role']=='Member'?'selected':'' ?>>Member</option>
                        <option value="Committee" <?= $m['club_role']=='Committee'?'selected':'' ?>>Committee</option>
                        <option value="Secretary" <?= $m['club_role']=='Secretary'?'selected':'' ?>>Secretary</option>
                        <option value="Treasurer" <?= $m['club_role']=='Treasurer'?'selected':'' ?>>Treasurer</option>
                        <option value="Vice Chairperson" <?= $m['club_role']=='Vice Chairperson'?'selected':'' ?>>Vice Chairperson</option>
                        <option value="Chairperson" <?= $m['club_role']=='Chairperson'?'selected':'' ?>>👑 Chairperson (Transfer)</option>
                    </select>
                    <button class="kick-btn btn-kick" data-mid="<?= $m['membership_Id'] ?>">Kick</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

<script>
$(document).ready(function(){
    
    
    $('.role-selector').on('change', function(){
        let selectElement = $(this);
        let mid = selectElement.data('mid');
        let newRole = selectElement.val();
        let isTransfer = (newRole === 'Chairperson');
        
        let confirmTitle = isTransfer ? "Transfer Ownership?" : "Change Role?";
        let confirmText = isTransfer 
            ? "🚨 WARNING: Transferring Chairperson status means you will LOSE management rights. Proceed?" 
            : "Change this user's role to " + newRole + "?";

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: isTransfer ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: '#1E3A8A',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, change it'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, send AJAX
                $.post(window.location.href, {
                    ajax_action: 1, action: 'set_role', membership_id: mid, new_role: newRole
                }, function(res){
                    if(res.status === 'transfer') {
                        Swal.fire('Transferred!', 'You are no longer the Chairperson.', 'success').then(() => {
                            window.location.href = "clubDetail.php?id=<?= $club_id ?>";
                        });
                    } else {
                        Swal.fire('Updated!', 'Role changed successfully.', 'success');
                    }
                }, 'json');
            } else {
                
                location.reload(); 
            }
        });
    });

    
    $('.kick-btn').on('click', function(){
        let mid = $(this).data('mid');
        
        Swal.fire({
            title: 'Remove member?',
            text: "Are you sure you want to kick this member out of the club?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove them'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(window.location.href, {
                    ajax_action: 1, action: 'kick', membership_id: mid
                }, function(res){
                    if(res.status === 'success') {
                        $('#row-'+mid).fadeOut();
                        Swal.fire('Removed!', 'The member has been kicked.', 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>

</body>
</html>