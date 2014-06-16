<?php
require_once('config.php');
require_once('db.php');
require_once('comp.php');
require_once('csrf.php');
//require_once('pdf.php');
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

$csrf_input = inputify(
    'hidden',
    'csrf_token',
    array('value' => $new_csrf_token)
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

$total = tagify(array(
	'tag' => 'span',
	'id' => 'invoice_total',
	'innerHTML' => currency($invoice_rows['total'])
));

$total = "<h4>Total: $$total</h4>";

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
  'onclick' => 'recordPayment()',
  'innerHTML' => 'Record payment'
)) .tagify(array(
  'tag' => 'button',
  'id' => 'add_row_button',
  'onclick' => 'addInvoiceRow()',
  'innerHTML' => 'Add row'
/*)) . tagify(array(
  'tag' => 'button',
  'id' => 'cancle_button',
  'onclick' => 'cancleEditInvoice()',
  'innerHTML' => 'Cancle'
)) . tagify(array(
  'tag' => 'button',
  'id' => 'save_button',
  'onclick' => 'saveInvoice()',
  'innerHTML' => 'Save'
 */));

$feature_box = tagify(array(
  'tag' => 'div',
  'class' => 'feature-box',
  'innerHTML' => $table . $total . $buttons
));

$edit_row_div = tagify(array(
  'tag' => 'div',
  'id' => 'edit_row_div',
  'class' => 'status-box center hidden',
  'innerHTML' =>
  	  $csrf_input .
  	  inputify(
		  'hidden',
		  'edit_invoice_id',
		  array('value' => $_GET['invoice_id'])
	  ) .
	  inputify(
		  'hidden',
		  'row_id',
		  array('value' => '0')
	  ) . 
      inputify(
        'text',
        'edit_project_name_input',
        array('label' => 'Project: ')
      ) .  '<br/>' .
      inputify(
        'text',
        'edit_project_notes_input',
        array('label' => 'Notes: ')
      ) .  '<br/>' .
      inputify(
        'number',
        'edit_project_price_input',
        array('label' => 'Price: ')
      ) .  '<br/>' .
      inputify(
        'text',
        'edit_project_unit_input',
        array('label' => 'Unit: ')
      ) .  '<br/>' .
      inputify(
        'number',
        'edit_project_quantity_input',
        array('label' => 'Quantity: ')
      ) . '<br/>' .
      tagify(
        array(
          'tag' => 'button',
          'id' => 'edit_commit_row_button',
          'onclick' => 'commitEditRow()',
          'innerHTML' => 'Ok'
        )
      ) . tagify(
        array(
          'tag' => 'button',
          'id' => 'edit_delete_row_button',
          'onclick' => 'deleteRow()',
          'innerHTML' => 'Delete'
        )
	  ) . tagify(
        array(
          'tag' => 'button',
          'id' => 'edit_cancle_row_button',
          'onclick' => 'cancleEditRow()',
          'innerHTML' => 'Cancle'
        )
      )
  )
);

$add_row_div = tagify(array(
  'tag' => 'div',
  'id' => 'add_row_div',
  'class' => 'status-box center hidden',
  'innerHTML' =>
  	  $csrf_input .
  	  inputify(
		  'hidden',
		  'invoice_id',
		  array('value' => $_GET['invoice_id'])
	  ) .
      inputify(
        'text',
        'project_name_input',
        array('label' => 'Project: ')
      ) .  '<br/>' .
      inputify(
        'text',
        'project_notes_input',
        array('label' => 'Notes: ')
      ) .  '<br/>' .
      inputify(
        'number',
        'project_price_input',
        array('label' => 'Price: ')
      ) .  '<br/>' .
      inputify(
        'text',
        'project_unit_input',
        array('label' => 'Unit: ')
      ) .  '<br/>' .
      inputify(
        'number',
        'project_quantity_input',
        array('label' => 'Quantity: ')
      ) . '<br/>' .
      tagify(
        array(
          'tag' => 'button',
          'id' => 'commit_row_button',
          'onclick' => 'commitRow()',
          'innerHTML' => 'Ok'
        )
      ) . tagify(
        array(
          'tag' => 'button',
          'id' => 'cancle_row_button',
          'onclick' => 'cancleRow()',
          'innerHTML' => 'Cancle'
        )
      )
    )
);

$record_payment_div = tagify(array(
  'tag' => 'div',
  'id' => 'record_payment_div',
  'class' => 'status-box center hidden',
  'innerHTML' =>
  	  $csrf_input .
  	  inputify(
		  'hidden',
		  'invoice_id',
		  array('value' => $_GET['invoice_id'])
	  ) .
      inputify(
        'date',
        'payment_date_input',
        array('label' => 'Payment Date: ')
      ) .  '<br/>' .
      inputify(
        'text',
        'payment_notes_input',
        array('label' => 'Notes: ')
      ) .  '<br/>' .
      inputify(
        'number',
        'ammount_paid_input',
        array('label' => 'Ammount: ')
      ) .  '<br/>' .
       tagify(
        array(
          'tag' => 'button',
          'id' => 'commit_payment_button',
          'onclick' => 'commitPayment()',
          'innerHTML' => 'Ok'
        )
      ) . tagify(
        array(
          'tag' => 'button',
          'id' => 'cancle_payment_button',
          'onclick' => 'canclePayment()',
          'innerHTML' => 'Cancle'
        )
      )
    )
);

$edit_script = tagify(array(
  'tag' => 'script',
  'src' => 'js/edit-invoice.js'
));

$header_buttons = '<div style="float:right">' .
	'<a href="admin.php"><button>Admin interface</button></a>' .
	'<a href="login.php?logout=true"><button>Logout</button></a>' .
	'</div>';



//build page
$head = get_default_head();

$body = (
  '<div class="header_placeholder">' .
  '<div class="header">' .
  "<h1>${invoice['invoice_number']}</h1>" .
  tagify(
    array(
      'tag' => 'div',
      'id' => 'edit_invoice_header',
      'innerHTML' => $header_buttons
      )
    ) . '</div></div>' .
    $feature_box .
	$add_row_div .
	$edit_row_div .
	$record_payment_div .
	$edit_script
);

if (isset($status)) {
    $body .= get_status_box($status);
}

echo get_document($head, $body, array());

?>
