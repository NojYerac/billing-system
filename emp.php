<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('creds.php');
require_once('csrf.php');

session_startup();

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

function create_project($id, $name, $notes, $price) {
    return insert_one_document(
        'projects',
        array(
            'customer_id' => $id,
			'project_name' => $name,
			'project_notes' => $notes,
			'project_price' => $price,
			'active' => true
        )
    );
}

function edit_project($id, $name, $notes, $price) {
	return update_one_document(
		'projects',
		array('_id' => (new MongoId($id))),
        array(
			'project_name' => $name,
			'project_notes' => $notes,
			'project_price' => $price,
			'active' => true
        )
    );
}		

function check_reqd_post_params($reqd_params) {
	foreach ($reqd_params as $param) {
		if (!isset($_POST[$param]) || $_POST[$param] == '') {
			return false;
		}
	}
	return true;
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
		$reqd_params = array(
			'customer_id',
			'name_new_project',
			'price_new_project'
		);
		if (check_reqd_post_params($reqd_params)) {
			$project_id = create_project(
				$_POST['customer_id'],
				$_POST['name_new_project'],
				$_POST['notes_new_project']?$_POST['notes_new_project']:'',
				$_POST['price_new_project']
			);
		} else {
			die('Mising parameters');
		}
        if (isset($project_id)) {
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
	case 'edit project':
		$reqd_params = array(
			'project_selector_edit_project',
			'name_edit_project',
			'notes_edit_project',
			'price_edit_project'
		);
		if (check_reqd_post_params($reqd_params)) {
			$project_id = edit_project(
				$_POST['project_selector_edit_project'],
				$_POST['name_edit_project'],
				$_POST['notes_edit_project'],
				$_POST['price_edit_project']
			);
		}
		if ($project_id) {
			$status = 'edit project successful';
		} else {
			$status = 'edit project failed';
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

$running_timer = tagify(array(
	'tag' => 'div',
	'id' => 'running_timer_div',
	'innerHTML' => '<script>runTimer()</script>' .
		'<h4 id="running_timer_display">0:00:00</h4>'
	)
);

$timer_form_stop = $running_timer .
	formify(
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
			get_customer_selector(
				array(
					'required' => 'required',
					'name' => 'customer_id',
					'onchange' => "getPrice('_new_project')"
				), '_new_project'), '<br/>',
            inputify('text', 'name_new_project', array(
                'required' => 'required',
				'label' => 'Project name: ',
                )
			), '<br/>',
			inputify('textarea', 'notes_new_project', array(
				'label' => 'Project notes: '
				)
			), '<br/>',
			inputify('number', 'price_new_project', array(
				'label' => 'Price: ',
				'step' => '0.01',
				'reqired' => 'required'
				)
			), '<br/>',
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

//define edit project form
$emp_forms['edit_project'] = array(
    'title' => 'Edit project',
    'innerHTML' => formify(
        'POST',
        '?action=edit+project',
        array(
            $csrf_input,
			get_project_selector(true, '_edit_project', array(
				'onchange' => "getProjectDetails('_edit_project')")
			), '<br/>',
			inputify('textarea', 'name_edit_project', array(
				'label' => 'Project name: ',
				'required' => 'required'
				)
			), '<br/>',
			inputify('textarea', 'notes_edit_project', array(
				'label' => 'Project notes: '
				)
			), '<br/>',
			inputify('number', 'price_edit_project', array(
				'label' => 'Price: ',
				'step' => '0.01',
				'reqired' => 'required'
				)
			), '<br/>',
            inputify('submit', 'edit_project', array(
		'value' => 'Edit project'
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

$customer_selector = get_customer_selector(array(), '_filter');

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

//assemble buttons and forms
$buttons = '';

$emp_forms_divs = '';

foreach ($emp_forms as $key => $value) {
    $buttons .= tagify(
        array(
			'tag' => 'button',
			'id' => "${key}_button",
            'onclick' => "toggleSelected('$key')",
            'innerHTML' => $value['title'],
            'class' => 'header_button deselected'
        )
    );
    $emp_forms_divs .= tagify(
        array(
            'tag' => 'div',
            'id' => "${key}_div",
            'innerHTML' => $value['innerHTML'],
            'class' => "emp_forms_div feature-box hidden"
        )
    );
}

$buttons .= '<div style="float:right">' .
	(($_SESSION['user_priv'] == 'Administrator')?
	'<a href="admin.php"><button>Admin interface</button></a>':'') .
	'<a href="login.php?logout=true"><button>Logout</button></a>' .
	'</div>';

//build page
$head = get_default_head();

$body = (
	'<div class="header_placeholder">' .
	'<div class="header">' .
    '<h1>Time tracking</h1>' .
    tagify(
        array(
            'tag' => 'div',
            'id' => 'emp_header',
            'innerHTML' => $buttons
        )
    ) . '</div></div>' .
    $emp_forms_divs
);

if (isset($status)) {
    $body .= get_status_box($status);
}

$body .= "<script>toggleSelected('timer_form')</script>";

echo get_document($head, $body, array());

?>
