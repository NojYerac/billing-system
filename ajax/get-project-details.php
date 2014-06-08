<?php
require_once('../config.php');
require_once('../db.php');
require_once('../creds.php');
session_startup();

if (!isset($_SESSION['user_priv']) ||
	!in_array($_SESSION['user_priv'], array('Administrator', 'Employee'))) {
	http_response_code(401);
	echo 'Forbidden';
	exit();
}

if (isset($_GET['project_id'])) {
	if ($_GET['project_id'] == '') {
		echo '<><>';
		exit();
	}
	$param = array('_id' => (new MongoId($_GET['project_id'])));
} else {
	http_response_status(500);
	echo 'missing parameter';
	exit();
}

$doc = get_one_document('projects', $param);

echo htmlentities($doc['project_name']) . '<>' .
	htmlentities($doc['project_notes']) . '<>' .
	htmlentities($doc['project_price']);

?>
