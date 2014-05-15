
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
    http_response_code(401);
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
            'start_time' => (new DateTime())
        )
    );
}

function stop_timer($timer_id) {
    return update_one_document(
        'timer',
        array('_id' => (new MongoId($timer_id))),
        array('stop_time' => (new DateTime()))
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
//var_dump($_POST);
//handle database changes...
if (isset($_GET['action']) && $csrf_passed) {
    switch ($_GET['action']) {
    case 'start timer':
        //start the timer
        $timer_id = start_timer($_POST['project_selector'], $_POST['customer_selector']);
        if ($timer_id) {
            $_SESSION['timer_started'] = $timer_started = $timer_id;
            $status = 'start timer successful';
        } else {
            $status = 'start timer failed';
        }
        break;
    case 'stop timer':
        //stop the timer
        if (stop_timer($_POST['timer_id'])) {
            $_SESSION['timer_started'] = $timer_started = false;
            $status = 'stop timer sucessful';
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

$emp_forms['new_project'] = array(
    'title' => 'New project',
    'innerHTML' => formify(
        'POST',
        '?action=new+project',
        array(
            $csrf_input,
            get_customer_selector(array('required' => 'required', 'name' => 'customer_id')), '<br/>',
            inputify('text', 'project_name', array(
                'required' => 'required',
		'label' => 'Project name: ',
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

$format = 'Y-m-d H:i:s';
$table_rows = '';
foreach (get_visible_clients() as $customer_name => $customer_id) {
	foreach (get_all_documents('projects', array(
		'customer_id' => (string)$customer_id)
	) as $project)
	{
		$project_id = $project['_id'];
		$project_name = htmlentities($project['project_name']);
		/*
		echo '<code style="background-color:#777">';
		var_dump($project_id);
		echo '<br/>';
		var_dump((string)$project_id);
		echo '<br/></code>';*/
		foreach (get_all_documents('timer', array(
			'project_id' => (string)$project_id
		)) as $time) {
		/*
		echo '<br/><br/><code style="background-color:#777">';
		var_dump($time);
		echo '<br>';*/
			$time_id = (string)($time['_id']);
			$start_time = new DateTime($time['start_time']['date']);
			if (isset($time['stop_time'])) {
				$stop_time = new DateTime($time['stop_time']['date']);
			} else {
				$stop_time = $start_time;
			}
			$diff_time = $start_time->diff($stop_time);
			//TODO: create a function for VVV td creation, put in comp.php,
			//similar functionality in ajax/edit-time.php
			$table_rows .= "<tr id=\"row_${time_id}\">" .
				"<td value=\"$customer_id\">$customer_name</td>" .
				"<td value=\"$project_id\">${project['project_name']}</td>" . 
				"<td>" . $start_time->format($format) . "</td>" .
				"<td>" . $stop_time->format($format) . "</td>" .
				"<td>" . $diff_time->format('%H:%I:%S') . "</td>" .
				"<td style=\"background-color:green\"" .
				" onclick=\"getEditTimeRow('$time_id')\"/>" .
				"<td style=\"background-color:red\"" .
			   	" onclick=\"if (confirm('Delete row?')) {deleteTime('$time_id')}\"/>" .
				"</tr>";
                }
        }
}

$table_headers = '<tr><th>Customer</th><th>Project</th><th>Start time</th>' .
	'<th>Stop time</th><th>Difference</th><th>E</th><th>D</th></tr>';

$show_times = tagify(array(
	'tag' => 'table',
	'class' => 'times',
	'id' => 'all_times_table',
        'innerHTML' => $table_headers . $table_rows
        )
);

$emp_forms['show_times'] = array(
    'title' => 'Show all times',
    'innerHTML' => $show_times
);

$compose_invoice = '';

$emp_forms['compose_invoice'] = array(
    'title' => 'Create invoice',
    'innerHTML' => $compose_invoice
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
