<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "zabbix_ports" . DIRECTORY_SEPARATOR ."config.inc.php");
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "zabbix_ports" . DIRECTORY_SEPARATOR ."ZabbixAPI.class.php");

ZabbixAPI::debugEnabled(TRUE);
$zlogin = ZabbixAPI::login(ZABBIX_URL, ZABBIX_USER, ZABBIX_PASS);
/*
 * Авторизация на самом скрипте
 * Пользователи: имя, пароль, группа узлов, показывать карту?
 */
$users = array(
            'admin' => array('pass' => 'adminpass', 'group' => 7, 'show_map' => 0),
            'user' => array('pass' => 'userpass', 'group' => 2, 'show_map' => 0)
        );

// URL карты http://логин:пароль@хост/путь. Логин и пароль указываются в get_map.php
$map_URL = "/zabbix_ports/get_map.php";

require_once('include/config.inc.php');
require_once('include/js.inc.php');
require_once('include/hosts.inc.php');
require_once('include/items.inc.php');
require_once "switches.php";

if (isset($_REQUEST['ajax']) && isset($_REQUEST['groupid']) && $_REQUEST['ajax']) {
    echo buildSwitches($_REQUEST['groupid']);
    exit;
}

include_once('include/page_header.php');
?>
<style>
* {
    margin: 0;
    padding: 0;
    font-family: Verdana, Arial, sans-serif;
}

.device {
    border: 3px groove black;
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

.one {
    -webkit-transform: rotate(-45deg);
    -moz-transform: rotate(-45deg);
    -o-transform: rotate(-45deg);
    filter:progid:DXImageTransform.Microsoft.Matrix(M11='0.707', M12='0.707', M21='-0.707', M22='0.707', SizingMethod="auto expand");
}

.two {
    -webkit-transform: rotate(45deg);
    -moz-transform: rotate(45deg);
    -o-transform: rotate(45deg);
    filter:
    progid:DXImageTransform.Microsoft.Matrix(M11='0.707', M12='0.707', M21='-0.707', M22='0.707', SizingMethod="auto expand");
    progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
}

.disable {
    background-color: #ccc;
}

.up {
    background-color: #3f3;
}

.down {
    background-color: #ff0;
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
<link href="zabbix_ports/js/ports/jquery.tooltip.css" rel="stylesheet">
<script type="text/javascript" src="zabbix_ports/js/ports/jquery-1.8.0.min.js"></script>
<script type="text/javascript" src="zabbix_ports/js/ports/jquery.dimensions.js"></script>
<script type="text/javascript" src="zabbix_ports/js/ports/jquery.tooltip.js"></script>
<script type="text/javascript" src="zabbix_ports/js/ports/jquery.cookie.js"></script>
<script type="text/javascript">
$(function() {

    $('input[name=size]').change(function() {
        $('select[name=Hostgroup]').on('change', function(e) {
            $.ajax(document.location.pathname + '?ajax=true', {
                dataType: 'html',
                data: {
                    groupid: e.target.value
                },
                success: function(response) {
                    $('#switches').html(response);
                }
            } )
        })
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
        setTimeout(refresh, $('input[name=refresh_time]').val()*1000);
        return;
    }

    var hostId = hostids[cur];
    cur++;
    console.log(cur);

    $('#' + hostId + '_upd').removeClass('lg lr');
    $('#' + hostId + '_upd').addClass('ly');

    jQuery.ajax({
        type: "POST",
        url: "zabbix_ports/ports_ajax.php",
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
size: <input type="radio" id="small_size" name="size" value="small"><label for="small_size">small</label>
<input type="radio" id="big_size" name="size" value="big" checked><label for="big_size">big</label><br>
disposition: <input type="radio" id="vertical_pos" name="position" value="vertical" checked><label for="vertical_pos">vertical</label>
<input type="radio" id="pyle_pos" name="position" value="pyle"><label for="pyle">pyle</label><br>
<input type="checkbox" value="refresh" name="refresh" id="refresh" checked><label for="refresh">auto-refresh</label>
<input type="input" value="30" name="refresh_time" id="refresh_time"></br>
<?php

function selectHtml($name, $options, $value = 'value', $label = 'label')  {
    $optionsHtml = '<option value="0">---</option>';
    foreach ($options as $o) {
        $optionsHtml .= ( '<option value="' . $o[$value] .'">' . $o[$label] . '</option>' );
    }
    return '<select name="' . $name. '">' . $optionsHtml . '</select>';
}

if($zlogin)
{
    $response = ZabbixAPI::fetch_array('hostgroup','get',array('output'=>Array("groupid", "name"), 'id'=>1));
    echo selectHtml('Hostgroup', $response, 'groupid', 'name');
}
?>
<div id="switches">
<?php
if (isset($_REQUEST['groupid'])) {
echo buildSwitches($groupid);
}
else echo 'Choose group';
?>
</div>
<?php
require_once('include/page_footer.php');
?>