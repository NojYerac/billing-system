function recordPayment() {
	toggleVisible('record_payment_div');
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
				invoiceTotal.textContent = (parseFloat(invoiceTotal.textContent) - parseFloat(subTotal)).toFixed(2);
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
			invoiceTable.innerHTML += this.responseText;
			invoiceTotal.textContent = (parseFloat(invoiceTotal.textContent) + price * quantity).toFixed(2);
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
