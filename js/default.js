onerror = alert;

function toggleVisible(targetId) {
    var target = document.getElementById(targetId);
    var targetClass = target.getAttribute('class', 2);
    if (/hidden/.test(targetClass)) {
        targetClass = targetClass.replace(/hidden/, 'visible');
    } else {
        targetClass = targetClass.replace(/visible/, 'hidden');
    }
    target.setAttribute('class', targetClass);
}

function toggleSelectedButton(targetId) {
    var target = document.getElementById(targetId);
    var targetClass = target.getAttribute('class', 2);
    if (/deselected/.test(targetClass)) {
        targetClass = targetClass.replace(/deselected/, 'selected');
    } else {
        targetClass = targetClass.replace(/selected/, 'deselected');
    }
    target.setAttribute('class', targetClass);
}

function toggleSelected(id) {
	var oldSelectedId = window.selectedId;
	window.selectedId = id;
	toggleVisible(id + '_div');
	toggleSelectedButton(id + '_button');
	if (oldSelectedId) {
		toggleVisible(oldSelectedId + '_div');
		toggleSelectedButton(oldSelectedId + '_button');
	}
}

function getProjects(suffix) {
    if (suffix === undefined) {
        suffix = '';
    }
    var custSel = document.getElementById('customer_selector'+suffix);
    var custID = custSel.options[custSel.selectedIndex].value;
    if (custID == '0') {
            return 0;
    }
    var projSel = document.getElementById('project_selector'+suffix);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            projSel.innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.open(
        "GET",
        "ajax/get-projects.php?custID=" + custID,
        true
    );
    xmlhttp.send();
}

function getPrice(suffix) {
    if (suffix === undefined) {
        suffix = '';
    }
	var customerSelector = document.getElementById('customer_selector' + suffix);
	var customerId = customerSelector.options[customerSelector.selectedIndex].value;
	var priceInput = document.getElementById('price' + suffix);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			priceInput.value = xmlhttp.responseText;
		}
	}
	xmlhttp.open(
		"GET",
		"ajax/get-price.php?customer_id=" + customerId,
		true
	);
	xmlhttp.send();
}

function getProjectDetails(suffix) {
    if (suffix === undefined) {
        suffix = '';
    }
	projectName = document.getElementById('name' + suffix);
	projectNotes = document.getElementById('notes' + suffix);
	projectPrice = document.getElementById('price' + suffix);
	projectId = document.getElementById('project_selector' + suffix).value;
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			var reResponse = xmlhttp.responseText.split('<>');
			projectName.value = reResponse[0];
			projectNotes.value = reResponse[1];
			projectPrice.value = reResponse[2];
		}
	}
	xmlhttp.open(
			'GET',
			'ajax/get-project-details.php?project_id=' + projectId,
			true
	);
	xmlhttp.send();
}

window.savedTr = {};

function getIndexByValue(selectElement, value) {
    for ( i=0, j=selectElement.children.length; i<j; i++ ) {
        if (selectElement.children[i].value == value) {
            return i;
        }
    }
    return 0;
}

function cloneNodeAndChildrenById(id) {
    var origNode = document.getElementById(id);
    var newNode = origNode.cloneNode();
    newNode.innerHTML = origNode.innerHTML;
    return newNode;
}

function getEditTimeRow(time_id) {
    /*
     * replace the row with a form
     * step 1: get curent values
     * step 2: build form elements
     * step 3: assemble new elements
     */
    var timeRow = document.getElementById('row_' + time_id);
    window.savedTr[time_id] = timeRow.innerHTML;
    var customerTd = timeRow.children[0];
    var projectTd = timeRow.children[1];
    var startTimeTd = timeRow.children[2];
    var stopTimeTd = timeRow.children[3];
    var diffTimeTd = timeRow.children[4];
    var greenButtonTd = timeRow.children[5];
    var redButtonTd = timeRow.children[6];
    var customerSelector = cloneNodeAndChildrenById('customer_selector');
    var custID = customerTd.getAttribute('value');
    customerTd.innerHTML = '';
    customerTd.appendChild(customerSelector);
    customerSelector.id += time_id;
    customerSelector.setAttribute('onchange', "getProjects('"+time_id+"')");
    customerSelector.selectedIndex = getIndexByValue(customerSelector, custID);
    var projId = projectTd.getAttribute('value');
    projectSelector = document.createElement('select');
    projectSelector.id = 'project_selector' + time_id;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            projectTd.innerHTML = '';
            projectTd.appendChild(projectSelector);
            projectSelector.innerHTML = xmlhttp.responseText;
            projectSelector.selectedIndex = getIndexByValue(projectSelector, projId);
        }
    }
    xmlhttp.open(
        "GET",
        "ajax/get-projects.php?custID=" + custID,
        true
    );
    xmlhttp.send();
    var startTimeValue = startTimeTd.innerHTML.replace(' ', 'T');
    startTimeTd.innerHTML = '';
    var startTimeInput = document.createElement('input');
    startTimeInput.id = 'start_datetime_' + time_id;
    startTimeInput.setAttribute('type', 'datetime-local');
    startTimeInput.setAttribute('onchange', "calculateTimeDiff('" + time_id + "')");
    startTimeInput.value = startTimeValue;
    startTimeTd.appendChild(startTimeInput);
    var stopTimeValue = stopTimeTd.innerHTML.replace(' ', 'T');
    stopTimeTd.innerHTML = '';
    var stopTimeInput = document.createElement('input');
    stopTimeInput.id = 'stop_datetime_' + time_id;
    stopTimeInput.setAttribute('type', 'datetime-local');
    stopTimeInput.setAttribute('onchange', "calculateTimeDiff('" + time_id + "')");
    stopTimeInput.value = stopTimeValue;
    stopTimeTd.appendChild(stopTimeInput);
    diffTimeTd.id = 'diff_time_' + time_id;
    greenButtonTd.setAttribute("onclick", "editTime('" + time_id + "')");
    redButtonTd.setAttribute("onclick", "cancleEditTimeRow('" + time_id + "')");
}

function calculateTimeDiff(time_id) {
    var diffTimeTd = document.getElementById('diff_time_' + time_id);
    var startTime = new Date(document.getElementById('start_datetime_' + time_id).value);
    var stopTime = new Date(document.getElementById('stop_datetime_' + time_id).value);
    var diffTime = stopTime - startTime;
    var hours = ((diffTime / (1000 * 60 * 60))|0);
    var mins = ('0' + ((((diffTime / (1000 * 60)) % 60)|0) % 60)).slice(-2);
    var secs = ('0' + (((diffTime / 1000) % 60)|0)).slice(-2);
    diffTimeTd.innerHTML = hours + ':' + mins + ':' + secs;
    //console.log(startTime.getTime() + ' - ' + stopTime.getTime() + ' = ' + diffTime);
}

function cancleEditTimeRow(time_id) {
    var timeRow = document.getElementById('row_' + time_id);
    timeRow.innerHTML = window.savedTr[time_id];
}

function editTime(time_id) {
    var timeRow = document.getElementById('row_' + time_id);
    var csrfToken = document.getElementById('csrf_token').value;
    var startTime = new Date(document.getElementById('start_datetime_' + time_id).value);
    var stopTime = new Date(document.getElementById('stop_datetime_' + time_id).value);
    var customerSelector = document.getElementById('customer_selector' + time_id);
    var projectSelector = document.getElementById('project_selector' + time_id);
    var customerID = customerSelector.options[customerSelector.selectedIndex].value;
    var projectID = projectSelector.options[projectSelector.selectedIndex].value;
    var params = "csrf_token=" + encodeURIComponent(csrfToken) + "&action=edit" +
        "&time_id=" + encodeURIComponent(time_id) +
        "&customer_id=" + encodeURIComponent(customerID) +
        "&project_id=" + encodeURIComponent(projectID) +
        "&start_time=" + encodeURIComponent(startTime.getTime()/1000) +
        "&stop_time=" + encodeURIComponent(stopTime.getTime()/1000);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            timeRow.innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.open(
        "POST",
        "ajax/edit-time.php",
        true
    );
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.send(params);
}

function deleteTime(time_id) {
    var timeRow = document.getElementById('row_' + time_id);
    var csrfToken = document.getElementById('csrf_token').value;
    var params = "csrf_token=" + encodeURIComponent(csrfToken) +
        "&time_id=" + encodeURIComponent(time_id) +
        "&action=delete";
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            timeRow.parentNode.removeChild(timeRow);
        }
    }
    xmlhttp.open(
        "POST",
        "ajax/edit-time.php",
        true
    );
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.send(params);
}

function filterRows() {
    var timeTable = document.getElementById('time_table').firstChild;
    var minTime = new Date(document.getElementById('filter_min_time').value);
    var maxTime = new Date(document.getElementById('filter_max_time').value);
    var customerSelector = document.getElementById('customer_selector_filter');
    var customerId = customerSelector.options[
            customerSelector.selectedIndex
        ].value;
    var params = 'customer_id=' + encodeURIComponent(customerId) + '&' +
        'min_time=' + encodeURIComponent(minTime.getTime()/1000) + '&' +
        'max_time=' + encodeURIComponent(maxTime.getTime()/1000);
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            if (xmlhttp.responseText != 'failed') {
				var headerRow = timeTable.firstChild.cloneNode();
				headerRow.innerHTML = timeTable.firstChild.innerHTML;
				timeTable.innerHTML = '';
				timeTable.appendChild(headerRow);
				timeTable.innerHTML += xmlhttp.responseText;
			}
		}
	}
    xmlhttp.open(
        "POST",
        "ajax/get-rows.php",
        true
    );
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.send(params);
}

function generateInvoice() {
	var invoiceListUl = document.getElementById('invoice_list_ul');
	var customerSelector = document.getElementById(
			'customer_selector_invoicing'
			);
	var invoiceMonth = document.getElementById('invoice_month').value;
	var customerId = customerSelector.options[
			customerSelector.selectedIndex
		].value;
	var csrfToken = document.getElementById('csrf_token').value;
	var params = 'customer_id=' + encodeURIComponent(customerId) + '&' +
		'invoice_month=' + encodeURIComponent(invoiceMonth) + '&' +
		'csrf_token=' + encodeURIComponent(csrfToken);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			var newInvoiceLinkId = /id="(invoice_li_[^"]+)/.exec(xmlhttp.responseText)[1];
			console.log(newInvoiceLinkId);
			var oldInvoiceLink = document.getElementById(newInvoiceLinkId);
			if (oldInvoiceLink) {
				//invoiceListDiv.removeChild(oldInvoiceLink.nextSibling);
				//invoiceListDiv.removeChild(oldInvoiceLink.nextSibling);
				invoiceListUl.removeChild(oldInvoiceLink);
			}
			invoiceListUl.innerHTML += xmlhttp.responseText;
		}
	}
	xmlhttp.open(
			"POST",
			"ajax/generate-invoice.php",
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.send(params);
}

function deleteInvoice(invoiceId) {
    var csrfToken = document.getElementById('csrf_token').value;
	var params = 'csrf_token=' + encodeURIComponent(csrfToken) +
        '&invoice_id=' + encodeURIComponent(invoiceId);
	var invoiceLi = document.getElementById('invoice_li_' + invoiceId);
	var editInvoiceDiv = document.getElementById('edit_invoice_div_' + invoiceId);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			editInvoiceDiv.parentNode.removeChild(editInvoiceDiv);
			invoiceLi.parentNode.removeChild(invoiceLi);
		}
	}
	xmlhttp.open(
			'POST',
			'ajax/edit-invoice.php?action=delete',
			true
	);
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
	xmlhttp.send(params);

}


function fillCustomerDetails(suffix) {
	var customerSelector = document.getElementById('customer_selector' + suffix);
	var customerId = customerSelector.options[
			customerSelector.selectedIndex
		].value;
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			var reResponse = xmlhttp.responseText.split('<>');
			document.getElementById(
				'customer_name' + suffix
			).value = reResponse[0];
			document.getElementById(
				'customer_prefix' + suffix
			).value = reResponse[1];
			document.getElementById(
				'customer_rate' + suffix
			).value = reResponse[2];
			document.getElementById(
				'customer_address' + suffix
			).value = reResponse[3];
			document.getElementById(
				'customer_phone' + suffix
			).value = reResponse[4];
			document.getElementById(
				'customer_email' + suffix
			).value = reResponse[5];
		}
	}
	xmlhttp.open(
		"GET",
		"ajax/get-customer-details.php?customer_id=" + customerId,
		true
	);
	xmlhttp.send();
}

function runTimer() {
	window.secondsElapsed = 0;
	window.intervalHandler = setInterval(function() {
		window.secondsElapsed++;
		var hours = ((window.secondsElapsed / (60 * 60))|0);
		var mins = ('0' + ((((window.secondsElapsed / (60)) % 60)|0) % 60)).slice(-2);
		var secs = ('0' + ((window.secondsElapsed % 60)|0)).slice(-2);
		document.getElementById('running_timer_display').innerHTML = hours + ':' + mins + ':' + secs;
	}, 1000);
}
