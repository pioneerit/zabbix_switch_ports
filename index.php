<?php

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "config.inc.php");
// User from config for connection with Zabbix API
$user = ZABBIX_USER;
$pass = ZABBIX_PASS;
$url = ZABBIX_URL;

/*
 * Авторизация на самом скрипте
 * Пользователи: имя, пароль, группа узлов, показывать карту?
 */
$users = array(
			'admin' => array('pass' => 'adminpass', 'group' => 7, 'show_map' => 0),
			'user' => array('pass' => 'userpass', 'group' => 2, 'show_map' => 0)
		);

// URL карты http://логин:пароль@хост/путь. Логин и пароль указываются в get_map.php
$map_URL = "https://map:mappass@zabbix.jayhosting.local/zabbix_ports/get_map.php";

if(empty($users[$_SERVER['PHP_AUTH_USER']]) || $_SERVER['PHP_AUTH_PW'] != $users[$_SERVER['PHP_AUTH_USER']]['pass'])
{
    header('WWW-Authenticate: Basic realm=" @( * O * )@ "');
    header('HTTP/1.0 401 Unauthorized');
    echo 'who are you?';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=utf-8">
<style>
* {
margin: 0;
padding: 0;
font-family: Verdana, Arial, sans-serif;
}

.device {
border: 3px double black;
background-color: #666;
padding: 3px;
margin: 10px;
color: white;
font-size: 14px;
float: left;
cursor: help;
display:-moz-inline-stack;
display:inline-block;
zoom:1;
*display:inline;
}

.port {
padding: 1px;
color: black;
float: left;
text-align: center;
cursor: help;
display:-moz-inline-stack;
display:inline-block;
}

.up {
background-color: #3f3;
}

.down {
    background-color: #ccc;
}

.ge {
    margin: 1px;
    border: 3px double black;
}

.fe {
    margin: 3px 1px 1px 1px;
    border: 1px solid black;
}

.name_label {
    float: left;
    display:-moz-inline-stack;
    display:inline-block;
}

.indicators {
    float: right;
    display:-moz-inline-stack;
    display:inline-block;
    font-size: 9px;
    height: 17px;
    margin-left: 10px;
}

.led {
    width: 5px;
    height: 5px;
    /*display:-moz-inline-stack;*/
    display:inline-block;
}

.lr {
    background-color: #f00;
}

.lg {
    background-color: #0f0;
}

.ly {
    background-color: #ff0;
}
</style>
<link href="js/ports/jquery.tooltip.css" rel="stylesheet">
<script type="text/javascript" src="js/ports/jquery-1.8.0.min.js"></script>
<script type="text/javascript" src="js/ports/jquery.dimensions.js"></script>
<script type="text/javascript" src="js/ports/jquery.tooltip.js"></script>
<script type="text/javascript" src="js/ports/jquery.cookie.js"></script>
<script type="text/javascript">
$(function() {
	$('input[name=size]').change(function() {
		if($('input[name=size]:checked').val() == 'big')
		{
			$(".port").css('height', '15px');
			$(".port").css('width', '15px');
			$(".port").css('font-size', '11px');
			//$.cookie('the_cookie', 'the_value', { expires: 7 });
		}
		else
		{
			$(".port").css('height', '10px');
			$(".port").css('width', '10px');
			$(".port").css('font-size', '8px');
		}
	});

	$('input[name=position]').change(function() {
		if($('input[name=position]:checked').val() == 'vertical')
		{
			$(".device").css('float', 'none');
		}
		else
		{
			$(".device").css('float', 'left');
		}
	});

    $('input[name=refresh]').change(function() {
        if($('input[name=refresh]').is(':checked') == true) refresh();
    });

	$('#map_switch').click(function() {
		$('#map').toggle();
	});

	$('input[name=size]').change();
	$('input[name=position]').change();
    $('input[name=refresh]').change();
});

var cur = 0;

function refresh()
{

    if($('input[name=refresh]').is(':checked') == false) return;
    if(cur == hostids.length)
    {
        cur = 0;
        //console.log(cur);
		$('#map').attr('src', '<?php echo $map_URL; ?>?' + Math.random());
        //setTimeout(refresh, 5000);
        setTimeout(refresh, 30000);
        return;
    }

    var hostId = hostids[cur];
    cur++;
    console.log(cur);

    $('#' + hostId + '_upd').removeClass('lg lr');
    $('#' + hostId + '_upd').addClass('ly');

    jQuery.ajax({
        type: "POST",
        url: "ports_ajax.php",
        cache: false,
        context: $('#'+hostId),
        dataType:"html",
        data:{hostid: hostId},
        success:function(response){
            $(this).html(response);
            $('input[name=size]').change();
            $('input[name=position]').change();
            $('#' + $(this).attr('id') + '_upd').removeClass('ly');
            $('#' + $(this).attr('id') + '_upd').addClass('lg');
            refresh();
        },
        error:function (xhr, ajaxOptions, thrownError){
            $('#' + $(this).attr('id') + '_upd').removeClass('ly');
            $('#' + $(this).attr('id') + '_upd').addClass('lr');
            refresh();
        }
    });
}
</script>

</head>
<body>
<?php if($users[$_SERVER['PHP_AUTH_USER']]['show_map']) { ?>
<a href="#" id="map_switch">карта</a><br>
<img src="<?php echo $map_URL; ?>" style="display: none" id="map"><br>
<?php } ?>
Размер: <input type="radio" id="small_size" name="size" value="small"><label for="small_size">мелко</label>
<input type="radio" id="big_size" name="size" value="big" checked><label for="big_size">крупно</label><br>
Расположение: <input type="radio" id="vertical_pos" name="position" value="vertical" checked><label for="vertical_pos">вертикально</label>
<input type="radio" id="pyle_pos" name="position" value="pyle"><label for="pyle">в кучу</label><br>
<input type="checkbox" value="refresh" name="refresh" id="refresh" checked><label for="refresh">обновлять</label>

<div id="switches">
<?php
error_reporting(E_ALL);
require_once("ZabbixAPI.class.php");
ZabbixAPI::debugEnabled(TRUE);
$zlogin = ZabbixAPI::login($url, $user, $pass);
if($zlogin)
{
    $hosts = ZabbixAPI::fetch_array('host','get',array('output'=>'extend', 'groupids'=>array($users[$_SERVER['PHP_AUTH_USER']]['group']), 'monitored'=>1, 'sortfield'=>'host'));
    $hostids = array();
    //print_r($hosts);
    foreach($hosts as $k => $host)
    {
		$host_html = '';
		$host_html .= "<div class=\"device\" id=\"{$host['hostid']}\"><div class=\"name_label\">{$host['host']}</div>";
        $hostid = $host['hostid'];

        $items = ZabbixAPI::fetch_array('item','get',array('hostids'=>array($hostid), 'output'=>'extend')); //print_r($items);
		$has_ports = false;

        foreach($items as $item)
        {
            //if(preg_match('/Status port ([GF]E )?([0-9]+)/', $item['name'], $regs)) $has_ports = true;
            if(preg_match('/Operational status of interface/', $item['name'], $regs)) $has_ports = true;
//echo "<pre>";
//print_r($item);
//echo "</pre></br>";

        }

        $indicators_html = "snmp<div class=\"led " . ($host['snmp_available'] == 2 ? ' lr' : ' lg') . "\"></div> ";
        $indicators_html .= "upd<div id=\"{$host['hostid']}[upd]\" class=\"led ly\"></div> ";

        $host_html .= "<div class=\"indicators\">$indicators_html</div>";

        $tooltip_str = '';

        if($host['snmp_available'] == 2)
        {
            $tooltip_str .= "<em>Error: {$host['snmp_error']}</em><br>";
        }

        $tooltip_str .= "IP: {$host['ip']}<br>" .
                        "DNS: {$host['dns']}";
        //$host_html .= "<script>\r\n$('#{$host['hostid']}').attr('title', '$tooltip_str');\*\r\n$('#{$host['hostid']}').tooltip();*\\r\n</script>\r\n";
        $host_html .= "<script>\r\n$('#{$host['hostid']}').attr('title', '$tooltip_str');\r\n</script>\r\n";
		$host_html .= "<br>";

        $host_html .= '<div class="port"> </div>';

		$host_html .= "</div><br>\r\n\r\n";

        if($has_ports)
        {
            echo $host_html;
            $hostids[] = $hostid;
        }

		//if($k == 3)	break;

    }

    // $hostids = join(',', $hostids);
    //echo "\r\n<script>\r\nvar hostids = new Array($hostids);\r\n</script>";
    echo "\r\n<script>\r\nvar hostids = ".json_encode($hostids).";\r\n</script>";
}
else echo "Нет соединения с zabbix";

?>
</div>
</body>
</html>
