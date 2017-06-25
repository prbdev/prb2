<?php
// $Id: discover.php,v 1.11 2006/08/29 10:23:39 guizy Exp $
//
 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());

 $mode = $_REQUEST[mode];
 if( $mode == '' ) $mode = 'snmp';

 function setSel($m) {
    global $mode;
    if( $mode == $m ) return "sel";
    return "nosel";
 }

$subMenu = "
<div id=subMenu>
Add new host to poll: 
<a class=".setSel('snmp')."   href=?p=discover.php&mode=snmp> discover by snmp </a> | 
<a class=".setSel('manual')." href=?p=discover.php&mode=manual> add by hand </a>
</div>";

$pageMain .= " <div id=infoBox>";

if( $mode == 'manual' ) {
    $pageMain .=  $w->manualDiscoveryForm();
} elseif( $mode == 'snmp' ) {
    $pageMain .=  $w->snmpDiscoveryForm();
} 

$pageMain .= "</div>";
?>
