
<?php

require_once __DIR__ . '/config/session.php';

if (isset($_SESSION['iduser'])) {

    header("Location: /stokflow/dashboard.php");

} else {

    header("Location: /stokflow/auth/login.php");

}

exit;
