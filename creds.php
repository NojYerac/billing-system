<?php 
require_once('db.php');
if (!function_exists('password_hash')) {
    require_once('password.php');
}

function get_priv($user_login) {
    $user_priv = get_one_value(
        'users',
        array('user_login' => $user_login),
        'user_privileges'
    );
    return $user_priv;
}

function check_creds($login, $pass) {
    $pass_result = get_one_value(
        'users',
        array('user_login' => $login),
        'user_pass'
    );
    if (password_verify($pass, $pass_result)) {
        return true;
    } else { 
        return false;
    }
}

function reset_creds($login, $old_pass, $new_pass) {
    if (!check_creds($login, $old_pass)) {
        return false;
    } else {
        set_creds($login, $new_pass);
        return true;
    }
}

function set_creds($login, $pass) {
    $hashed_pass = password_hash($pass, 1);
    update_one_value(
        'users',
        array('user_login' => $login),
        'user_pass', $hashed_pass
    );
    return true;
}

function password_is_acceptable($password) {
    if (preg_match(
        '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*\W).{8,255}$/',
        $password)) {
        return true;
    } else {
        return false;
    }
}

function add_user($user_login, $user_pass, $verify_pass, $user_priv) {
    if ($user_pass == $verify_pass && password_is_acceptable($user_pass)) {
        $hashed_pass = password_hash($user_pass, 1);
        echo "creating new user\n";
        insert_one_document(
            'users',
            array(
                'user_login' => $user_login,
                'user_pass' => $hashed_pass,
                'user_privileges' => $user_priv
            )
        );
        return true;
    } else {
        return false;
    }
}

function check_reqd_post_params($reqd_params) {
	foreach ($reqd_params as $param) {
		if (!isset($_POST[$param]) || $_POST[$param] == '') {
			return false;
		}
	}
	return true;
}

function get_visible_clients() {
    /*
     * Returns an associative array in the form:
     * customer_name => _id
     */
    $all_customers = get_all_documents('clients', array());
    $customers = array();
    switch ($_SESSION['user_priv']) {
    case 'Administrator':
        foreach ($all_customers as $doc) {
                $customers[htmlentities($doc['customer_name'])] = $doc['_id'];
        }
        break;
    case 'Employee' || 'Customer':
        $doc = get_one_value(
            'users',
            array('user_login' => $_SESSION['user_login']),
            'can_see_clients'
        );
        $can_see_clients = $doc['can_see_clients'];
        if (gettype($customers) == 'string') {
            $can_see_clients = split(', ', $customers);
	        }
        foreach ($all_customers as $doc) {
            if (in_array($doc['_id'], $can_see_clients)) {
                $customers[htmlentities($doc['customer_name'])] = $doc['_id'];
            } 
        }
        break;
    default:
        die("WTF!? How'd you get here?");
    }
    return $customers;
}

function add_customer($name, $rate, $address, $phone, $email, $invoice_prefix) {
    return insert_one_document('clients', array(
        'customer_name' => $name,
        'customer_rate' => floatval($rate),
		'customer_address' => $address,
		'customer_phone' => $phone,
        'customer_email' => $email,
		'invoice_prefix' => $invoice_prefix
        )
    );
}

function edit_customer($id, array $updates) {
    return update_one_document('clients', array(
        '_id' => (new MongoId($id))
        ),
        $updates
    );
}

function edit_company($name, $address, $phone, $email, $website) {
	return update_one_document('company_profile',
		array(),	
		array(
			'company_name' => $name,
			'company_address' => $address,
			'company_phone' => $phone,
			'company_email' => $email,
			'company_website' => $website
		)
	);
}

function session_startup($lifetime=0) {
		$URL = parse_url(BASE_URL);
		$path = $URL['path'];
		$secure = (($URL['scheme'] == 'https')?true:false);
		$domain = $URL['host']; //. (isset($URL['port'])?":".$URL['port']:'');
		session_set_cookie_params($lifetime, $path, $domain, $secure, true);
		session_start();
}

?>
