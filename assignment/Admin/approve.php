<?php
session_start();
require_once '../database.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    die("Access Denied");
}

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $con->query("UPDATE event SET EventStatus='Approved' WHERE EventID='$id'");
}

header("Location: admin.php");
exit();