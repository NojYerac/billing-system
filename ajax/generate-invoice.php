<?php
require_once('../config.php');
require_once('../db.php');
require_once('../comp.php');
require_once('../creds.php');

session_startup();

//prevent unauthorized access
if (!isset($_SESSION['user_priv']) || 
    $_SESSION['user_priv'] != 'Administrator'
    ) {
	http_response_code(302);
	header('Location: ' . BASE_URL . 'login.php');
    echo 'Not Authorized';
    exit();
}

//prevent csrf
$csrf_passed = (
    isset($_SESSION['csrf_token']) &&
    isset($_POST['csrf_token']) &&
    $_SESSION['csrf_token'] == $_POST['csrf_token']
) || die("CSRF check failed!");

if (!isset($_POST['customer_id']) || !isset($_POST['invoice_month'])) {
    http_response_code(500);
    echo "Missing required params";
    exit();
}

$min_time = (new DateTime($_POST['invoice_month'] . ' UTC'));
$max_time = (clone $min_time);
$max_time->modify('last day of this month');
$max_time->setTime(23,59,59);

function new_line_item($project_id) {
	$project = get_one_document(
		'projects',
		array(
			'_id' => (new MongoId($project_id))
		)
	);
	return array(
		'project_name' => $project['project_name'],
		'notes' => $project['project_notes'],
		'price' => $project['project_price'],
		'quantity' => 0,
		'unit' => 'hour',
	);
}

function seconds_to_hours_rounded($seconds) {
	return round($seconds/(60*15))/4;
}

function get_invoice_rows($customer_id, $min_time, $max_time) {
	$billable_times = get_all_documents(
		'timer',
		array(
			'customer_id'=>$_POST['customer_id'],
			'start_time' => array(
				'$gte' => $min_time,
				'$lte' => $max_time
			)
		)
	);

	$line_items = array();

	foreach ($billable_times as $time) {
		$interval = ($time['stop_time']->sec) - ($time['start_time']->sec);
		if (!isset($line_items[$time['project_id']])) {
			$line_items[$time['project_id']] = new_line_item($time['project_id']);
		}
		$line_items[$time['project_id']]['quantity'] += $interval;
	}

	$total = 0;

	foreach ($line_items as $project_id => $item) {
		$item['quantity'] = seconds_to_hours_rounded((float)$item['quantity']);
		$item['sub_total'] =  (float)$item['price'] * (float)$item['quantity'];
		$total += $item['sub_total'];
		$rows .= get_invoice_row(array($item, 'proj_' . $project_id));
	}

	//allow for custom line items.
	$line_items = get_all_documents('custom_rows', array(
		'customer_id' => $_POST['customer_id'],
		'time' => prepare_datetime($min_time)
		)
	);

	foreach ($line_items as $item) {
		$total += $item['sub_total'];
		$rows .= get_invoice_row($item, 'custom_' . $item['_id']);
	}
	return array('rows' => $rows, 'total' => $total);
}

$invoice_rows = get_invoice_rows($_POST['customer_id'], $min_time, $max_time);

$rows = "<tr><th>Project</th><th>Note</th><th>Quantity</th><th>Price</th><th>Total</th></tr>" .
	$invoice_rows['rows'];

//actual file path
$file = INVOICE_DIR . $invoice_num . '.pdf';

//create magic token... we'll use this to give secure links to customers without logins.
$token = bin2hex(openssl_random_pseudo_bytes(32));
$url = BASE_URL . '/invoice.php?token=' . $token;

$params  = array(
	'customer_id' => $customer_id,
	'month' => new MongoDate($min_time->format('U')),
	'invoice_num' => $invoice_num,
	'paid' => false,
	'url' => $url,
	'file' => $file,
	'total' => $invoice_rows['total'],
	'token' => $token
);

//save invoice params in database
$doc = get_one_document('invoices', array('file' => $file));
if ($doc) {
	update_one_document(
		'invoices',
		array('_id' => $doc['_id']),
		$params
	);
	$params['_id'] = $doc['_id'];
} else {
	$doc_id = insert_one_document('invoices', $params);
	$params['_id'] = $doc_id;
}

//FIXME should be easier to edit. Seperate file?
$style = "<style>" .
"td, table { border-bottom:1px solid black; border-collapse:collapse }" .
"th {background-color: #333; color: #FFF; border: 1px solid #333; padding:3px}" .
"tr:nth-child(2n) { background-color: #CCC; color: #000 }" .
"tr:nth-child(2n+1) { background-color: #FFF; color: #000 }" .
"</style>";

//get contact blocks
$cust = get_one_document('clients', array('_id' => (new MongoId($_POST['customer_id']))));

$invoice_num = $cust['invoice_prefix'] . '_' . $_POST['invoice_month'];

$customer_contact = 
	"<div style=\"float:left;width:50%\" id=\"customer_contact\">" .
	"<p id=\"customer_contact_name\">" .
	htmlentities($cust['customer_name']) .
	"</p><p id=\"customer_contact_address\">" .
	str_replace("\n", "<br/>", htmlentities($cust['customer_address'])) .
	"</p><p id=\"customer_contact_phone\">" .
	htmlentities($cust['customer_phone']) .
	"</p><p id=\"customer_contact_email\">" 
	. $cust['customer_email'] .
	"</p></div>";

$comp = get_one_document('company_profile', array());
$name = htmlentities($comp['company_name']);
$address = htmlentities($comp['company_address']);
$phone = htmlentities($comp['company_phone']);
$email = htmlentities($comp['company_email']);
$website = htmlentities($comp['company_website']);

$company_contact = 
	"<div style=\"float:left;width:50%;text-align:right\" id=\"company_contact\">" .
	"<p id=\"company_contact_name\">$name</p>" .
	"<p id=\"company_contact_address\">" . str_replace("\n", "<br/>", $address) . "</p>" .
	"<p id=\"company_contact_phone\">$phone</p>" . 
	"<a href=\"mailto:$email\" id=\"company_contact_email\">$email</a><br/>" .
	"<a href=\"http://$website\" id=\"company_contact_website\">$website</a>" .
	"</div>";

//F = full month name
$pretty_month = $min_time->format('F, Y');

//build_html
$html = "<body>" . $style .
	"<div><h1 style=\"text-align:center\">" .
	"Invoice for " . $pretty_month .
	"</h1><h4>Invoice number: " . htmlentities($invoice_num) . "</h4>" .
	"<div id=\"contact_container\" " .
	"style=\"width:100%\">" .
	$customer_contact .	$company_contact . "</div></div>" .
	"<table style=\"width:100%;" .
	"text-align:center;border:1px solid black\">$rows</table>" .
	"<h4 style=\"margin-right:10%;text-align:right\">Total: $${invoice_rows['total']}</h4>" .
	"</body>";

//convert html to pdf
include('../mpdf/mpdf.php');

$mpdf = new mPDF('utf-8', 'letter');
$mpdf->WriteHTML($html);
$mpdf->Output($file, 'F');

echo get_invoice_link($params);
exit();
?>
