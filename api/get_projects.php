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
	http_response_code(401);
    die('Not Authorized');
}

$params = array();
if ($_SESSION['user_priv'] != 'Administrator') {
	if(isset($REQUEST['client_id'])) {
		if (!in_array($REQUEST['client_id'], $_SESSION['visible_clients'])) {
			http_response_code('401');
			die('Not Authorized');
		}
	} else {
		$params['client_id'] = array('$in' => array_values($_SESSION['visible_clients']));
	}
}

//////////////////////////HANDLE PARAMETERS/////////////////////////////////////

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
			$params['_id'] = new MongoId($value);
			break;
		case 'project_name':
			assert(
				is_string($value),
				'Bad project name.'
			);
			$params['project_name'] = $value;
			break;
		case 'active':
			assert(in_array($value, array('0', '1')), 'Bad active value.');
			$params['active'] = (bool)$value;
			break;
		default:
			Die('Invalid parameter.');
	}
}

$result = json_encode(get_all_documents('projects', $params));
header('Content-type: application/json');
header('Content-length: ' . strlen($result));
echo $result;

?>
