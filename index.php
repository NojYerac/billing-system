<?php
require_once('config.php');
//require_once('db.php');
require_once('creds.php');
//require_once('csrf.php');
//require_once('comp.php');
//Session
session_startup();
	http_response_code(302);
if (isset($_SESSION['user_login'])) {
	switch ($_SESSION['user_priv']) {
	case 'Administrator':
		$loc = 'admin.php';
		break;
	case 'Customer':
		$loc = 'cust.php';
		break;
	case 'Employee':
		$loc = 'emp.php';
		break;
	default:
		$loc = 'login.php?logout=true';
		break;
    }
} else {
    $loc = 'login.php';
}
$dest =  BASE_URL . $loc;
header('Location: ' . $dest);
echo "<a href=\"$dest\">Redirect</a>" .
    "<script>window.location='$dest'</script>";
exit();

/*
http_response_code(302);
if (isset($_SESSION['user_login'])) {
    switch ($_SESSION['user_login']) {
    case "Administrator":
        header('Location: ' .  BASE_URL . 'admin.php');
        break;
    case "Employee":
        header('Location: ' .  BASE_URL . 'emp.php');
        break;
    case "Customer":
        header('Location: ' .  BASE_URL . 'cust.php');
        break;
    default:
        header('Location: ' .  BASE_URL . 'login.php?logout=true');
        break;
    }
} else {
    var_dump($_SESSION);
    //header('Location: ' .  BASE_URL . 'login.php');
}
/*
//Check csrf_token
$passed_csrf = false;

if (isset($_POST['csrf_token'])) {
    $passed_csrf = check_csrf_token($_POST['csrf_token']);
}
$_SESSION['csrf_token'] = $new_csrf_token = get_csrf_token();

//html header
$head = get_default_head();

/*
 * header
 */

//body start







/*
 * footer
 */


?>
