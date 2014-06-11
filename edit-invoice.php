<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('csrf.php');
require_once('creds.php');
session_startup();

////////////////////////////////////////PREVENT CSRF///////////////////////////

if (isset($_SESSION['csrf_token'])) {
    $old_csrf_token = $_SESSION['csrf_token'];
}

$_SESSION['csrf_token'] = $new_csrf_token = get_csrf_token();

$csrf_passed = (
    isset($old_csrf_token) &&
    isset($_POST['csrf_token']) &&
    $old_csrf_token == $_POST['csrf_token']
);

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

if (!isset($_SESSION['user_priv']) || $_SESSION['user_priv'] != 'Administrator') {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

if (!isset($_GET['invoice_id'])) {
	http_response_code(500);
	echo "Missing Parameters";
	exit();
}

$invoice = get_one_document('invoices', array('_id' => (new MongoId($_GET['invoice_id']))));

$min_time = date_create_from_format('U', $invoice['month']->sec);
$max_time = (clone $min_time);
$max_time->modify('last day of this month');
$max_time->setTime(23,59,59);
$customer_id = $_POST['customer_id'];
$invoice_rows = get_invoice_rows($invoice['customer_id'], $min_time, $max_time);

foreach ($invoice_rows as $row) {
	echo $row;
}

?>
