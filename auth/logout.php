<?php
// ============================================================
//   NOVASTORE - auth/logout.php
//   Déconnexion et destruction de la session
// ============================================================

session_start();
session_unset();
session_destroy();

header('Location: ../index.php');
exit;