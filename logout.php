<?php
session_start();

// remove all session variables
$_SESSION = [];

// delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// finally, destroy the session
session_destroy();

// if you have any "remember me" cookies, clear them here too
// setcookie('remember_token', '', time() - 3600, '/', '', true, true);

header('Location: login.php');
exit;
?>