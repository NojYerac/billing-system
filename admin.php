<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('creds.php');
require_once('csrf.php');

session_startup();

////////////////////////////////////////PREVENT CSRF///////////////////////////

if (isset($_SESSION['csrf_token'])) {
    $old_csrf_token = $_SESSION['csrf_token'];
}

$_SESSION['csrf_token'] = $new_csrf_token = get_csrf_token();

$csrf_passed = (
    isset($old_csrf_token) &&
    isset($_POST['csrf_token']) &&
    $old_csrf_token == $_POST['csrf_token']
);

////////////////////////////////////////PRVENT UNAUTHORIZED ACCESS/////////////

if (!isset($_SESSION['user_priv']) || $_SESSION['user_priv'] != 'Administrator') {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

////////////////////////////////////////HANDLE DATABASE CHANGES////////////////

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
        if (add_customer(
            $_POST['new_customer_name'],
            $_POST['new_customer_rate'],
			$_POST['new_customer_address'],
			$_POST['new_customer_phone'],
			$_POST['new_customer_email'],
			$_POST['new_customer_prefix']
            )
        ) {
            $status = "add customer successful";
        } else {
            $status =  "add customer failed";
        }
		break;
    case 'edit customer':
		if (edit_customer(
			$_POST['customer_id'],
			array(
				'customer_name' => $_POST['edit_customer_name'],
				'customer_rate' => $_POST['edit_customer_rate'],
				'customer_address' => $_POST['edit_customer_address'],
				'customer_phone' => $_POST['edit_customer_phone'],
				'customer_email' => $_POST['edit_customer_email'],
				'invoice_prefix' => $_POST['edit_customer_prefix']
				)
            )
        ) {
            $status = "edit customer successful";
        } else {
            $status =  "edit customer failed";
        }
		break;
	case 'edit company':
		if (edit_company(
			$_POST['company_name'],
			$_POST['company_address'],
			$_POST['company_phone'],
			$_POST['company_email'],
			$_POST['company_website']
			)
		) {
            $status = "edit company profile successful";
        } else {
            $status =  "edit company profile failed";
        }
		break;
	default:
        die("<br/>invalid action: csrf_passed=" . $csrf_passed);
    }
}

////////////////////////////////////////BUILD FORMS////////////////////////////

$admin_forms = array();

$csrf_input = inputify(
    'hidden',
    'csrf_token',
    array('value' => $new_csrf_token)
);

////////////////////////////////////////EDIT COMPANY////////////////////////////

$company_profile = get_one_document('company_profile', array());
if (!$company_profile) {
	$company_profile = array(
		'company_name' => 'Name',
		'company_address' => 'Address',
		'company_phone' => 'Phone',
		'company_email' => 'Email',
		'company_website' => 'Website'
	);
	insert_one_document('company_profile', $company_profile);
}

$edit_company_inputs =
$edit_company_form = formify(
	'POST' , BASE_URL . 'admin.php?action=edit+company',
	array(
		$csrf_input,
		inputify('text', 'company_name', array(
			'label' => 'Name: ',
			'required' => 'required',
			'value' => htmlentities($company_profile['company_name'])
			)
		) , '<br/>' ,
		textareaify('company_address', array(
			'innerHTML' => htmlentities($company_profile['company_address']),
			'rows' => '4',
			'cols' => '30',
			'label' => 'Address: '
			)
		), '<br/>',
/*
		'<div class="input_container">',
		tagify(array(
			'tag' => 'label',
			'for' => 'company_address',
			'innerHTML' => 'Address: '
			)
		),
		tagify(array(
			'tag' => 'textarea',
			'id' => 'company_address',
			'name' => 'company_address',
			'innerHTML' => htmlentities($company_profile['company_address']),
			'rows' => '5',
			'cols' => '40'
			)
		),'</div>', '<br/>',
 */
		inputify('text', 'company_phone', array(
			'label' => 'Phone: ',
			'required' => 'required',
			'value' => htmlentities($company_profile['company_phone'])
			)
		) , '<br/>' ,
		inputify('text', 'company_email', array(
			'label' => 'Email: ',
			'required' => 'required',
			'value' => htmlentities($company_profile['company_email'])
			)
		) , '<br/>' ,
		inputify('text', 'company_website', array(
			'label' => 'Website: ',
			'required' => 'required',
			'value' => htmlentities($company_profile['company_website'])
			)
		) , '<br/>',
		inputify('submit', 'submit', array('value' => 'Edit company profile'))
	),
	array('id' => 'edit_company_form')
);

$admin_forms['edit_company'] = array(
	'title' => 'Edit company profile',
	'innerHTML' => $edit_company_form
);

////////////////////////////////////////NEW USER///////////////////////////////

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

//edit user
////////////////////////////////////////EDIT USER//////////////////////////////
//TODO

////////////////////////////////////////ADD CUSTOMER///////////////////////////

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
			'label' => 'Invoice prefix: ',
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
		textareaify ('new_customer_address', array(
			'rows' => '5',
			'cols' => '20',
			'label' => 'Address: '
			)
		), '<br/>',
/*
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
 */
		inputify('text', 'new_customer_phone', array(
            'label' => 'Phone: '
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

//edit customer

$edit_customer_form = formify(
	'POST', BASE_URL . '/admin.php?action=edit+customer',
    array(
        //inputs
		$csrf_input,
		get_customer_selector(
			array(
				'required' => 'required',
				'name' => 'customer_id',
				'onchange' => 'fillCustomerDetails(\'edit_\')'
			), 'edit_'
		),
		'<br/>',
		inputify('text', 'edit_customer_name', array(
			'label' => 'Name: ',
			'required' => 'required'
			)
		), '<br/>',
		inputify('text', 'edit_customer_prefix', array(
			'label' => 'Invoice prefix: ',
			'required' => 'required',
			'pattern' => '[A-Z0-9_-]+'
			)
		), '<br/>',
        inputify('number', 'edit_customer_rate', array(
            'label' => 'Default rate: ',
            'required' => 'required'
            )
        ), '<br/>',
		textareaify ('edit_customer_address', array(
			'rows' => '5',
			'cols' => '20',
			'label' => 'Address: '
			)
		), '<br/>',
/*
        tagify(array(
            'tag' => 'label',
            'for' => 'edit_customer_address',
            'innerHTML' => 'Address: '
            )
        ),
        tagify(array(
            'tag' => 'textarea',
            'id' => 'edit_customer_address',
            'name' => 'edit_customer_address',
            'innerHTML'=>'',
            'rows' => '5',
            'cols' => '40'
            )
        ), '<br/>',
 */
		inputify('text', 'edit_customer_phone', array(
            'label' => 'Phone: '
            )
        ), '<br/>',
        inputify('text', 'edit_customer_email', array(
            'label' => 'Email: '
            )
        ), '<br/>',
        inputify('submit', 'submit_edit_customer', array(
            'value' => 'Edit customer'
            )
        )
    ),
    array(
        //addnl_attrs
        'autocomplete' => 'off'
    )
);


$admin_forms['edit_customer'] = array(
	'title' => 'Edit customer',
	'innerHTML' => $edit_customer_form
);
//define invoicing

////define invoice list.

function get_invoice_links() {
	$invoice_links = '<ul id="invoice_list_ul">';
	$invoices = get_all_documents('invoices', array());
	foreach ($invoices as $invoice) {
		$invoice_links .= get_invoice_link($invoice);
	}
	$invoice_links .= '</ul>';
	return $invoice_links;
}

$invoice_list = tagify(
	array(
		'tag' => 'div',
		'id' => 'invoice_list_div',
		'innerHTML' => get_invoice_links()
	)
);

//define compose invoice
$invoicing_customer_selector = get_customer_selector(
	array(
		'name' => 'customer_id',
		'required' => 'required'
	),
	'invoicing_'
);

$invoicing_month = inputify(
	'month',
	'invoice_month',
	array(
		'label' => 'Month: ',
		'required' => 'required',
		'value' => (new DateTime())->format('Y-m')
	)
);

$generate_invoice_button= tagify(
	array(
		'tag' => 'button',
		'onclick' => 'generateInvoice()',
		'innerHTML' => 'Generate invoice',
		'id' => 'generate_invoice_button'
	)
);

$invoicing_form = formify(
	'POST',
	'javascript:void(0)',
	array(
		$csrf_input,
		$invoicing_customer_selector,
		'<br/>',
		$invoicing_month,
		'<br/>',
		$generate_invoice_button
	),
	array(
		'id' =>	'invoicing_times_form',
	)
);

$admin_forms['invoicing'] = array(
    'title' => 'Invoicing',
    'innerHTML' => $invoice_list . '<hr/>' . $invoicing_form
);

//create buttons and forms

$buttons = '';

$admin_forms_divs = '';

foreach ($admin_forms as $key => $value) {
    $buttons .= tagify(
		array(
            'tag' => 'button',
            'onclick' => "toggleSelected('$key')",
            'innerHTML' => $value['title'],
			'class' => 'header_button deselected',
			'id' => "${key}_button"
        )
    );
    $admin_forms_divs .= tagify(
        array(
            'tag' => 'div',
            'id' => "${key}_div",
            'innerHTML' => "<h4>${value['title']}</h4>" . $value['innerHTML'],
            'class' => 'admin_forms_div hidden feature-box'
        )
    );
}

$buttons .= '<div style="float:right">' .
    '<a href="emp.php"><button class="header_button">Employee interface</button></a>' .
	'<a href="login.php?logout=true"><button class="header_button">Logout</button></a>' .
	'</div>';

$head = get_default_head();

$body = (
	'<div class="header_placeholder">' .
	'<div class="header">' .
    '<h1>Admin Page</h1>' .
    tagify(
        array(
            'tag' => 'div',
            'id' => 'admin_header',
            'innerHTML' => $buttons
        )
	) .
	'</div></div>' .
    $admin_forms_divs
);

if (isset($status)) {
    $body .= get_status_box($status);
}

$body .= "<script>toggleSelected('invoicing')</script>";

echo get_document($head, $body, array());

?>
