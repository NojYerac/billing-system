<?php
session_start();
?>
<!DOCTYPE html>
<head>
<title>Session Test</title>
<body>
<?php
echo "<h1>SESSION VARIABLES</h1>";
foreach ($_SESSION as $key=>$value) {
	echo "$key => $value<br/>";
}
?>
</body><html>
