function recordPayment() {}

function addInvoiceRow() {
	toggleVisible('add_row_div');
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

function commitRow() {
	var csrfToken = document.getElementById('csrf_token').value;
	var invoiceId = document.getElementById('invoice_id').value;
	var name = document.getElementById('project_name_input').value;
	var notes = document.getElementById('project_notes_input').value;
	var price = document.getElementById('project_price_input').value;
	var unit = document.getElementById('project_unit_input').value;
	var quantity = document.getElementById('project_quantity_input').value;
	cancleRow();
	var params = "csrf_token=" + encodeURIComponent(csrfToken) +
		"&name=" + encodeURIComponent(name) +
		"&notes=" +  encodeURIComponent(notes) +
		"&price=" + encodeURIComponent(price) +
		"&unit=" + encodeURIComponent(unit) +
		"&quantity=" + encodeURIComponent(quantity);
	var invoiceTable = document.getElementById('invoice_table');
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			invoiceTable.innerHTML += this.responseText;
		}
	}
	xmlhttp.open(
			'POST',
			'ajax/edit-invoice.php?invoice_id=' + invoiceId,
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);
	xmlhttp.close();
}
