<?php
require_once('mpdf/mpdf.php');
require_once('db.php');

function regenerate_invoice_by_id($invoice_id) {
	$invoice = get_one_document('invoices', array('_id' => (new MongoId($invoice_id))));
	$min_time = date_create_from_format('U', $invoice['month']->sec);
	$max_time = (clone $min_time);
	$max_time->modify('last day of this month');
	$max_time->setTime(23,59,59);
	$customer_id = $invoice['customer_id'];
	return generate_invoice($customer_id, $min_time, $max_time);
}

function generate_invoice($customer_id, $min_time, $max_time) {
	$due_time = (new DateTime());
	$due_time->setTime(0,0,0);
	$due_time->modify('+10 days');

	$invoice_rows = get_invoice_rows($customer_id, $min_time, $max_time);

	$total = $invoice_rows['total'];
	$rows = "<tr><th>Project</th><th>Note</th><th>Quantity</th><th>Price</th><th>Total</th></tr>" .
		$invoice_rows['rows'];

	$cust = get_one_document('clients', array('_id' => (new MongoId($customer_id))));

	$invoice_month = $min_time->format('Y-M');

	$invoice_number	= $cust['invoice_prefix'] . '_' . $min_time->format('Ym');


	//actual file path
	$file = INVOICE_DIR . $invoice_number . '.pdf';

	$params  = array(
		'customer_id' => $customer_id,
		'month' => new MongoDate($min_time->format('U')),
		'due' => new MongoDate($due_time->format('U')),
		'invoice_number' => $invoice_number,
		'paid' => false,
		'file' => $file,
		'total' => $total,
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
		//create magic token... we'll use this to give secure links to customers without logins.
		$token = bin2hex(openssl_random_pseudo_bytes(32));
		$url = BASE_URL . '/invoice.php?token=' . $token;
		$params['token'] = $token;
		$params['url'] = $url;
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
		"</h1><h4>Invoice number: " . htmlentities($invoice_number) . "</h4>" .
		"<p>Payment due: <em>" . $due_time->format('F jS, Y') . "</em></p>" .
		"<div id=\"contact_container\" " .
		"style=\"width:100%\">" .
		$customer_contact .	$company_contact . "</div></div>" .
		"<table style=\"width:100%;" .
		"text-align:center;border:1px solid black\">$rows</table>" .
		"<h4 style=\"margin-right:10%;text-align:right\">Total: $" .
		currency($total) . "</h4>" .
		"</body>";

	//convert html to pdf
	$mpdf = new mPDF('utf-8', 'letter');
	$mpdf->WriteHTML($html);
	$mpdf->Output($file, 'F');
	return $params;
}
