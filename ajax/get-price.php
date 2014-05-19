<?php
require_once('../config.php');
require_once('../db.php');

session_start();

if (!isset($_SESSION['user_priv']) ||
	!in_array($_SESSION['user_priv'], array('Administrator', 'Employee'))) {
	http_response_code(401);
	echo 'Not Authorized';
	exit();
}

if (!isset($_GET['customer_id'])) {
	http_response_code(500);
	echo 'Missing required param';
	exit();
}

$price = get_one_value(
	'clients',
	array(
		'_id' => (new MongoId($_GET['customer_id']))
	),
	'customer_rate'
);

echo $price;

?>
