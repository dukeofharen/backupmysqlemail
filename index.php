<?php
/**
 * This file contains the MysqlBackup class wich performs
 * a partial or complete backup of any given MySQL database
 * @author Daniel López Azaña <http://www.daniloaz.com> (original author)
 * @author Duco Winterwerp <http://www.ducowinterwerp.nl> (upgrade)
 * @version 1.0
 */

// Turn off time limit
set_time_limit(0);
// Report all errors
error_reporting(E_ALL);

require_once('class.php');
 
/**
 * Define database parameters here
 */
define("DB_NAME", isset($_GET['database']) ? $_GET['database'] : "");
 
define("DB_USER", 'root');
define("DB_PASSWORD", 'password');
define("DB_HOST", 'localhost');
define("OUTPUT_DIR", 'backup');
define("TABLES", '*');

define('SMTP_HOST', 'mail.email.com');
define('SMTP_USER', 'you@email.com');
define('SMTP_PASSWORD', 'password');
define('SMTP_PORT', '587');
define('SMTP_AUTH', true);
define('SMTP_SECURE', 'tls');
define('SMTP_DEBUG', false);

define('EMAIL_TO', 'you@email.com');
define('EMAIL_SUBJECT', 'MySQL backup '.date("Y-m-d G:i:s").', database "'.DB_NAME.'"');
define('EMAIL_MESSAGE', 'Hi, here\'s your MySQL backup of the database '.DB_NAME);
define('EMAIL_FROM', 'you@email.com');
define('EMAIL_FROM_NAME', 'MySQL Backup');

$mysqlBackup = new MysqlBackup(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, EMAIL_TO, EMAIL_SUBJECT, EMAIL_MESSAGE, EMAIL_FROM, EMAIL_FROM_NAME);
$status = $mysqlBackup->backupTables(TABLES, OUTPUT_DIR) ? 'OK' : 'KO';;
?>