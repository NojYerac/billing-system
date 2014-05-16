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
        "&action=delete"
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

