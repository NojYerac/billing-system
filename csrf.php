<?php

function get_csrf_token() {
    $csrf_token = base64_encode(openssl_random_pseudo_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
    return $csrf_token;
}

function check_csrf_token($csrf_token) {
    if (isset($_SESSION['csrf_token'])) {
        if ($csrf_token == $_SESSION['csrf_token']) {
            unset($_SESSION['csrf_token']);
            return true;
        }
    }
    return false;
}
?>
