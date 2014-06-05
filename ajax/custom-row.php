
<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');
require_once('../creds.php');

session_startup();

//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || 
	$_SESSION['user_priv'] != 'Administrator'
) {
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
) || die('CSRF check failed.') ;

if (
	!isset($_GET['action']) ||
	!isset($_POST['customer_id']) ||
	!isset($_POST['invoice_month']) ||
	!isset($_POST['quantity'] ||
	!isset($_POST['unit'] ||
	!isset($_POST['price']
) {
    http_response_code(500);
    echo "Missing required params";
    exit();
}

$param = $_POST;
if (!isset($param['project_name']) { $param['project_name'] = ''; }
if (!isset($param['notes']) { $param['notes'] = ''; }

$param['min_time'] = prepare_datetime((new DateTime($param['invoice_month'] . ' UTC'))->format('U');

//TODO: logic and db interaction for custom invoice rows

//create, edit or delete?
switch ($_GET['action']) {
case 'create+row':
	break;
case 'delete+row':
	break;
case 'edit+row':
	break;
default:
	die('Invalid action');
	break;
}



?>
