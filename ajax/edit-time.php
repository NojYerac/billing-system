<?php
include('../config.php');
include('../db.php');

session_start();
if (!isset($_SESSION['user_login']) || !isset($_SESSION['user_priv']) ||
    !in_array($_SESSION['user_priv'], array('Administrator', 'Employee'))) {
    http_response_code(401);
    echo "Not Authorized";
    exit();
}

if (!isset($_POST['csrf_token']) ||
    $_POST['csrf_token'] != $_SESSION['csrf_token']) {
    http_response_code(500);
    echo "CSRF check failed!";
    exit();
}

if (!isset($_POST['time_id']) || !isset($_POST['action'])) {
    http_response_code(500);
    echo "Missing required params";
    exit();
}

$time_id = $_POST['time_id'];
$action = $_POST['action'];

/*
 * TODO: add permision check!
 * employee can only change if time belongs to
 * customer in visible clients.
 */ 

switch ($action) {
    case "delete":
        $status = delete_one_document('timer', array(
            '_id' => (new MongoId($time_id))
            )
        );
            break;
    case "edit":
        $status = false;
    default:
        $status = false;
}

if ($status) {
    echo "success";
} else {
    echo "failed";
}
?>
