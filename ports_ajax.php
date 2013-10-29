<?php

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "config.inc.php");
// User from config for connection with Zabbix API
$user = ZABBIX_USER;
$pass = ZABBIX_PASS;
$url = ZABBIX_URL;

error_reporting(E_ALL);
ini_set('display_errors', '1');
if(empty($_REQUEST['hostid']))
{
    avost();
}

require_once("ZabbixAPI.class.php");
ZabbixAPI::debugEnabled(TRUE);
$zlogin = ZabbixAPI::login($url,$user,$pass);
if($zlogin)
{
    $hosts = ZabbixAPI::fetch_array('host','get',array('output'=>'extend', 'hostids'=>array($_REQUEST['hostid']), 'monitored'=>1));

    $host = $hosts[0];

    if(!$host) avost();

	$host_ifaces = ZabbixAPI::fetch_array('hostinterface','get',array('output'=>'extend', 'hostids'=>array($_REQUEST['hostid']), 'monitored'=>1));
	$host_iface = $host_ifaces[0];

    $host_html = '';
    $host_html .= "<div class=\"name_label\">{$host['host']}</div>";
    $hostid = $host['hostid'];
    $items = ZabbixAPI::fetch_array('item','get',array('hostids'=>array($hostid), 'output'=>'extend', 'monitored'=>1));
    if(!$items) avost();
    $has_ports = false;
    $ports_info = array();
    $ports_speed = array('fe', 'ge');
    $uptime = false;
    $temperature = false;
    $memory = false;
    $cpu = false;

    $catch_keys = array('ifAlias', 'ifInBroadcastPkts', 'ifOutBroadcastPkts', 'ifInOctets', 'ifOutOctets', 'ifInErrors', 'ifOutErrors', 'ifOperStatus', 'ifAdminStatus', 'ifLastChange');
    //print_r($items);
    foreach($items as $itemkey => $item)
    {
        $regs = array();

        if($item['key_'] == 'temperatureStatus') $temperature = $item['lastvalue'];
        if($item['key_'] == 'cpuUsage') $cpu = $item['lastvalue'];
        if($item['key_'] == 'Uptime') $uptime = $item['lastvalue'];
        if($item['key_'] == 'memoryUsage') $memory = $item['lastvalue'];

        foreach($catch_keys as $key)
        {
            if(strstr($item['snmp_oid'], $key))
            {
                $ports_info[get_item_key($item)][$key] = $item;
            }
        }

        //if(preg_match('/Status port ([GF]E )?([0-9]+)/', $item['name'], $regs))
//echo "<pre>";
//echo $itemkey;
//print_r($item);
//print_r($ports_info);
//echo "</pre>";
        // if(preg_match('/Operational status of interface/', $item['name'], $regs))
        if(preg_match('/ifOperStatus\[([a-zA-Z]*).*\/([0-9]+)\]/', $item['key_'], $regs))
        {
            if($regs[1] == 'GE ' || $regs[1] == 'GigabitEthernet' ) $ports_info[get_item_key($item)]['speed'] = 'ge';
            else $ports_info[get_item_key($item)]['speed'] = 'fe';

            $ports_info[get_item_key($item)]['number'] = $regs[2];
            $has_ports = true;
        }
    }
//echo "<pre>";
//print_r($ports_info);
//echo "</pre>";

    foreach($ports_info as $k => $port) if(empty($port['ifOperStatus']) || empty($port['speed'])) unset($ports_info[$k]);

    $tooltip_str = '';
    if($uptime)
    {
        $tooltip_str .= "Uptime: " . sprintf("%dd %02d:%02d", $uptime / 86400, $uptime % 86400 / 3600,  $uptime % 3600 / 60) . "\\r\\n";
    }

    if($host['snmp_available'] == 2)
    {
        $tooltip_str .= "<b>Error: " . strip_tags($host['snmp_error']) . "</b><br>";
    }

    $tooltip_str .= "IP: {$host_iface['ip']}\\r\\n" .
                    "DNS: {$host_iface['dns']}\\r\\n";

    $indicators_html = "snmp<div class=\"led " . ($host['snmp_available'] == 2 ? ' lr' : ' lg') . "\"></div> ";
    if($cpu !== false)
    {
        $indicators_html .= "cpu<div class=\"led\" style=\"background-color: #" . GYR_scale_exp($cpu, 100) . "\"></div> ";
        $tooltip_str .= "CPU: $cpu %\\r\\n";
    }
    if($memory !== false) $tooltip_str .= "Memory: " . round($memory / 1048576) . " Mb\\r\\n";
    if($temperature !== false) $indicators_html .= "temp<div class=\"led " . ($temperature > 1 ? ' lr' : ' lg') . "\"></div> ";
    $indicators_html .= "upd<div id=\"{$host['hostid']}_upd\" class=\"led lg\"></div> ";

    $host_html .= "<div class=\"indicators\">$indicators_html</div>";

    $host_html .= "<script>\r\n$('#{$host['hostid']}').attr('title', '$tooltip_str');\r\n</script>\r\n";
    $host_html .= "<br>";

    $keys_for_normalize = array('Broadcast Rx' => 'ifInBroadcastPkts',
                        'Broadcast Tx' => 'ifOutBroadcastPkts',
                        'Traffic Rx' => 'ifInOctets',
                        'Traffic Tx' => 'ifOutOctets',
                        'Errors Rx' => 'ifInErrors',
                        'Errors Tx' => 'ifOutErrors');
    $headers_keys = array('ifAlias' => 'ifAlias', 'LastChange' => 'ifLastChange');
    $keys_tooltip = array_merge($headers_keys, $keys_for_normalize);

    function cmp($a, $b)
    {
        if ($a['number'] == $b['number']) {
            return 0;
        }
        return ($a['number'] < $b['number']) ? -1 : 1;
    }
    //echo '<pre>';
   // print_r($ports_info);echo '</pre>';
    usort($ports_info, "cmp");


    foreach($ports_speed as $speed)
    {
        foreach($ports_info as $k => $port_items)
        {
            if($port_items['speed'] != $speed) continue;

            if($port_items['ifAdminStatus']['lastvalue'] == '1')
            {
                if($port_items['ifOperStatus']['lastvalue'] == '1') $port_status = 'up';
                else $port_status = 'down';
            }
            else $port_status = 'disable';

            $host_html .= "<div class=\"port $port_status {$port_items['speed']}\"";
            $host_html_style = '';
            if($port_status == 'up')
            {
                if(!empty($port_items['ifInErrors']) && !empty($port_items['ifOutErrors']) && $port_items['ifInErrors']['lastvalue'] + $port_items['ifOutErrors']['lastvalue'] > 0)
                {
                    $host_html_style .= "border-color: orange;";
                    //print_r($port_items);
                }

                if(!empty($port_items['ifInOctets']) && !empty($port_items['ifOutOctets']))
                {
                    $host_html_style .= "background-color: #" . GYR_scale_exp(max($port_items['ifInOctets']['lastvalue'], $port_items['ifOutOctets']['lastvalue']), $port_items['speed'] == 'ge' ? 1e9 : 1e8) . ";";
                }
            }
			if($host_html_style) $host_html .= " style=\"$host_html_style\"";
            $host_html .= " id=\"{$port_items['ifOperStatus']['itemid']}\">{$port_items['number']}</div>\r\n";
            $tooltip_str = '';

            if ($port['ifAlias'])
            {
                $tooltip_str .= "Alias: " . $port_items['ifAlias']['lastvalue'] . "\\r\\n";
            }
            if ($port['ifLastChange'])
            {
                //$lastchange = sprintf("%dd %02d:%02d", $port['ifLastChange'] / 86400, $port['ifLastChange'] % 86400 / 3600,  $port['ifLastChange'] % 3600 / 60);
                $tooltip_str .= "LastChange: " . sprintf("%dd %02dh %02dm", $port_items['ifLastChange']['lastvalue'] / 86400, $port_items['ifLastChange']['lastvalue'] % 86400 / 3600,  $port_items['ifLastChange']['lastvalue'] % 3600 / 60)."\\r\\n";
            }

            foreach($keys_for_normalize as $title => $key)
            {
                if(!empty($port_items[$key]))
                {
                    //echo "$key<HR>";
                    //print_r($port_items[$key]);
                    $tooltip_str .= "$title: " . normalize(intval($port_items[$key]['lastvalue'])) . $port_items[$key]['units'] . " " . "\\r\\n";
                }
            }
            $host_html .= "<script>\r\n$('#{$port_items['ifOperStatus']['itemid']}').attr('title', '$tooltip_str');\r\n</script>\r\n";
        }
    }

    if($has_ports) echo $host_html;
}
else
{
    avost();
}

function get_item_key($item) {
    return substr(strrchr($item['snmp_oid'], "."), 1);
}

function normalize($x, $dec=2)
{
    if($x == 0) return $x;
    $prefixes = array('', 'k', 'M', 'G', 'T', 'P');
    $n = floor(log(abs($x), 1000));
    return sprintf("%.{$dec}f", $x / pow(1000, $n)) . ($n ? " " . $prefixes[$n] : '');
}

function GYR_scale($x, $max)
{
    if($x > $max) $x = $max;
    if($x / $max < 0.5) return sprintf("%02x", round($x / $max * 510)) . 'ff00';
    else return 'ff' . sprintf("%02x", round(255 - ($x - $max * 0.5) / $max * 2 * 255)) . '00';
}

function GYR_scale_exp($x, $max)
{
    $p = $x / $max * 100;
	if($p > 100) $x = 100;
	if($p < 0) $x = 0;
	$line = array(0=>0,1=>12,2=>24,3=>36,4=>47,5=>58,6=>68,7=>77,8=>86,9=>93,10=>100,11=>106,12=>112,13=>117,14=>123,15=>128,16=>133,17=>137,18=>142,19=>146,20=>150,21=>154,22=>158,23=>161,24=>165,25=>168,26=>172,27=>175,28=>178,29=>182,30=>185,31=>188,32=>191,33=>195,34=>198,35=>201,36=>204,37=>207,38=>211,39=>214,40=>217,41=>221,42=>224,43=>228,44=>231,45=>235,46=>239,47=>243,48=>247,49=>251,50=>255,51=>259,52=>264,53=>268,54=>272,55=>277,56=>281,57=>286,58=>290,59=>295,60=>299,61=>304,62=>309,63=>313,64=>318,65=>323,66=>328,67=>333,68=>337,69=>342,70=>347,71=>352,72=>357,73=>362,74=>367,75=>372,76=>377,77=>383,78=>388,79=>393,80=>398,81=>404,82=>409,83=>414,84=>420,85=>425,86=>430,87=>436,88=>441,89=>447,90=>453,91=>458,92=>464,93=>469,94=>475,95=>481,96=>487,97=>492,98=>498,99=>504,100=>510);
    $clr = $line[round($p)];
	return ($clr < 256 ? sprintf('%02xff', $clr) : sprintf('ff%02x', 510 - $clr)) . '00';
}

function G_scale($x, $max)
{
    if($x > $max) $x = $max;
    return '00' . sprintf("%02x", round($x / $max * 128) + 127) . '00';
}

function Y_scale($x, $max)
{
    if($x > $max) $x = $max;
    return 'cc' . sprintf("%02x", round($x / $max * 128) + 127) . '00';
}

function avost()
{
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    exit;
}
?>
