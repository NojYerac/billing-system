<?php

function edit_invoice($invoice_id) {
	if (!isset($_POST['new_row'])) {
		return get_edit_invoice_interface($invoice_id);
	}
	foreach ($_POST['new_row'] as $new_row) {
		add_custom_invoice_row($invoice_id, $new_row);
	}
	regenerate_invoice_by_id($invoice_id);
	return get_invoice_li($invoice_id);
}

function get_edit_invoice_interface($invoice_id) {
	return "Place Holder";
}

function add_invoice_row($invoice_id, array $params) {
	if ($invoice_id != '') {
		$invoice_params = get_one_document(
			'invoices',
			array('_id' => (new MongoId($invoice_id)))
		);
	}
	foreach (array('invoice_id', 'csrf_token') as $param) {
		if (isset($params[$param])) {
			unset($params[$param]);
		}
	}
	foreach(array('customer_id', 'month') as $param) {
		if (!isset($params[$param]) && (isset($invoice_params[$param]))) {
			$params[$param] = $invoice_params[$param];
		}
	}
	if (!isset($params['notes']) || gettype($params['notes']) != 'string') {
		$params['notes'] = '';
	}
	if (isset($params['price']) && isset($params['quantity'])) {
		$params['sub_total'] = (float)$params['price'] * (float)$params['quantity'];
	} else {
		foreach (array('price', 'quantity', 'sub_total') as $param) {
			$params[$param] = 0;
		}
	}
	$id = insert_one_document(
		'custom_rows',
		$params
	);
	if ($invoice_id) {regenerate_invoice_by_id($invoice_id);}
	return get_invoice_row($params, "custom_$id");
}

function edit_invoice_row($invoice_id, array $params) {
	if (!isset($params['row_id'])) {
		return false;
	} else {
		$row_id = new MongoId($params['row_id']);
	}
	$invoice_params = get_one_document(
		'invoices',
		array('_id' => (new MongoId($invoice_id)))
	);
	foreach (array('invoice_id', 'csrf_token', 'row_id') as $param) {
		if (isset($params[$param])) {
			unset($params[$param]);
		}
	}
	foreach(array('customer_id', 'month') as $param) {
		if (!isset($params[$param]) && (isset($invoice_params[$param]))) {
			$params[$param] = $invoice_params[$param];
		}
	}
	if (!isset($params['notes']) || gettype($params['notes']) != 'string') {
		$params['notes'] = '';
	}
	if (isset($params['price']) && isset($params['quantity'])) {
		$params['sub_total'] = (float)$params['price'] * (float)$params['quantity'];
	} else {
		foreach (array('price', 'quantity', 'sub_total') as $param) {
			$params[$param] = 0;
		}
	}
	update_one_document(
		'custom_rows',
		array('_id' => $row_id),
		$params
	);
	regenerate_invoice_by_id($invoice_id);
	$params['_id'] = $row_id; //make sure we get the ondblclick attribute
	return get_invoice_row($params, "custom_$row_id");
}

function add_invoice_payment($invoice_id, $payment_params) {
	$payment_params['invoice_id'] = $invoice_id;
	$payment_params['date'] = new MongoDate($payment_params['date']);
	insert_one_document('payments', $payment_params);
	return get_payment_row($payment_params);
}

if(count(debug_backtrace()) == 0) {
	
	require_once('../config.php');
	require_once('../db.php');
	require_once('../comp.php');
	require_once('../creds.php');
	require_once('../pdf.php');

	session_startup();

	if (!isset($_SESSION['user_login']) || !isset($_SESSION['user_priv']) ||
	   $_SESSION['user_priv'] != 'Administrator') {
		http_response_code(401);
		echo "Not Authorized";
		exit();
	}

	if (!isset($_POST['csrf_token']) ||
		!isset($_SESSION['csrf_token']) ||
		$_POST['csrf_token'] != $_SESSION['csrf_token']) {
		http_response_code(500);
		echo "CSRF check failed!";
		exit();
	}

	if (!isset($_POST['invoice_id']) || !isset($_GET['action'])) {
		http_response_code(500);
		echo "Missing required params";
		exit();
	}

	$invoice_id = $_POST['invoice_id'];
	$action = $_GET['action'];


	switch ($action) {
		case "mark paid":
			$status = update_one_document('invoices',
				array('_id' => (new MongoId($invoice_id))),
				array('paid' => ($_POST['paid'] == "1"))
			);
			break;
		case "delete":
			$status = delete_one_document('invoices',
						array('_id' => (new MongoId($invoice_id)))
			);
			break;
		case "delete row":
			$status = delete_one_document(
				'custom_rows',
				array('_id' => (new MongoId($_POST['row_id'])))
			);
			regenerate_invoice_by_id($invoice_id);
		case "edit":
			$status = edit_invoice($invoice_id);
			break;
		case "add row":
			$status = add_invoice_row($invoice_id, $_POST);
			break;
		case "edit row":
			$status = edit_invoice_row($invoice_id, $_POST);
			break;
		case "add payment":
			$status = add_invoice_payment($invoice_id, $_POST);
			break;
		default:
			http_response_code(500);
			$status = "invalid action";
			break;
	}

	if ($status) {
		echo (gettype($status) == 'string')?$status:"success";
	} else {
		echo "failed";
	}

}
?>
