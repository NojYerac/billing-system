<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');
require_once('../creds.php');
require_once('../pdf.php');

session_startup();

//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || 
    $_SESSION['user_priv'] != 'Administrator'
    ) {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

//prevent csrf
$csrf_passed = (
    isset($_SESSION['csrf_token']) &&
    isset($_POST['csrf_token']) &&
    $_SESSION['csrf_token'] == $_POST['csrf_token']
) || die("CSRF check failed!");

if (!isset($_POST['customer_id']) || !isset($_POST['invoice_month'])) {
    http_response_code(500);
    echo "Missing required params";
    exit();
}

$min_time = (new DateTime($_POST['invoice_month'] . ' UTC'));
$max_time = (clone $min_time);
$max_time->modify('last day of this month');
$max_time->setTime(23,59,59);
$customer_id = $_POST['customer_id'];

$params = generate_invoice($customer_id, $min_time, $max_time);

echo get_invoice_link($params);
exit();
?>
