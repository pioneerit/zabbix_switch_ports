<?php

function buildSwitches($groupid) {
    global $zlogin;
    $result_html = '';
    if($zlogin)
    {
        // $hosts = ZabbixAPI::fetch_array('host','get',array('output'=>'extend', 'groupids'=>array($users[$_SERVER['PHP_AUTH_USER']]['group']), 'monitored'=>1, 'sortfield'=>'host'));
        $hosts = ZabbixAPI::fetch_array('host','get',array('output'=>'extend', 'groupids'=>array($groupid), 'monitored'=>1, 'sortfield'=>'host'));
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

            // $tooltip_str .= "IP: {$host['ip']}<br>" .
            //                 "DNS: {$host['dns']}";
            //$host_html .= "<script>\r\n$('#{$host['hostid']}').attr('title', '$tooltip_str');\*\r\n$('#{$host['hostid']}').tooltip();*\\r\n</script>\r\n";
            $host_html .= "<script>\r\n$('#{$host['hostid']}').attr('title', '$tooltip_str');\r\n</script>\r\n";
            $host_html .= "<br>";

            $host_html .= '<div class="port"> </div>';

            $host_html .= "</div><br>\r\n\r\n";

            if($has_ports)
            {
                // echo $host_html;
                $result_html .= $host_html;
                $hostids[] = $hostid;
            }

            //if($k == 3)   break;

        }

        // $hostids = join(',', $hostids);
        //echo "\r\n<script>\r\nvar hostids = new Array($hostids);\r\n</script>";
        // echo "\r\n<script>\r\nvar hostids = ".json_encode($hostids).";\r\n</script>";
        $result_html .= "\r\n<script>\r\nvar hostids = ".json_encode($hostids).";\r\nrefresh(); </script>";
        return $result_html;
    }
    else return "Нет соединения с zabbix";
}