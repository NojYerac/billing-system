<?php
require_once('../config.php');
require_once('../db.php');
require_once('../creds.php');
require_once('../assert.php');

session_startup();

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

if (!isset($_SESSION['user_priv']) ||
	$_SESSION['user_priv'] != 'Administrator'){
	http_response_code(401);
    echo 'Not Authorized';
    exit();
}

//////////////////////////HANDLE PARAMETERS/////////////////////////////////////

$params = array();

foreach($_REQUEST as $key => $value) {
	switch ($key) {
		case 'user_id':
			assert(
				is_string($value) &&
				preg_match('/^[A-Za-z0-9]{24}$/', $value),
				'Bad user id.'
			);
			$params['_id'] = new MongoId($value);
			break;
		case 'user_login':
			assert(
				is_string($value),
				'Bad user login.'
			);
			$params['user_login'] = $value;
			break;
		case 'user_privileges':
			assert(
				is_string($value) &&
				in_array($value, array('Administrator', 'Employee', 'Customer')),
				'Bad user privileges.'
			);
			$params['user_privileges'] = $value;
			break;
		default:
			Die('Invalid parameter.');
	}
}

$result = get_all_documents('users', $params);

for ( $i=0;$i<count($result);$i++) {
	unset($result[$i]['user_pass']);
}

header('Content-type: application/json');
header('Content-length: ' . strlen($result));
echo json_encode($result);

?>
