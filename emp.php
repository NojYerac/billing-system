
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
    return update_one_value(
        'timer',
        array('_id' => $timer_id),
        'stop_time', (new DateTime())
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
            $status = 'stop timer successful';
        } else {
            $status = 'stop timer failed';
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
            $timer_id = start_timer($project_id, $_POST['customer_id']);
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
        get_project_selector(), '<br/>',
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
            get_customer_selector(array('name' => 'customer_id')), '<br/>',
            inputify('text', 'project_name', array(
                'required' => 'required',
                'label' => 'Project name: '
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
$table_rows = '';
foreach (get_visible_clients() as $customer_name => $customer_id) {
        foreach (get_all_documents('projects', array('customer_id' => $customer_id)) as $project) {
                foreach (get_all_documents('timer', array('project_id' => $project['_id'])) as $time) {
                        $table_rows .= "<tr><td>$customer_name</td><td>${project['project_name']}<td>${time['start_time']}</td><td>${time['end_time']}</td>";
                }
        }
}

$show_times = tagify(array(
        'tag' => 'table',
        'innerHTML' => $table_rows
        )
);

$emp_forms['show_times'] = array(
    'title' => 'Show all times',
    'innerHTML' => $show_times
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
