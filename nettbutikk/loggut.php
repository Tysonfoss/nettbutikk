<?php
// Logg ut bruker ved å ødelegge sesjonen
session_start();
session_destroy();

// Redirect tilbake til hovedsiden
header("Location: index.php");
exit();
?>
