<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');
require_once('../creds.php');

session_start();
//var_dump($_POST);
//prevent unauthorized access
if (!isset($_SESSION['user_priv']) ||
	!in_array(
		$_SESSION['user_priv'],
		array('Administrator', 'Employee', 'Customer')
	)
) {
    http_response_code(401);
    echo 'Not Authorized';
    exit();
}
if (isset($_POST['min_time']) &&
	$_POST['min_time'] != 'NaN'
) {
	$min_time = prepare_timestamp($_POST['min_time']);
} else {
	$min_time = prepare_timestamp('00000000000');
}

if (isset($_POST['max_time']) &&
	$_POST['max_time'] != 'NaN'
) {
	$max_time = prepare_timestamp($_POST['max_time']);
} else {
	$max_time = prepare_datetime(new DateTime());
}


$customers = get_visible_clients();
//...
$rows = '';
if (isset($_POST['customer_id']) &&
	$_POST['customer_id'] != ''
) {
	//check can_see_clients
	if (in_array($_POST['customer_id'], $customers)) {
		//return option tags
		$rows = get_time_rows_by_customer_and_datetime(
			$_POST['customer_id'],
			$min_time,
			$max_time
		);
	} else {
		http_response_code(403);
		echo 'Forbidden';
		exit();
	}
} else {
	foreach ($customers as $customer_name => $customer_id) {
		$rows .= get_time_rows_by_customer_and_datetime(
			(string)$customer_id,
			$min_time,
			$max_time
		); //echo $customer_id . "<br/>";
	}
}
echo $rows;
/*
if (!$rows) {
	echo "failed";
} else {
	echo $rows;
}
 */
?>	
