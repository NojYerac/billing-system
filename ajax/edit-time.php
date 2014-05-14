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

function edit_time($time_id) {
    $set = array();
    $param_names = array('customer_id', 'project_id', 'start_time', 'stop_time');
    foreach ($param_names as $pn) {
        if (!isset($_POST[$pn]) && gettype($_POST[$pn]) != 'string') {
            echo "Failed param name check\n";
            return false;
        } else if (strpos($pn, 'time')) {
            $set[$pn] = (new DateTime())->setTimestamp($_POST[$pn]);
        } else {
            $set[$pn] = $_POST[$pn];
        }
    }
    $status = update_one_document('timer', array('_id' => (new MongoId($time_id))), $set);
    if ($status) {
        $customer_name = get_one_value(
            'clients',
            array('_id' => (new MongoId($set['customer_id']))),
            'customer_name'
        );
        $project_name = get_one_value(
            'projects',
            array('_id' => (new MongoId($set['project_id']))),
            'project_name'
        );
        if (!($project_name && $customer_name)) {
            echo "Failed cust/proj lookup\n";
            return false;
        }
        //TODO: create a function for td creation, put in comp.php,
        //similar functionality in emp.php
        $format = 'Y-m-d h:i:s';
        $diff_time = $set['start_time']->diff($set['stop_time']);
        $status = "<td value=\"${set['customer_id']}\">$customer_name</td>" .
            "<td value=\"${set['project_id']}\">$project_name</td>" .
            "<td>" . $set['start_time']->format($format) . "</td>" .
            "<td>" . $set['stop_time']->format($format) . "</td>" .
            "<td>" . $diff_time->format('%H:%I:%S') . "</td>" .
            "<td style=\"background-color:green\"" .
            " onclick=\"getEditTimeRow('$time_id')\"/>" .
            "<td style=\"background-color:red\"" .
            " onclick=\"if (confirm('Delete row?')) {deleteTime('$time_id')}\"/>";
    } else {
        echo "Update query failed\n";
    }
    return $status;
}

switch ($action) {
    case "delete":
        $status = delete_one_document('timer', array(
            '_id' => (new MongoId($time_id))
            )
        );
            break;
    case "edit":
        $status = edit_time($time_id);
        break;
    default:
        $status = false;
        break;
}

if ($status) {
    echo (gettype($status) == 'string')?$status:"success";
} else {
    echo "failed";
}
?>
