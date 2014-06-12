<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('csrf.php');
require_once('creds.php');
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

if (!isset($_SESSION['user_priv']) ||
  $_SESSION['user_priv'] != 'Administrator') {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

if (!isset($_GET['invoice_id'])) {
	http_response_code(500);
	echo "Missing Parameters";
	exit();
}

$invoice = get_one_document(
  'invoices',
  array(
    '_id' => (new MongoId($_GET['invoice_id']))
  )
);

$min_time = date_create_from_format('U', $invoice['month']->sec);
$max_time = (clone $min_time);
$max_time->modify('last day of this month');
$max_time->setTime(23,59,59);
$customer_id = $_POST['customer_id'];
$invoice_rows = get_invoice_rows($invoice['customer_id'], $min_time, $max_time);

$rows = "<tr><th>Project</th><th>Note</th><th>Quantity</th>" .
  "<th>Price</th><th>Total</th></tr>" . $invoice_rows['rows'];

$total = $invoice_rows['total'];

$table = tagify(array(
  'tag' => 'table',
  'id' => 'invoice_table',
  'class' => 'times',
  'innerHTML' => $rows
  )
);

$buttons = tagify(array(
  'tag' => 'button',
  'id' => 'record_payment_button',
  'innerHTML' => 'Record payment'
)) .tagify(array(
  'tag' => 'button',
  'id' => 'add_row_button',
  'innerHTML' => 'Add row'
)) . tagify(array(
  'tag' => 'button',
  'id' => 'cancle_button',
  'innerHTML' => 'Cancle'
)) . tagify(array(
  'tag' => 'button',
  'id' => 'save_button',
  'innerHTML' => 'Save'
));

$feature_box = tagify(array(
  'tag' => 'div',
  'class' => 'feature-box',
  'innerHTML' => $table . $buttons
));

//build page
$head = get_default_head();

$body = (
  '<div class="header_placeholder">' .
  '<div class="header">' .
    "<h1>${invoice['invoice_number']}</h1>" .
    tagify(
        array(
            'tag' => 'div',
            'id' => 'emp_header',
            'innerHTML' => $buttons
        )
    ) . '</div></div>' .
    $feature_box
);

if (isset($status)) {
    $body .= get_status_box($status);
}

echo get_document($head, $body, array());

?>
