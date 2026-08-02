<?php
session_start();
require('../database.php');

if(!isset($_SESSION['userid'])){
    header("Location: login.php");
    exit();
}

$userId  = $_SESSION['userid'];
$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');

$message = "";
$messageType = "";


if(isset($_SESSION['flash_message'])){
    $message     = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}


$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$canEdit = true;

$type = $name = $description = $date = $start = $end = $block = $hall = $image = "";

if($eventId > 0){

    if($isAdmin){
        $stmt = $con->prepare("SELECT * FROM event WHERE EventId=?");
        $stmt->bind_param("i", $eventId);
    } else {
        $stmt = $con->prepare("SELECT * FROM event WHERE EventId=? AND UserId=?");
        $stmt->bind_param("ii", $eventId, $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $type        = $row['EventType'];
        $name        = $row['EventName'];
        $description = $row['EventInfo'];
        $date        = $row['EventDate'];
        $start       = $row['EventStartTime'];
        $end         = $row['EventEndTime'];
        $block       = $row['EventBlock'];
        $hall        = $row['EventHall'];
        $image       = $row['EventImage'];

        if($row['EventStatus'] === 'Approved' && !$isAdmin){
            $canEdit = false;
            $message = "Approved event cannot be edited.";
            $messageType = "warning";
        }

    } else {
        die("Event not found / no permission.");
    }
}

if(isset($_POST['add']) && $canEdit){

    $type        = $_POST['EventType'];
    $name        = $_POST['EventName'];
    $description = $_POST['EventInfo'];
    $date        = $_POST['EventDate'];
    $start       = $_POST['EventStartTime'];
    $end         = $_POST['EventEndTime'];
    $block       = $_POST['EventBlock'];
    $hall        = $_POST['EventHall'];
    $register_date = date("Y-m-d H:i:s");

    
    $folder = $image;

    if(isset($_FILES['EventImage']) && $_FILES['EventImage']['name'] != ""){
        $imageName = $_FILES['EventImage']['name'];
        $folder = "image/" . $imageName;
        move_uploaded_file($_FILES['EventImage']['tmp_name'], $folder);
    }

    
    $check = $con->prepare("
        SELECT * FROM event
        WHERE EventDate=? AND EventBlock=? AND EventHall=?
        AND (
            (? BETWEEN EventStartTime AND EventEndTime)
            OR (? BETWEEN EventStartTime AND EventEndTime)
            OR (EventStartTime BETWEEN ? AND ?)
        )
        AND EventId != ?
    ");

    $check->bind_param(
        "sssssssi",
        $date,
        $block,
        $hall,
        $start,
        $end,
        $start,
        $end,
        $eventId
    );

    $check->execute();
    $conflict = $check->get_result();

    if($conflict->num_rows > 0 && $eventId == 0){
        $_SESSION['flash_message'] = "Hall already booked!";
        $_SESSION['flash_type'] = "error";
        header("Location: eventHandler.php");
        exit();
    }

    
    if($eventId > 0){

        if($isAdmin){
            $stmt = $con->prepare("
                UPDATE event SET
                EventType=?, EventName=?, EventInfo=?,
                EventDate=?, EventStartTime=?, EventEndTime=?,
                EventBlock=?, EventHall=?, EventImage=?,
                EventStatus='Pending'
                WHERE EventId=?
            ");

            $stmt->bind_param(
                "sssssssssi",
                $type,$name,$description,
                $date,$start,$end,
                $block,$hall,$folder,
                $eventId
            );

        } else {
            $stmt = $con->prepare("
                UPDATE event SET
                EventType=?, EventName=?, EventInfo=?,
                EventDate=?, EventStartTime=?, EventEndTime=?,
                EventBlock=?, EventHall=?, EventImage=?,
                EventStatus='Pending'
                WHERE EventId=? AND UserId=?
            ");

            $stmt->bind_param(
                "sssssssssi",
                $type,$name,$description,
                $date,$start,$end,
                $block,$hall,$folder,
                $eventId,$userId
            );
        }

        $stmt->execute();

        $_SESSION['flash_message'] = "Event updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: eventHandler.php");
        exit();
    }

    
    else {

        $stmt = $con->prepare("
            INSERT INTO event
            (EventType, EventName, EventInfo, EventDate,
             EventStartTime, EventEndTime, EventBlock, EventHall,
             EventStatus, Register_date, UserId, EventImage)
            VALUES (?,?,?,?,?,?,?,?,'Pending',?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssis",
            $type,$name,$description,$date,
            $start,$end,$block,$hall,
            $register_date,$userId,$folder
        );

        $stmt->execute();

        $_SESSION['flash_message'] = "Event created successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: eventHandler.php");
        exit();
    }
}


if(isset($_POST['delete']) && isset($_POST['EventId'])){

    if(!$isAdmin){
        $_SESSION['flash_message'] = "No permission to delete!";
        $_SESSION['flash_type'] = "error";
        header("Location: eventHandler.php");
        exit();
    }

    $id = intval($_POST['EventId']);

    $stmt = $con->prepare("DELETE FROM event WHERE EventId=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['flash_message'] = "Deleted successfully!";
    $_SESSION['flash_type'] = "success";
    header("Location: eventHandler.php");
    exit();
}

if($isAdmin){
    $events = $con->query("SELECT event.*, user.username FROM event JOIN user ON event.UserId = user.id ORDER BY EventId DESC");
} else {
    $stmt = $con->prepare("SELECT event.*, user.username FROM event JOIN user ON event.UserId = user.id WHERE event.UserId=? ORDER BY EventId DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $events = $stmt->get_result();
}

$eventRows = [];
while($r = $events->fetch_assoc()) $eventRows[] = $r;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $eventId > 0 ? 'Edit Event' : 'Create Event'; ?> - Uni Event Tracker</title>
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
.dropdown{position:relative;}
.dropdown-content{display:none;position:absolute;background:white;top:30px;border-radius:6px;box-shadow:0 5px 15px rgba(0,0,0,0.2);z-index:999;min-width:160px;}
.dropdown-content a{display:block;padding:10px 15px;color:#1E3A8A;font-size:13px;}
.dropdown-content a:hover{background:#f0f4ff;}
.dropdown:hover .dropdown-content{display:block;}
.user-menu{display:flex;align-items:center;gap:10px;}
.avatar{width:35px;height:35px;border-radius:50%;}

.page-wrapper{flex:1;max-width:1100px;margin:40px auto;padding:0 20px;width:100%;}
.breadcrumb{font-size:13px;color:#888;margin-bottom:10px;}
.breadcrumb a{color:#1E3A8A;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}
.page-header{margin-bottom:24px;}
.page-header h2{font-size:22px;font-weight:700;color:#1E3A8A;}
.page-header p{font-size:13px;color:#888;margin-top:4px;}

.layout-grid{display:grid;grid-template-columns:420px 1fr;gap:24px;align-items:start;}
@media(max-width:900px){.layout-grid{grid-template-columns:1fr;}}

.alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:20px;}
.alert-success{background:#dcfce7;color:#16a34a;}
.alert-warning{background:#fef9c3;color:#92400e;}
.alert-error{background:#fee2e2;color:#dc2626;}

.card{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 24px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:15px;font-weight:600;color:#1E3A8A;}
.card-body{padding:24px;}

.form-group{margin-bottom:16px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:#333;margin-bottom:6px;}
.form-group input,
.form-group select,
.form-group textarea{
    width:100%;padding:9px 12px;
    border:1.5px solid #dde3f0;border-radius:8px;
    font-family:'Poppins',sans-serif;font-size:13px;color:#333;
    transition:border 0.2s;background:white;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{outline:none;border-color:#1E3A8A;}
.form-group input:disabled,
.form-group select:disabled{background:#f9f9f9;color:#aaa;cursor:not-allowed;}
.form-group textarea{resize:vertical;}
.preview-img{width:100%;max-height:120px;object-fit:cover;border-radius:8px;margin-top:8px;}

.btn-submit{width:100%;padding:11px;background:#1E3A8A;color:white;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:background 0.2s;margin-top:4px;}
.btn-submit:hover{background:#163070;}
.btn-submit:disabled{background:#ccc;cursor:not-allowed;}

.filter-bar{background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.07);padding:14px 20px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.filter-bar input[type="text"]{flex:1;min-width:160px;padding:7px 12px;border:1.5px solid #dde3f0;border-radius:7px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;transition:border 0.2s;}
.filter-bar input[type="text"]:focus{outline:none;border-color:#1E3A8A;}
.filter-bar select{padding:7px 10px;border:1.5px solid #dde3f0;border-radius:7px;font-family:'Poppins',sans-serif;font-size:13px;color:#333;background:white;cursor:pointer;}
.filter-bar select:focus{outline:none;border-color:#1E3A8A;}
.btn-reset{padding:7px 14px;background:#f4f6f9;color:#555;border:1.5px solid #dde3f0;border-radius:7px;font-family:'Poppins',sans-serif;font-size:13px;cursor:pointer;transition:all 0.2s;}
.btn-reset:hover{background:#e8edf8;color:#1E3A8A;border-color:#1E3A8A;}
.result-count{font-size:12px;color:#888;margin-left:auto;white-space:nowrap;}

.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #f0f0f0;white-space:nowrap;}
td{padding:12px 16px;font-size:13px;color:#333;border-bottom:1px solid #f9f9f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafbff;}

.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.badge-pending{background:#fef9c3;color:#92400e;}
.badge-approved{background:#dcfce7;color:#16a34a;}
.badge-rejected{background:#fee2e2;color:#dc2626;}

.btn-edit-row{display:inline-block;padding:5px 12px;background:#1E3A8A;color:white;border-radius:6px;font-size:12px;text-decoration:none;margin-right:4px;transition:background 0.2s;}
.btn-edit-row:hover{background:#163070;}
.btn-delete-row{padding:5px 12px;background:#dc2626;color:white;border:none;border-radius:6px;font-size:12px;font-family:'Poppins',sans-serif;cursor:pointer;transition:background 0.2s;}
.btn-delete-row:hover{background:#b91c1c;}
.action-form{display:inline;}
.empty-row td{text-align:center;padding:30px;color:#aaa;font-size:13px;}
.no-results{text-align:center;padding:30px;color:#aaa;font-size:13px;display:none;}
mark{background:#fef08a;border-radius:3px;padding:0 2px;}

.toast{
    position:fixed;top:24px;right:24px;z-index:9999;
    padding:14px 20px;border-radius:10px;font-size:13px;font-weight:600;
    box-shadow:0 8px 24px rgba(0,0,0,0.15);
    display:flex;align-items:center;gap:10px;
    opacity:0;transform:translateY(-10px);
    transition:opacity 0.3s,transform 0.3s;
    pointer-events:none;min-width:260px;max-width:360px;
}
.toast.show{opacity:1;transform:translateY(0);}
.toast-success{background:#dcfce7;color:#16a34a;border-left:4px solid #16a34a;}
.toast-warning{background:#fef9c3;color:#92400e;border-left:4px solid #D97706;}
.toast-error{background:#fee2e2;color:#dc2626;border-left:4px solid #dc2626;}
.toast-icon{font-size:16px;flex-shrink:0;}

.modal-overlay{
    display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);
    z-index:9998;align-items:center;justify-content:center;
}
.modal-overlay.show{display:flex;}
.modal-box{
    background:white;border-radius:14px;padding:32px 28px;
    max-width:380px;width:90%;text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,0.2);
    animation:modalIn 0.2s ease;
}
@keyframes modalIn{from{transform:scale(0.92);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-icon{font-size:40px;margin-bottom:12px;}
.modal-title{font-size:17px;font-weight:700;color:#1a1a2e;margin-bottom:8px;}
.modal-desc{font-size:13px;color:#888;margin-bottom:24px;line-height:1.5;}
.modal-actions{display:flex;gap:10px;justify-content:center;}
.btn-modal-cancel{padding:9px 22px;background:#f4f6f9;color:#555;border:1.5px solid #dde3f0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;}
.btn-modal-cancel:hover{background:#e8edf8;}
.btn-modal-confirm{padding:9px 22px;background:#dc2626;color:white;border:none;border-radius:8px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s;}
.btn-modal-confirm:hover{background:#b91c1c;}

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
        <a href="index.php">Home</a> &rsaquo; <?php echo $eventId > 0 ? 'Edit Event' : 'Create Event'; ?>
    </div>

    <div class="page-header">
        <h2><?php echo $eventId > 0 ? 'Edit Event' : 'Create Event'; ?></h2>
        <p><?php echo $eventId > 0 ? 'Update the details below.' : 'Fill in the details to submit a new event for approval.'; ?></p>
    </div>

    <div class="layout-grid">
        <div>
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $eventId > 0 ? '✏️ Edit Event' : '➕ New Event'; ?></h3>
                    <?php if(!$canEdit): ?>
                    <span class="badge badge-approved">Approved</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                <?php $disabled = $canEdit ? "" : "disabled"; ?>
                
                <form id="editForm" method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Event Type</label>
                        <select name="EventType" <?php echo $disabled; ?>>
                            <?php foreach(["Competition","Sport","Workshop","Event","Service/Volunteer"] as $t){
                                $sel = ($type == $t) ? "selected" : "";
                                echo "<option value='$t' $sel>$t</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Event Name</label>
                        <input type="text" name="EventName" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter event name" <?php echo $disabled; ?> required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="EventInfo" rows="3" placeholder="Describe the event..." <?php echo $disabled; ?> required><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Event Date</label>
                            <input type="date" name="EventDate" value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $disabled; ?> required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="EventStartTime" value="<?php echo htmlspecialchars($start, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $disabled; ?> required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="EventEndTime" value="<?php echo htmlspecialchars($end, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $disabled; ?> required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Block</label>
                            <select name="EventBlock" id="block" onchange="updateHall()" <?php echo $disabled; ?> required>
                                <?php foreach(["A","B","C","D","E","F","G","H","I","K","L","M"] as $b){
                                    $sel = ($block == $b) ? "selected" : "";
                                    echo "<option value='$b' $sel>Block $b</option>";
                                } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hall / Classroom</label>
                            <select name="EventHall" id="hall" <?php echo $disabled; ?> required>
                                <option>Select block first</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Event Image</label>
                        <input type="file" name="EventImage" accept="image/*" <?php echo $disabled; ?>>
                        <?php if($image != ""): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" class="preview-img">
                        <?php endif; ?>
                    </div>

                    <?php if($eventId > 0 && $canEdit): ?>
                    <button type="button" class="btn-submit" onclick="openUpdateModal()">
                        💾 Update Event
                    </button>
                    <?php elseif($canEdit): ?>
                    <button type="submit" name="add" class="btn-submit">
                        🚀 Submit Event
                    </button>
                    <?php endif; ?>

                </form>
                </div>
            </div>
        </div>

        <div>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍 Search by name...">
                <select id="filterStatus">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                </select>
            </div>
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="eventTbody">
                            <?php foreach($eventRows as $row): ?>
                            <tr data-name="<?php echo strtolower($row['EventName']); ?>" data-status="<?php echo $row['EventStatus']; ?>">
                                <td class="col-name"><strong><?php echo htmlspecialchars($row['EventName']); ?></strong></td>
                                <td><span class="badge badge-<?php echo strtolower($row['EventStatus']); ?>"><?php echo $row['EventStatus']; ?></span></td>
                                <td>
                                    <a class="btn-edit-row" href="eventHandler.php?id=<?php echo $row['EventId']; ?>">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 Uni Event Tracker</footer>

<div class="toast" id="toast"><span class="toast-icon"></span><span class="toast-msg"></span></div>

<div class="modal-overlay" id="updateModal">
    <div class="modal-box">
        <div class="modal-icon">💾</div>
        <div class="modal-title">Save Changes?</div>
        <div class="modal-desc">Event status will be reset to <strong>Pending</strong>.</div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="closeUpdateModal()">Cancel</button>
            <button type="button" class="btn-modal-confirm" id="confirmUpdateBtn">Yes, Update</button>
        </div>
    </div>
</div>

<script>

function updateHall(){
    const b = document.getElementById("block").value;
    const hSelect = document.getElementById("hall");
    hSelect.innerHTML = "<option>General Hall</option>"; // 简化示例
}
updateHall();


function openUpdateModal(){ document.getElementById('updateModal').classList.add('show'); }
function closeUpdateModal(){ document.getElementById('updateModal').classList.remove('show'); }


document.getElementById('confirmUpdateBtn').addEventListener('click', function(){
    const form = document.getElementById('editForm'); // 精准通过 ID 找到表单
    
    
    if(!form.querySelector('input[name="add"]')){
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'add';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);
    }
    
    closeUpdateModal();
    form.submit(); 
});

// Toast
function showToast(msg, type='success'){
    const t = document.getElementById('toast');
    t.querySelector('.toast-msg').textContent = msg;
    t.className = `toast toast-${type} show`;
    setTimeout(() => t.classList.remove('show'), 3000);
}
<?php if($message) echo "showToast(".json_encode($message).", ".json_encode($messageType).");"; ?>
</script>

</body>
</html>