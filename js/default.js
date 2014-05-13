function youWin() {
    document.getElementById('tah-dah').setAttribute('class', 'visible');
    document.getElementById('chalenge').setAttribute('class', 'hidden');
}

function resetChalenge() {
    document.getElementById('tah-dah').setAttribute('class', 'hidden');
    document.getElementById('chalenge').setAttribute('class', 'visible');
}

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

function getProjects() {
	getProjects('');
}

function getProjects(suffix) {
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
function getEditTimeRow(time_id) {
    /*
     * replace the row with a form
     * step 1: get curent values
     * step 2: build form elements
     * step 3: assemble new elements
     */
    //step 1
    var timeRow = document.getElementById('row_' + time_id);
    window.savedTr[time_id] = timeRow.innerHTML;
    var customerTd = timeRow.children[0];
    var projectTd = timeRow.children[1];
    var startTimeTd = timeRow.children[2];
    var stopTimeTd = timeRow.children[3];
    var differenceTd = timeRow.children[4];
    var greenButtonTd = timeRow.children[5];
    var redButtonTd = timeRow.children[6];

    var customerSelector = cloneNode(document.getElementById('customer_selector'));
    var custID = customerTd.value;
    customerTd.innerHTML = '';
    customerTd.appendChild(customerSelector);
    customerSelector.id += time_id;
    customerSelector.setAttribute('onchange', "getProjects('"+time_id+"')");
    
    projectSelector = document.createElement('select');
    projectSelector.id = 'project_selector' + time_id;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            projectSelector.innerHTML = xmlhttp.responseText;
        }
    }
    xmlhttp.open(
        "GET",
        "ajax/get-projects.php?custID=" + custID,
        true
    );
    xmlhttp.send();
    var projId = projectTd.value;
    projectTd.innerHTML = '';
    projectTd.appendChild(projectSelector);
    projectSelector.selectedIndex = projId;
    var startTimeValue = startTimeTd.inneHTML.replace(' ', 'T');
    startTimeTd.innerHTML = '';
    var startTimeInput = document.createElement('datetime-local');
    startTimeInput.value = startTimeValue;
    startTimeTd.appendChild(startTimeInput);
    var stopTimeValue = stopTimeTd.innerHTMLdd.replace(' ', 'T');
    stopTimeTd.innerHTML = '';
    var stopTimeInput = document.createElement('datetime-local');
    stopTimeInput.value = stopTimeValue;
    stopTimeTd.appendChild(stopTimeInput);
    //diffTime
    greenButtonTd.setAttribute("onclick", "editTime('" + time_id + "')");
    redButtonTd.setAttribute("onclick", "cancleEditTimeRow('" + time_id + "')");

}

function cancleEditTimeRow(time_id) {
    var timeRow = document.getElementById(time_id);
    timeRow = window.savedTr[time_id];
}

function editTime(time_id) {
    var timeRow = document.getElementById(time_id);
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

    //var window.savedTimeRow = cloneNode(timeRow);
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
        "ajax/edit-time.php?",
        true
    );
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.setRequestHeader("Content-length", params.length);
    xmlhttp.setRequestHeader("Connection", "close");
    xmlhttp.send(params);
}


