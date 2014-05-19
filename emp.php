<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('creds.php');
require_once('csrf.php');

session_start();

//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || 
    !in_array(
        $_SESSION['user_priv'],
        array('Administrator', 'Employee')
    )) {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

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

if (!isset($_SESSION['timer_started'])) {
    $_SESSION['timer_started'] = false;
}

$timer_started = $_SESSION['timer_started'];

function start_timer($project_id, $customer_id) {
    return insert_one_document(
        'timer',
        array(
            'project_id' => $project_id,
            'customer_id' => $customer_id,
            'start_time' => prepare_datetime(new DateTime()),
            'stop_time' => prepare_datetime(new DateTime())
        )
    );
}

function stop_timer($timer_id) {
    return update_one_document(
        'timer',
        array('_id' => (new MongoId($timer_id))),
        array('stop_time' => prepare_datetime(new DateTime()))
    );
}

function create_project($customer_id, $project_name) {
    return insert_one_document(
        'projects',
        array(
            'customer_id' => $customer_id,
	    'project_name' => $project_name,
	    'active' => true
        )
    );
}

//handle database changes...
if (isset($_GET['action']) && $csrf_passed) {
    switch ($_GET['action']) {
    case 'start timer':
        //start the timer
        $timer_id = start_timer($_POST['project_selector'], $_POST['customer_selector']);
        if ($timer_id) {
            $_SESSION['timer_started'] = $timer_started = $timer_id;
            $status = "Timer $timer_id started sucessfully";
        } else {
            $status = 'start timer failed';
        }
        break;
    case 'stop timer':
        //stop the timer
        if (stop_timer($_POST['timer_id'])) {
            $_SESSION['timer_started'] = $timer_started = false;
            $status = "Timer ${_POST['timer_id']} sucessfully stopped";
        } else {
            $status = 'stop timer failed';
        }
        break;
    case 'new project':
        $project_id = create_project(
            $_POST['customer_id'],
            $_POST['project_name']
            );
        if ($project_id) {
            $status = 'create new project sucessful';
        } else {
            $status = 'create new project failed';
        }
        if (isset($_POST['start_timer'])) {
            $timer_id = start_timer((string)$project_id, $_POST['customer_id']);
	    if ($timer_id) {
		$_SESSION['timer_started'] = $timer_started = $timer_id;
                $status .= '<br/>start timer successful';
            } else {
                $status .= '<br/>start timer failed';
            }
        }
        break;
    default:
        die("<br/>invalid action: csrf_passed=" . $csrf_passed);
    }
}

//build forms
$emp_forms = array();

$csrf_input = inputify(
    'hidden',
    'csrf_token',
    array('value' => $new_csrf_token)
);

//define timer forms
$timer_form_start = formify(
    'POST',
    '?action=start+timer',
    array(
        $csrf_input,
        get_project_selector('required'), '<br/>',
        inputify('submit', 'timer_submit', array('value' => 'Start'))
    ),
    array()
);

$timer_form_stop = formify(
    'POST',
    '?action=stop+timer',
    array(
        $csrf_input,
        inputify('hidden', 'timer_id', array(
            'value' => $_SESSION['timer_started']
            )
        ), '<br/>',
        inputify('submit', 'timer_submit', array('value' => 'Stop'))
    ),
    array()
);

$emp_forms['timer_form'] = array(
    'title' => 'Timer',
    'innerHTML' => ($timer_started?$timer_form_stop:$timer_form_start)
);


//define new project form
$emp_forms['new_project'] = array(
    'title' => 'New project',
    'innerHTML' => formify(
        'POST',
        '?action=new+project',
        array(
            $csrf_input,
            get_customer_selector(array('required' => 'required', 'name' => 'customer_id', 'onchange' => 'getPrice()'), 'project_'), '<br/>',
            inputify('text', 'project_name', array(
                'required' => 'required',
		'label' => 'Project name: ',
                )
			), '<br/>',
			inputify('textarea', 'project_notes', array(
				'label' => 'Project notes: '
				)
			), '<br/>',
			inputify('number', 'project_price', array(
				'label' => 'Price: ',
				'step' => '0.01',
				'reqired' => 'required'
				)
			),
            inputify('checkbox', 'start_timer', array(
                'label' => 'Start timer: '
                )
            ), '<br/>',
            inputify('submit', 'create_new_project', array(
		'value' => 'Create project'
                )
            )
        ),
        array()
    )
);

//define show_tables
$format = 'Y-m-d\TH:i:s';
$table_rows = '';
$min_time = (new DateTime())->modify('first day of this month');
$min_time->setTime(0, 0, 0);
$max_time = (new DateTime())->modify('last day of this month');
$max_time->setTime(23, 59, 59);

////filter form
$min_datetime_input = inputify(
	'datetime-local',
	'min_time',
	array(
		'label' => 'From: ',
		'id' => 'filter_min_time',
		'value' => $min_time->format($format)
	)
);

$max_datetime_input = inputify(
	'datetime-local',
	'max_time',
	array(
		'label' => 'Until: ',
		'id' => 'filter_max_time',
		'value' => $max_time->format($format),
	)
);

//$project_selector = get_project_selector();

$customer_selector = get_customer_selector(array(), 'filter_');

$filter_submit = inputify(
	'submit',
	'filter_submit',
	array(
		'value' => 'Filter',
		'onclick' => 'filterRows()'
	)
);


$filter_times_form = formify(
	'GET',
	'javascript:void(0)',
	array(
		$customer_selector,
		'<br/>',
		$min_datetime_input,
		$max_datetime_input,
		$filter_submit
	),
	array(
		'id' =>	'filter_times_form',
		'class' => 'hidden',
	)
);

$filter_times_div = tagify(
	array(
		'tag' => 'div',
		'id' => 'filter_times_div',
		'innerHTML' => 	tagify(
			array(
				'tag' => 'button',
				'id' => 'toggle_filter_button',
				'onclick' => 'toggleVisible(\'filter_times_form\')',
				'innerHTML' => 'Filter options'
			)
		) . $filter_times_form
	)
);

////build table
foreach (get_visible_clients() as $customer_name => $customer_id) {
    $table_rows .= get_time_rows_by_customer_and_datetime(
        (string)$customer_id,
        prepare_datetime($min_time),
        prepare_datetime($max_time)
        );
}


$table_headers = '<tr><th>Customer</th><th>Project</th><th>Start time</th>' .
	'<th>Stop time</th><th>Difference</th><th>E</th><th>D</th></tr>';

$show_times = tagify(array(
	'tag' => 'table',
	'class' => 'times',
	'id' => 'time_table',
        'innerHTML' => $table_headers . $table_rows
        )
);

$emp_forms['show_times'] = array(
    'title' => 'Show times',
    'innerHTML' => $filter_times_div . $show_times
);

//define compose invoice
$invoicing_customer_selector = get_customer_selector(
	array('name' => 'customer_id', 'required' => 'required'), 'invoicing_');

$invoicing_month = inputify(
	'month',
	'invoice_month',
	array('required' => 'required')
);

$invoicing_submit = inputify(
	'submit',
	'invoicing_submit',
	array(
		'value' => 'Create',
	)
);

$invoicing_form = formify(
	'POST',
	'ajax/invoice.php',
	array(
		$csrf_input,
		$invoicing_customer_selector,
		'<br/>',
		$invoicing_month,
		$invoicing_submit
	),
	array(
		'id' =>	'invoicing_times_form',
	)
);

$emp_forms['invoicing'] = array(
    'title' => 'Invoicing',
    'innerHTML' => $invoicing_form
);

//assemble buttons and forms
$buttons = '';

$emp_forms_divs = '';

foreach ($emp_forms as $key => $value) {
    $visibility = (($key == 'timer_form')?'visible':'hidden');
    $buttons .= tagify(
        array(
            'tag' => 'button',
            'onclick' => "toggleVisible('${key}_div')",
            'innerHTML' => $value['title'],
            'class' => 'header_button'
        )
    );
    $emp_forms_divs .= tagify(
        array(
            'tag' => 'div',
            'id' => "${key}_div",
            'innerHTML' => $value['innerHTML'],
            'class' => "emp_forms_div feature-box $visibility"
        )
    );
}

$buttons .= '<a href="login.php?logout=true"><button>Logout</button></a>';

if ($_SESSION['user_priv'] == 'Administrator') { 
    $buttons .= '<a href="admin.php"><button>Admin interface</button></a>';
}

//build page
$head = get_default_head();

$body = (
    tagify(
        array(
            'tag' => 'div',
            'id' => 'emp_header',
            'innerHTML' => $buttons
        )
    ) .
    '<h1>Time tracking</h1>' .
    $emp_forms_divs
);

if (isset($status)) {
    $body .= get_status_box($status);
}

echo get_document($head, $body, array());

?>
