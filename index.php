<?php
require_once('config.php');
require_once('db.php');
require_once('creds.php');
require_once('csrf.php');
require_once('comp.php');
//Session
session_start();
if (isset($_SESSION['user_name'])) {
    $privileges = $_SESSION['user_privileges'];
} else {
    http_response_code(302);
    header('Location: ' .  BASE_URL . 'login.php');
}

//Check csrf_token
$passed_csrf = false;

if (isset($_POST['csrf_token'])) {
    $passed_csrf = check_csrf_token($_POST['csrf_token']);
}
$_SESSION['csrf_token'] = $new_csrf_token = get_csrf_token();

//html header
echo get_default_head();

/*
 * header
 */

//body start







/*
 * footer
 */


?>
</body>
</html>
