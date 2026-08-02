<?php
session_start();
require("../database.php");


if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php"); 
    exit();
}

$userid   = intval($_SESSION['userid']);
$username = $_SESSION['username'] ?? 'Admin';
$isAdmin  = true; 


if(isset($_POST['action']) && isset($_POST['aid'])){
    $aid = intval($_POST['aid']);
    $status = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';
    
    $stmt = $con->prepare("UPDATE achievement SET award_status = ? WHERE achievement_id = ?");
    $stmt->bind_param("si", $status, $aid);
    $stmt->execute();
    header("Location: admin_achievement.php?msg=success");
    exit();
}


$sql = "SELECT a.*, u.username FROM achievement a 
        JOIN user u ON a.userid = u.id 
        WHERE a.award_status = 'Pending' 
        ORDER BY a.issued_date DESC";
$pendingRes = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Achievements - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:#f4f6f9;color:#333;display:flex;flex-direction:column;min-height:100vh;}

        
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

        
        .page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
        .breadcrumb{font-size:13px;color:#888;margin-bottom:15px;}
        .breadcrumb a{color:#1E3A8A;text-decoration:none;}
        
        .page-header{margin-bottom:24px;}
        .page-header h2{font-size:26px;font-weight:700;color:#1E3A8A;}

        .ach-table{width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.07);}
        th, td{padding:18px 20px; text-align:left; border-bottom:1px solid #eee; font-size:14px;}
        th{background:#1E3A8A; color:white; font-weight:600; text-transform:uppercase; font-size:12px; letter-spacing:1px;}
        
        .btn{padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-weight:600; font-size:12px; transition: 0.2s;}
        .btn-approve{background:#dcfce7; color:#16a34a; margin-right:5px;}
        .btn-approve:hover{background:#bbf7d0;}
        .btn-reject{background:#fee2e2; color:#dc2626;}
        .btn-reject:hover{background:#fecaca;}
        
        .view-link{color:#1E3A8A; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:5px;}
        .view-link:hover{text-decoration:underline;}

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
            <a href="admin_dashboard.php">Admin Panel</a>
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
        <a href="admin_dashboard.php">Admin Dashboard</a> &rsaquo; Achievement Approvals
    </div>

    <div class="page-header">
        <h2>🏆 Achievement Approvals</h2>
    </div>

    <table class="ach-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Achievement Title</th>
                <th>Level</th>
                <th>Certificate</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($pendingRes->num_rows > 0): ?>
                <?php while($row = $pendingRes->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                    <td style="max-width:300px;"><?= htmlspecialchars($row['title']) ?></td>
                    <td><span style="color:#7c3aed; font-weight:600;"><?= htmlspecialchars($row['award_level']) ?></span></td>
                    <td>
                        <?php if($row['file_path']): ?>
                            <a href="../uploads/certificates/<?= $row['file_path'] ?>" target="_blank" class="view-link">📄 View File</a>
                        <?php else: ?>
                            <span style="color:#ccc;">No File</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="aid" value="<?= $row['achievement_id'] ?>">
                            <button name="action" value="approve" class="btn btn-approve">Approve</button>
                            <button name="action" value="reject" class="btn btn-reject">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#aaa; padding:60px;">
                        <div style="font-size:40px; margin-bottom:10px;">✅</div>
                        No pending achievements to review.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer>© 2026 Uni Event Tracker</footer>

</body>
</html>