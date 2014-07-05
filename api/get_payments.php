<?php
require_once('../config.php');
require_once('../db.php');
require_once('../creds.php');
require_once('../assert.php');

session_startup();

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

if (!isset($_SESSION['user_priv']) ||
	$_SESSION['user_priv'] != 'Administrator') {
	http_response_code(401);
    echo 'Not Authorized';
    exit();
}

//////////////////////////HANDLE PARAMETERS/////////////////////////////////////

$params = array();

foreach($_REQUEST as $key => $value) {
	switch ($key) {
		case 'customer_id':
			assert(
				!isset($_REQUEST['invoice_id']) &&
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad customer id.'
			);
			$invoices = get_all_documents('invoices', array('customer_id' => $value));
			foreach ($invoices as $invoice) {
				$invoice_ids[] = (string)$invoice['_id'];
			}
			$params['invoice_id']['$in'] = $invoice_ids;
			break;
		case 'invoice_id':
			assert(
				!isset($_REQUEST['customer_id']) &&
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad invoice id.'
			);
			$params['invoice_id'] = $value;
			break;
		case 'payment_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad payment id.'
			);
			$params['_id'] = new MongoId($value);
			break;
		case 'min_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad min time value.'
			);
			$params['date']['$gte'] = (new MongoDate($value));
			break;
		case 'max_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad max time value.'
			);
			$params['date']['$lte'] = (new MongoDate($value));
			break;
		default:
			Die('Invalid parameter.');
	}
}

$result = json_encode(get_all_documents('payments', $params));
header('Content-type: application/json');
header('Content-length: ' . strlen($result));
echo $result;

?>
