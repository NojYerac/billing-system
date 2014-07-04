<?php
require_once('../config.php');
require_once('../db.php');
require_once('../creds.php');
require_once('../assert.php');

session_startup();

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

if (!isset($_SESSION['user_priv']) ||
	$_SESSION['user_priv'] != 'Administrator') {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

//////////////////////////HANDLE PARAMETERS/////////////////////////////////////

$params = array();

foreach($_REQUEST as $key => $value) {
	switch ($key) {
		case 'customer_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad customer id.'
			);
			$params['customer_id'] = $value;
			break;
		case 'invoice_number':
			assert(
				is_string($value),
				'Bad invoice number.'
			);
			$params['invoice_number'] = $value;
			break;
		case 'invoice_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad invoice id.'
			);
			$params['_id'] = new MongoId($value);
			break;
		case 'min_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad min time value.'
			);
			if (!isset($params['month'])) {$params['month'] = array();}
			$params['month']['$gte'] = (new MongoDate($value));
			break;
		case 'max_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad max time value.'
			);
			$params['month']['$lte'] = (new MongoDate($value));
			break;
		case 'paid':
			assert(in_array($value, array('0', '1')));
			$params['paid'] = (bool)$value;
			break;
		case 'overdue':
			assert(in_array($value, array('0', '1')));
			$now = new MongoDate((new DateTime())->format('U'));
			if ((bool)$value) {
				$params['due']['$lt'] = $now;
			} else {
				$params['due']['$gte'] = $now;
			}
			break;
		default:
			Die('Invalid parameter.');
	}
}

$result = json_encode(get_all_documents('invoices', $params));
header('Content-type: application/json');
header('Content-length: ' . strlen($result));
echo $result;

?>
