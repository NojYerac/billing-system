<?php
require_once('creds.php');
require_once('config.php');
session_startup();
?>
<!DOCTYPE html>
<head>
<title>Session Test</title>
<body>
<?php
echo '<h1>COOKIE PARAMETERS</h1>';
foreach (session_get_cookie_params() as $key => $value) {
	echo "$key => ";
	var_dump($value);
	echo "<br/>";
}
echo "<h1>SESSION VARIABLES</h1>";
foreach ($_SESSION as $key=>$value) {
	echo "$key => $value<br/>";
}
?>
</body><html>
