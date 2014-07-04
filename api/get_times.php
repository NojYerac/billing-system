<?php
require_once('../config.php');
require_once('../db.php');
require_once('../creds.php');
require_once('../assert.php');

session_startup();

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

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
		case 'project_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad project id.'
			);
			$params['project_id'] = $value;
			break;
		case 'time_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad time id.'
			);
			$params['_id'] = new MongoId($value);
			break;
		case 'min_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad min time value.'
			);
			$params['min_time'] = array('$gte' => (new MongoDate($value)));
			break;
		case 'max_time':
			assert(
				is_string($value) &&
				preg_match('/\d{10}/', $value), 'Bad max time value.'
			);
			$params['max_time'] = array('$lte' => (new MongoDate($value)));
			break;
		default:
			Die('Invalid parameter.');
	}
}

$result = json_encode(get_all_documents('timer', $params));
header('Content-type: application/json');
header('Content-length: ' . strlen($result));
echo $result;

?>
