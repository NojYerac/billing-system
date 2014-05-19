<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');

session_start();

//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || 
    !in_array(
        $_SESSION['user_priv'],
        array('Administrator', 'Employee')
    )) {
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
);

if (!isset($_POST['customer_id']) || !isset($_POST['invoice_month'])) {
    http_response_code(500);
    echo "Missing required params";
    exit();
}
//($_REQUEST); echo '<hr/>';

$min_time = (new DateTime($_POST['invoice_month'] . ' UTC'));
$max_time = (clone $min_time);
$max_time->modify('last day of this month');
$max_time->setTime(23,59,59);
//($min_time); echo '<hr/>';
//($max_time); echo '<hr/>';
$billable_times = get_all_documents(
	'timer',
	array(
		'customer_id'=>$_POST['customer_id'],
		'start_time' => array(
			'$gte' => prepare_datetime($min_time),
			'$lte' => prepare_datetime($max_time)
		)
	)
);

//($billable_times); echo '<hr/>';

function new_line_item($project_id) {
	$project = get_one_document(
		'projects',
		array(
			'_id' => (new MongoId($project_id))
		)
	);
	return array(
		'project_name' => $project['project_name'],
		'notes' => $project['notes'],
		'price' => $project['price'],
		'quantity' => 0,
		'unit' => 'hour',
	);
}

function seconds_to_hours_rounded($seconds) {
	/*
	 * $quarter_hours = round($seconds / (60*15));
	 * $hours = $quarter_hours/4;
	 */
	return round($seconds/(60*15))/4;
}

$line_items = array();

foreach ($billable_times as $time) {
	$interval = ($time['stop_time']->sec) - ($time['start_time']->sec);
	if (!isset($line_items[$time['project_id']])) {
		$line_items[$time['project_id']] = new_line_item($time['project_id']);
	}
	$line_items[$time['project_id']]['quantity'] += $interval;
}

foreach ($line_items as $item) {
	$item['quantity'] = seconds_to_hours_rounded((float)$item['quantity']);
	echo $item['project_name'] . " => " . $item['quantity'] . '<br/>';
}

?>
