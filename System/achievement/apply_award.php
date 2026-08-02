<?php
session_start();
require('../database.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../login.php");
    exit();
}

$userid = intval($_SESSION['userid']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $achievement_id = intval($_POST['achievement_id'] ?? 0);
    $award_level    = $_POST['award_level'] ?? '';
    $validLevels    = ['1st Place', '2nd Place', '3rd Place'];

    if (!in_array($award_level, $validLevels) || $achievement_id <= 0) {
        $_SESSION['flash_message'] = "Invalid award application.";
        $_SESSION['flash_type']    = "error";
        header("Location: achievement_dashboard.php");
        exit();
    }

    
    $stmt = $con->prepare("
        SELECT * FROM achievement
        WHERE achievement_id = ? AND userid = ? AND type = 'Certificate' AND event_type = 'Competition'
        AND award_status = 'None'
    ");
    $stmt->bind_param("ii", $achievement_id, $userid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $_SESSION['flash_message'] = "You cannot apply for this award.";
        $_SESSION['flash_type']    = "error";
        header("Location: achievement_dashboard.php");
        exit();
    }

    $level  = $con->real_escape_string($award_level);
    $upd = $con->prepare("
        UPDATE achievement
        SET award_level = ?, award_status = 'Pending'
        WHERE achievement_id = ? AND userid = ?
    ");
    $upd->bind_param("sii", $award_level, $achievement_id, $userid);

    if ($upd->execute()) {
        $_SESSION['flash_message'] = "✅ Award application submitted! Waiting for Admin approval.";
        $_SESSION['flash_type']    = "success";
    } else {
        $_SESSION['flash_message'] = "Error submitting application.";
        $_SESSION['flash_type']    = "error";
    }

    header("Location: achievement_dashboard.php");
    exit();
}

header("Location: achievement_dashboard.php");
exit();