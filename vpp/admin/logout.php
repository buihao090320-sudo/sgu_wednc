<?php
// admin/logout.php
require_once '../includes/config.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: login.php');
exit;
