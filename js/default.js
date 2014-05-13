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
    var custSel = document.getElementById('customer_selector');
    var custID = custSel.options[custSel.selectedIndex].value;
    if (custID == '0') {
            return 0;
    }
    var projSel = document.getElementById('project_selector');
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

