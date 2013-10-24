<?php
//�������� ���������, �� �������� ������� ���� ��������!

//URL ���-���������� Zabbix
define('ZABBIX_URL', 'http://monitoring.lan/zabbix/');
//������������ � Zabbix
define('ZABBIX_USER', 'apiuser');
//������ ��� Zabbix
define('ZABBIX_PW', 'apipass');
//URL ����� ����
define('MAP_URL', ZABBIX_URL . 'map.php?noedit=1&sysmapid=3');
//����� ��� ����� ���� (�������� ���������� � index.php)
define('MAP_USER', 'map');
//������ ��� ����� ���� (�������� ���������� � index.php)
define('MAP_PASS', 'mappass');


// ���

if($_SERVER['PHP_AUTH_USER'] != MAP_USER || $_SERVER['PHP_AUTH_PW'] != MAP_PASS) 
{
    header('WWW-Authenticate: Basic realm=" @( * O * )@ "');
    header('HTTP/1.0 401 Unauthorized');
    exit;
} 
set_time_limit(30);

chdir(dirname(__FILE__));
unlink('./cookie.txt');
 
//"���������" �������� � Zabbix
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ZABBIX_URL . 'index.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array('name'=>ZABBIX_USER, 'password'=>ZABBIX_PW,'enter'=>'Sign in', 'autologin'=>1));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
//curl_setopt($ch, CURLOPT_TIMEOUT, 10);


curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt"); //��������� ���� � ����
curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");

$t = curl_exec($ch);

/*curl_close($ch);

$ch = curl_init();*/
curl_setopt($ch, CURLOPT_URL, MAP_URL);
curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_setopt($ch, CURLOPT_TIMEOUT, 10);


$file = curl_exec($ch);
header('Content-type: image/png');
echo $file;
curl_close($ch);

?>