<?php
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: login.php");
    exit;
}

require_once "db.php";


if(!isset($_GET['artID']) || !is_numeric($_GET['artID'])){
    header("Location: dashboard.php");
    exit;
}


$artID = (int) $_GET['artID'];
$sql = "SELECT artID from artworks WHERE artID = ? AND userID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$artID, $_SESSION['userID']]);

if(!$stmt->fetch()){
    header("Location: dashboard.php");
    exit;
}


$sqlDEL = "DELETE from artworks WHERE artID = ?";
$stmtDEL = $conn->prepare($sqlDEL);
$stmtDEL->execute([$artID]);

header("Location: dashboard.php");
exit;
?>