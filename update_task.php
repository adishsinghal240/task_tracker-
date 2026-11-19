<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: tasks.php'); exit(); }
$id=(int)($_POST['id']??0); if($id<=0){ header('Location: tasks.php'); exit(); }
$_GET['id']=$id; require 'edit_task.php';
?>
