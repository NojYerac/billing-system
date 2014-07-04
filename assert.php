<?php
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_QUIET_EVAL, 1);
function assert_handler($file, $line, $code, $desc = null){
	echo $desc?$desc:'';
}
assert_options(ASSERT_CALLBACK, 'assert_handler');
?>
