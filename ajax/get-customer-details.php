<?php
require_once('../config.php');
require_once('../db.php');
session_start();

if (!isset($_SESSION['user_priv']) ||
	$_SESSION['user_priv'] !=  'Administrator') {
	http_response_code(401);
	echo 'Forbidden';
	exit();
}

if (isset($_GET['customer_id'])) {
	if ($_GET['customer_id'] == '') {
		echo '<><><><>';
		exit();
	}
	$param = array('_id' => (new MongoId($_GET['customer_id'])));
} else {
	http_response_status(500);
	echo 'missing parameter';
	exit();
}

$doc = get_one_document('clients', $param);

echo htmlentities($doc['customer_name']) . '<>' .
	htmlentities($doc['invoice_prefix']) . '<>' .
	htmlentities($doc['customer_rate']) . '<>' .
	htmlentities($doc['customer_address']) . '<>' .
	htmlentities($doc['customer_email']);

?>
