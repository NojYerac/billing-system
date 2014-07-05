<?php
require_once('config.php');
require_once('comp.php');
require_once('creds.php');

function get_login_form() {
    $inputs=array( 
        inputify(
            'text',
            'user_login',
            array('label' => 'User name: ', 'autofocus' => 'autofocus')
        ),  '<br/>',
        inputify(
            'password',
            'user_pass',
            array('label' => 'Password: ')
        ), '<br/>',
        inputify(
            'checkbox',
            'remember me',
            array('label' => 'Remember Me: ')
        ), '<br/>',
        inputify(
            'submit',
            'submit',
            array('value' => 'Login')
        ));
    return formify(
        'POST',
        BASE_URL . 'login.php',
        $inputs,
        array()
    );
}


$login_message = 'Please enter your credentials.';

if (isset($_GET['logout'])) {
	session_startup();
    if (isset($_COOKIE[session_name()])) {
		$session_params = session_get_cookie_params();
		setcookie(
			session_name(),
			'',
			time()-3600,
			$session_params['path'],
			$session_params['domain'],
			$session_params['secure'],
			$session_params['httponly']
		);
        //setcookie(session_name(), '', time()-3600, '/' );
    }
    $_SESSION = array();
    session_destroy();
    $login_message = 'You have sucessfully logged out.';
}

if (isset($_POST['user_login']) && isset($_POST['user_pass'])) {
    if (check_creds($_POST['user_login'], $_POST['user_pass'])) {
        if (isset($_POST['remember me'])) {
            $lifetime = 7 * 24 * 60 * 60 ; 
        } else {
            $lifetime = 0;
        }
		session_regenerate_id(true);
		session_startup($lifetime);
		$_SESSION['user_login'] = $_POST['user_login'];
		$_SESSION['user_priv'] = $user_priv = get_priv($_POST['user_login']);
		$_SESSION['visible_clients'] = get_visible_clients();
		http_response_code(302);
		switch ($user_priv) {
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
			$loc = '';
			break;
		}
		$dest =  BASE_URL . $loc;
		header('Location: ' . $dest);
		echo "<a href=\"$dest\">Redirect</a>" .
			"<script>window.location='$dest'</script>";
		exit();
    } else {
        $login_message = 'Failed login atempt';
    }
}

$login_message = '<h2 id="login_message">' . $login_message . '</h2>';
$login_form = '<div class="feature-box">' .
    $login_message . get_login_form() .
    '</div>';

$title = '<h1>Log in to ' . TITLE . '</h1>';

$body = get_body(
    $title .
    $login_form,
    array()
);

echo (get_document(get_default_head(), $body, array()));
