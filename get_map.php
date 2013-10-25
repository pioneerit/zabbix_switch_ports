<?php
// Load config
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "config.inc.php");

// Code
if($_SERVER['PHP_AUTH_USER'] != MAP_USER || $_SERVER['PHP_AUTH_PW'] != MAP_PASS)
{
    header('WWW-Authenticate: Basic realm=" @( * O * )@ "');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}
set_time_limit(30);

chdir(dirname(__FILE__));
unlink('./cookie.txt');

// "Login" to Zabbix with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ZABBIX_URL . 'index.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array('name'=>ZABBIX_USER, 'password'=>ZABBIX_PASS,'enter'=>'Sign in', 'autologin'=>1));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_TIMEOUT, 10);


// Save cookie to file
curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");

$t = curl_exec($ch);

/*curl_close($ch);

$ch = curl_init();*/
curl_setopt($ch, CURLOPT_URL, MAP_URL);
curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Return image
$file = curl_exec($ch);
header('Content-type: image/png');
echo $file;
curl_close($ch);

?>
