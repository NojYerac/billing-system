function recordPayment() {
	var invoiceTotal = document.getElementById('invoice_total').textContent;
	var paymentDateInput = document.getElementById('payment_date_input');
	var paymentAmmountInput = document.getElementById('payment_ammount_input');
	paymentDateInput.value =  (new Date()).toISOString().slice(0,10);
	paymentAmmountInput.value = invoiceTotal;
	toggleVisible('record_payment_div');
}

function canclePayment() {
	var paymentDateInput = document.getElementById('payment_date_input');
	var paymentAmmountInput = document.getElementById('payment_ammount_input');
	var paymentNotesInput = document.getElementById('payment_notes_input');
	paymentDateInput.value =  '';
	paymentAmmountInput.value = '';
	paymentNotesInput.value = '';
	toggleVisible('record_payment_div');
}

function commitPayment() {
	var paymentsDiv = document.getElementById('invoice_payments_div');
	var paymentsDivClass = paymentsDiv.getAttribute('class', 2);
	var paymentsTable = document.getElementById('invoice_payments_table');
	var invoiceId = document.getElementById('invoice_id').value;
	var csrfToken = document.getElementById('csrf_token').value;
	var date = new Date(document.getElementById('payment_date_input').value);
	var notes = document.getElementById('payment_notes_input').value;
	var ammount = document.getElementById('payment_ammount_input').value;
	var params = "invoice_id=" + encodeURIComponent(invoiceId) +
		"&csrf_token=" + encodeURIComponent(csrfToken) +
		"&date=" + encodeURIComponent(date.getTime()/1000)+
		"&notes=" + encodeURIComponent(notes) +
		"&ammount=" + encodeURIComponent(ammount);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			if (/hidden/.test(paymentsDivClass)) {
				paymentsDivClass = paymentsDivClass.replace(/hidden/, 'visible');
				paymentsDiv.setAttribute('class', paymentsDivClass);
			}
			paymentsTable.firstChild.innerHTML += this.responseText;
			canclePayment();
			editBalance(-(ammount));
		}
	}
	xmlhttp.open(
		'POST',
		'ajax/edit-invoice.php?action=add+payment',
		true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
}

function checkBalance() {
	editBalance(0);
}

function editBalance(ammount) {
	var balanceSpan = document.getElementById('payment_balance');
	var balance = (parseFloat(balanceSpan.textContent) + ammount).toFixed(2);
	balanceSpan.textContent = balance;
	//disable add payment button when balance <= 0.
	var paymentButton = document.getElementById('record_payment_button');
	if (balance <= 0) {
		paymentButton.disabled = true;
		markPaid(true);
	} else {
		paymentButton.disabled = false;
		markPaid(false);
	}
}

function markPaid(paid) {
	var invoiceId = document.getElementById('invoice_id').value;
	var csrfToken = document.getElementById('csrf_token').value;
	var params = "invoice_id=" + encodeURIComponent(invoiceId) +
		"&csrf_token=" + encodeURIComponent(csrfToken) +
		"&paid=" + (paid?"1":"0");
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
		}
	}
	xmlhttp.open(
		'POST',
		'ajax/edit-invoice.php?action=mark+paid',
		true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
}



function addInvoiceRow() {
	toggleVisible('add_row_div');
}

function editInvoiceRow(rowId) {
	toggleVisible('edit_row_div');
	var row = document.getElementById('row_custom_' + rowId);
	var fields = ['name', 'notes', 'price', 'unit', 'quantity'];
	var field = document.getElementById('edit_project_name_input');
	field.value = row.children[0].textContent;
	var field = document.getElementById('edit_project_notes_input');
	field.value = row.children[1].textContent;
	var field = document.getElementById('edit_project_price_input');
	var price_unit = /([\d.]+)\/(\w+)/.exec(row.children[3].textContent)
	field.value = price_unit[1];
	var field = document.getElementById('edit_project_unit_input');
	field.value = price_unit[2];
	var field = document.getElementById('edit_project_quantity_input');
	field.value = /[\d.]+/.exec(row.children[2].textContent);
	document.getElementById('row_id').value = rowId;
}

function commitEditRow() {
	var csrfToken = document.getElementById('csrf_token').value;
	var invoiceId = document.getElementById('invoice_id').value;
	var rowId = document.getElementById('row_id').value;
	var name = document.getElementById('edit_project_name_input').value;
	var notes = document.getElementById('edit_project_notes_input').value;
	var price = document.getElementById('edit_project_price_input').value;
	var unit = document.getElementById('edit_project_unit_input').value;
	var quantity = document.getElementById('edit_project_quantity_input').value;
	var oldSubtotalRow = document.getElementById('row_custom_' + rowId);
	var oldSubtotal = parseFloat(oldSubtotalRow.lastChild.textContent.substr(1));
	var invoiceTotal = document.getElementById('invoice_total');
	cancleEditRow();
	var params = "invoice_id=" + encodeURIComponent(invoiceId) +
		"&row_id=" + encodeURIComponent(rowId) +
		"&csrf_token=" + encodeURIComponent(csrfToken) +
		"&project_name=" + encodeURIComponent(name) +
		"&notes=" +  encodeURIComponent(notes) +
		"&price=" + encodeURIComponent(price) +
		"&unit=" + encodeURIComponent(unit) +
		"&quantity=" + encodeURIComponent(quantity);
	var invoiceTable = document.getElementById('invoice_table');
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			oldSubtotalRow.parentNode.removeChild(oldSubtotalRow);
			invoiceTable.firstChild.innerHTML += this.responseText;
			invoiceTotal.textContent = (parseFloat(invoiceTotal.textContent) +
				   (price * quantity) - oldSubtotal).toFixed(2);
			editBalance(price * quantity);
		}
	}
	xmlhttp.open(
			'POST',
			'ajax/edit-invoice.php?action=edit+row',
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
}

function deleteRow() {
	var rowId = document.getElementById('row_id').value;
	var csrfToken = document.getElementById('csrf_token').value;
	var invoiceId = document.getElementById('invoice_id').value;
	var invoiceTotal = document.getElementById('invoice_total');
	var invoiceRow = document.getElementById('row_custom_' + rowId);
	var subTotal = invoiceRow.lastChild.textContent.slice(1);
	var invoiceTotal = document.getElementById('invoice_total');
	cancleEditRow();
	var params = "invoice_id=" + encodeURIComponent(invoiceId) +
		"&csrf_token=" + encodeURIComponent(csrfToken) +
		"&row_id=" + encodeURIComponent(rowId);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			if (this.responseText != 'failed') {
				invoiceRow.parentNode.removeChild(invoiceRow);
 				var adjustment = parseFloat(subTotal);
				invoiceTotal.textContent = (parseFloat(invoiceTotal.textContent) - adjustment).toFixed(2);
				editBalance(-(adjustment));
			}
		}
	}
	xmlhttp.open(
			'POST',
			'ajax/edit-invoice.php?action=delete+row',
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
}

function saveInvoice() {}

function cancleRow() {
	document.getElementById('project_name_input').value = '';
	document.getElementById('project_notes_input').value = '';
	document.getElementById('project_price_input').value = '';
	document.getElementById('project_unit_input').value = '';
	document.getElementById('project_quantity_input').value = '';
	toggleVisible('add_row_div');
}

function cancleEditRow() {
	document.getElementById('edit_project_name_input').value = '';
	document.getElementById('edit_project_notes_input').value = '';
	document.getElementById('edit_project_price_input').value = '';
	document.getElementById('edit_project_unit_input').value = '';
	document.getElementById('edit_project_quantity_input').value = '';
	toggleVisible('edit_row_div');
}

function commitRow() {
	var csrfToken = document.getElementById('csrf_token').value;
	var invoiceId = document.getElementById('invoice_id').value;
	var name = document.getElementById('project_name_input').value;
	var notes = document.getElementById('project_notes_input').value;
	var price = document.getElementById('project_price_input').value;
	var unit = document.getElementById('project_unit_input').value;
	var quantity = document.getElementById('project_quantity_input').value;
	var invoiceTotal = document.getElementById('invoice_total');
	cancleRow();
	var params = "invoice_id=" + encodeURIComponent(invoiceId) +
		"&csrf_token=" + encodeURIComponent(csrfToken) +
		"&project_name=" + encodeURIComponent(name) +
		"&notes=" +  encodeURIComponent(notes) +
		"&price=" + encodeURIComponent(price) +
		"&unit=" + encodeURIComponent(unit) +
		"&quantity=" + encodeURIComponent(quantity);
	var invoiceTable = document.getElementById('invoice_table');
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			invoiceTable.firstChild.innerHTML += this.responseText;
			invoiceTotal.textContent = (parseFloat(invoiceTotal.textContent) + price * quantity).toFixed(2);
			editBalance(price * quantity);
		}
	}
	xmlhttp.open(
			'POST',
			'ajax/edit-invoice.php?action=add+row',
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
}
