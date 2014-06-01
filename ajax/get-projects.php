
<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');
require_once('../creds.php');

session_startup();

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

$customers = get_visible_clients();

//...
$options = '';
if (isset($_GET['custID'])) {
	//check can_see_clients
	if (in_array($_GET['custID'], $customers)) {
		//return option tags
		$options .= get_project_options($_GET['custID']);
	} else {
		http_response_code(403);
		echo 'Forbidden';
		exit();
	}
} else {
	foreach ($customers as $customer_name => $customer_id) {
		$options .= "<p>$customer_name</p>" .
			get_project_options($customer_id);
	}
}

if (!$options) {
	echo "<p>No projects found.</p>";
} else {
	echo $options;
}

?>	
