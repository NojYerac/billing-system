<?php
require_once('config.php');
require_once('db.php');
//require_once('filter.php');
require_once('comp.php');
require_once('creds.php');
require_once('csrf.php');

session_start();
//prevent csrf
if (isset($_SESSION['csrf_token'])) {
    $old_csrf_token = $_SESSION['csrf_token'];
}

$_SESSION['csrf_token'] = $new_csrf_token = get_csrf_token();

$csrf_passed = (
    isset($old_csrf_token) &&
    isset($_POST['csrf_token']) &&
    $old_csrf_token == $_POST['csrf_token']
);
//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || $_SESSION['user_priv'] != 'Administrator') {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

//handle database changes...
if (isset($_GET['action']) && $csrf_passed) {
    switch($_GET['action']) {
    case 'add user':
        // add user logic
        if (add_user(
            $_POST['new_user_login'],
            $_POST['new_user_pass'],
            $_POST['verify_new_user_pass'],
            $_POST['new_user_priv'])
        ) {
            $status = "add user successful";
        } else {
            $status = "add user failed";
        }
        break;
    case 'add customer':
        if(add_customer(
            $_POST['new_customer_name'],
            $_POST['new_customer_rate'],
            $_POST['new_customer_address'],
			$_POST['new_customer_email'],
			$_POST['new_customer_prefix']
            )
        ) {
            $status = "add customer successful";
        } else {
            $status =  "add customer failed";
        }
        break;
/*    case 'new filter':
        echo 'new filter';
        // new filter logic
        break;
    case 'remove filter':
        echo 'remove filter';
        // remove filter logic
        break;*/
    default:
        die("<br/>invalid action: csrf_passed=" . $csrf_passed);
    }
}
//build the forms...
$admin_forms = array();

$csrf_input = inputify(
    'hidden',
    'csrf_token',
    array('value' => $new_csrf_token)
);

/*
$user_priv_options = tagify(array(
    'tag' => 'option',
    'innerHTML' => 'Privilege Level',
    'selected' => 'selected'
    ));


foreach (array('Administrator', 'Employee', 'Customer') as $priv) {
    $user_priv_options .= tagify(array(
        'tag'	=>	'option',
        'label'	=>	"user_priv_option_$priv",
        'id'	=>	"user_priv_option_$priv",
        'innerHTML'=>	$priv
    ));
}
 */

$add_user_form = formify(
    'POST', BASE_URL . '/admin.php?action=add+user',
    array(
        $csrf_input,
        inputify('text', 'new_user_login', array('label' => 'User name: ')), '<br/>',
        inputify('password', 'new_user_pass', array('label' => 'Password: ')), '<br/>',
        inputify('password',
            'verify_new_user_pass',
            array('label' => 'Verify Password: ')
        ), '<br/>',
/*    tagify(array(
        'tag'	=> 'lable',
        'for'	=> 'new_user_priv',
        'innerHTML'=> 'Privileges: '
    )),
    tagify(array(
        'tag'	=> 'select',
        'id'	=> 'new_user_priv',
        'name'	=> 'new_user_priv',
        'innerHTML'=> $user_priv_options
    )), '<br/>',
 */
        selectify('new_user_priv',
            array(
                'Administrator' => 'Administrator',
                'Employee' => 'Employee',
                'Customer' => 'Customer'
            ), 
            array('label' => 'Privileges: ')
        ), '<br/>',
        inputify('submit', 'submit', array('value' => 'Create User'))
    ),
    array('id' => 'add_user_form')
);

$admin_forms['add_user'] = array(
    'title' => 'Add user',
    'innerHTML' => $add_user_form
);

$add_customer_form = formify(
    'POST', BASE_URL . '/admin.php?action=add+customer',
    array(
        //inputs
	$csrf_input,
        inputify('text', 'new_customer_name', array(
            'label' => 'Name: ',
            'required' => 'required',
            )
		), '<br/>',
		inputify('text', 'new_customer_prefix', array(
			'label' => 'Invoice number prefix: ',
			'required' => 'required',
			'pattern' => '[A-Z0-9_-]+'
			)
		), '<br/>',
        inputify('number', 'new_customer_rate', array(
            'label' => 'Default rate: ',
            'required' => 'required',
            'value' => '35'
            )
        ), '<br/>',
        tagify(array(
            'tag' => 'label',
            'for' => 'new_customer_address',
            'innerHTML' => 'Address: '
            )
        ),
        tagify(array(
            'tag' => 'textarea',
            'id' => 'new_customer_address',
            'name' => 'new_customer_address',
            'innerHTML'=>'',
            'rows' => '5',
            'cols' => '40'
            )
        ), '<br/>',
        inputify('text', 'new_customer_email', array(
            'label' => 'Email: '
            )
        ), '<br/>',
        inputify('submit', 'submit_new_customer', array(
            'value' => 'Create customer'
            )
        )
    ),
    array(
        //addnl_attrs
        'autocomplete' => 'off'
    )
);

$admin_forms['add_customer'] = array(
    'title' => 'Add customer',
    'innerHTML' => $add_customer_form
);
/*
$new_filter_form = formify(
    'POST', BASE_URL . '/admin.php?action=new+filter',
    array(
        inputify('hidden', 'csrf_token', array('value' => $new_csrf_token)),
        inputify('text', 'new_filter_title', array('label' => 'Title: ', 'placeholder' => 'title')), '<br/>',
        inputify('text', 'new_filter_regex', array('label' => 'Regex: ', 'placeholder' => '/(.*)/')), '<br/>',
        inputify('text', 'new_filter_replacement', array('label' => 'Replacement: ', 'placeholder' => '$1')), '<br/>',
        tagify(
            array(
                'tag' => 'label',
                'id' => 'new_filter_note_label',
                'for' => 'new_filter_note',
                'innerHTML' => 'Note: '
            )
        ),
        tagify(
            array(
                'tag' => 'textarea',
                'id' => 'new_filter_note',
                'name' => 'new_filter_note',
                'innerHTML' => ''
            )
        ), '<br/>',
        inputify('submit', 'submit', array('value' => 'Create Filter'))
    ),
    array('enctype' => 'multipart/form-data', 'id' => 'new_filter_form')
);

$admin_forms['new_filter'] = array(
    'title' => 'New filter',
    'innerHTML' => $new_filter_form
);

$remove_filter_form = formify(
    'POST', BASE_URL . '/admin.php?action=remove+filter',
    array(
        inputify('hidden', 'csrf_token', array('value' => $new_csrf_token)),
        tagify(
            array(
                'tag' => 'select',
                'name' => 'remove_filter_id[]',
                'multiselect' => 'multiselect',
                'innerHTML' => get_filter_selections()
            )
        ), '<br/>',
        inputify('submit', 'submit', array('value' => 'Remove'))
    ),
    array('id' => 'remove_filter_form')
);

$admin_forms['remove_filter'] = array(
    'title' => 'Remove filter',
    'innerHTML' => $remove_filter_form
);
 */
$buttons = '';

$admin_forms_divs = '';

foreach ($admin_forms as $key => $value) {
    $buttons .= tagify(
        array(
            'tag' => 'button',
            'onclick' => "toggleVisible('${key}_div')",
            'innerHTML' => $value['title'],
            'class' => 'header_button'
        )
    );
    $admin_forms_divs .= tagify(
        array(
            'tag' => 'div',
            'id' => "${key}_div",
            'innerHTML' => $value['innerHTML'],
            'class' => 'admin_forms_div hidden feature-box'
        )
    );
}

$buttons .= '<a href="login.php?logout=true"><button>Logout</button></a>' .
        '<a href="emp.php"><button>Employee interface</button></a>';

$head = get_default_head();

$body = (
    tagify(
        array(
            'tag' => 'div',
            'id' => 'admin_header',
            'innerHTML' => $buttons
        )
    ) .
    '<h1>Admin Page</h1>' .
    $admin_forms_divs
);

if (isset($status)) {
    $body .= get_status_box($status);
}

echo get_document($head, $body, array());

?>
