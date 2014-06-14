<?php
include('../config.php');
include('../db.php');
include('../comp.php');
require_once('../creds.php');

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
	$invoice_params = get_one_document(
		'invoices',
		array('_id' => (new MongoId($invoice_id)))
	);
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
	return get_invoice_row($params, "row_cust_$id");
}

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
		$status = updat_one_document('invoices',
			array('_id' => (new MongoId($invoice_id))),
			array('$set' => array('paid' => 'true'))
		);
		break;
    case "delete":
        $status = delete_one_document('invoices',
					array('_id' => (new MongoId($invoice_id)))
        );
		break;
    case "edit":
        $status = edit_invoice($invoice_id);
        break;
		case "add row":
		    $status = add_invoice_row($invoice_id, $_POST);
				break;
    default:
        $status = false;
        break;
}

if ($status) {
    echo (gettype($status) == 'string')?$status:"success";
} else {
    echo "failed";
}

?>
