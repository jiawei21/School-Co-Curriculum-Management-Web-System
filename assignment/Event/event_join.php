<?php
session_start();
require('../database.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid  = intval($_SESSION['userid']);
$eventId = intval($_POST['event_id'] ?? 0);
$action  = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'event_search.php';

if ($eventId <= 0) {
    header("Location: $redirect");
    exit();
}

if ($action === 'join') {
   
    $check = $con->prepare("SELECT EventId FROM event WHERE EventId=? AND EventStatus='Approved'");
    $check->bind_param("i", $eventId);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $_SESSION['flash_message'] = "This event is no longer available.";
        $_SESSION['flash_type']    = "error";
        header("Location: $redirect");
        exit();
    }

   
    $stmt = $con->prepare("INSERT IGNORE INTO event_participant (userid, eventid, join_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $userid, $eventId);
    if ($stmt->execute() && $con->affected_rows > 0) {
        $_SESSION['flash_message'] = "✅ Successfully joined the event!";
        $_SESSION['flash_type']    = "success";
    } else {
        $_SESSION['flash_message'] = "You have already joined this event.";
        $_SESSION['flash_type']    = "warning";
    }

} elseif ($action === 'unjoin') {
    $stmt = $con->prepare("DELETE FROM event_participant WHERE userid=? AND eventid=?");
    $stmt->bind_param("ii", $userid, $eventId);
    $stmt->execute();
    $_SESSION['flash_message'] = "You have left the event.";
    $_SESSION['flash_type']    = "warning";
}

header("Location: $redirect");
exit();