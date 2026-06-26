<?php
// ============================================================
// SITE CONFIGURATION
// Non-database settings. You can override these by defining
// the constants in your ../private/db_credentials.php file
// before this file is loaded.
// ============================================================

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM',      'noreply@yourdomain.com');   // ← change to your sending address
    define('MAIL_FROM_NAME', 'Display System');
    define('SITE_NAME',      'Store Display System');
}
?>
