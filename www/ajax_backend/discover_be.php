<?php
// $Id: discover.php,v 1.11 2006/08/29 10:23:39 guizy Exp $
//
 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
 require "../../etc/prbconfig.php";
 require $cfg->libPath."/Info.php";
 require $cfg->libPath."/Web.php";
#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 session_start();

 $action = $_REQUEST['act'];
 $mode   = $_REQUEST['mode'];
 if( $mode == '' ){ 
	$mode = 'snmp';
 }

 $w = new Web();

echo "Mode: $mode";
if( $mode == 'manual' ) {

 switch( $action ) {
    case 'manualdiscovery':
        $host = $_POST['host'];
        $host['ip'] = getHostByName("$host[name]");
        $sql = "insert into host 
                    (
                        `name`,
                        `ip`,
                        `sysDescr`,
                        `description`,
                        `vendor`,
                        `OS`,
                        `location`,
                        `status`     ) 
                    values (
                        '$host[name]',
                        '$host[ip]',
                        '$host[sysDescr]',
                        '$host[description]',
                        '$host[vendor]',
                        '$host[OS]',
                        '$host[location]',
                        'polling'    )";

        mysql_query($sql) or $error = mysql_error();
        if( $error ) {
            print "Error: $error<P>";
            if(substr('Duplicate entry', $error)) print "Click <a href=?p=browse.php&host=$host[name]>here</a> to continue...<p>";
        } else {
            echo $w->showAddForm($host[name], 'discoveryForm');
        }
        exit;
    break;
    default:
        print $w->manualDiscoveryForm();
    break;
 }

} elseif( $mode == 'snmp' ) {
#
# Vars
#
 snmp_set_quick_print(TRUE);
 $status = 'polling';
 $host      = $_REQUEST['host'];
 $hostname  = strtolower($host['name']);
 $community = $host['community'];
 if( $community == '' ) { $community = "public"; }

 switch( $action ) {
    case 'sysinfo':
	$f = new Form(array("name"=>"hostinfo"));
        $ip = gethostbyname($hostname);
        $f->frmOpenFieldset(array("name"=>"hostinfo", "legend"=>"Host Info") );
	showHostInfo(getHostInfo($hostname),$f);
        $f->frmCloseFieldset();
	print $f->frmOutputHTML();
    break;
    case 'snmpdiscovery':
            # check for host in database
            $r = mysql_query("select * from host where host.name='$hostname'");
            $hostInfo = mysql_fetch_object($r);
            $ip = gethostbyname($hostname);
            if(! $hostInfo ) {
                $hostInfo = getHostInfo($hostname);
            }

            # Get availability and CPU stuff
            $uptimeInfo = getUptimeInfo($hostname);
            $cpuInfo    = getCPUInfo($hostname);
            # Get interfaces info
            $ifInfo = getIfInfo($hostname);
            $html  = "
<script type='text/javascript'>
window.addEvent('domready', function() {
    var cauca = $('cauca');
    cauca.addEvent('click', function() {
      if(cauca.get('rel') == 'yes') {
      do_check = false;
      cauca.set('src','images/uncheck.jpg').set('rel','no');
    }
    else {
      do_check = true;
      cauca.set('src','images/check.jpg').set('rel','yes');
    }
    $$('input[type=checkbox]').each(function(el) { el.checked = do_check; });
  });
});
</script>
            ";
            $html .= "<table>";
            $html .= "<tr><th><img src='images/uncheck.jpg' id='cauca' style='cursor: hand;'></th><th>ifIndex</th><th>rec name</th><th>ifDescr</th><th>ifAlias</th><th>ifType</th><th>Status</th></tr>";
            for($i=0; $i<count($ifInfo); $i++) {
                $html .= showIfInfo($ifInfo[$i], $i);
            }
            $html .= "</table>";

            $f = new Form( array( "name"=>"snmpForm", "action"=>"$cfg->backEndPath/discover_be.php" ));
            $f->frmAddInput('hidden', array("name"=>"mode", "value"=>"snmp"));
            $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$hostname"));
            $f->frmAddInput('hidden', array("name"=>"community", "value"=>"$community"));
            $f->frmOpenFieldset(array("name"=>"hostinfo", "legend"=>"Host Info") );
            showHostInfo($hostInfo,$f);
            $f->frmCloseFieldset();
            $f->frmOpenFieldset(array("name"=>"cpuinfo", "legend"=>"CPU Info") );
            showCPUInfo($cpuInfo,$f);
            $f->frmCloseFieldset();
            showUptimeInfo($uptimeInfo,$f);
            $f->frmOpenFieldset(array("name"=>"ifinfo", "legend"=>"Discovered interfaces") );
            $f->frmAddHtml($html);
            $f->frmCloseFieldset();
            $f->frmOpenFieldset(array("name"=>"actions", "legend"=>"Actions") );
            $f->frmAddButton('submit', array( "name"=>"act", "value"=>"add", "image"=>"accept.png", "text"=>"Add"));
            $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel", "class"=>"red"));
            $f->frmCloseFieldset();
            $f->frmAjaxActivate('infoBox');
            print $f->frmOutputHTML();

        break;
    case 'add':
            $hostInfo = $_REQUEST['hostInfo'];
            $cpuInfo = $_REQUEST['cpuInfo'];
            $uptimeInfo = $_REQUEST['uptimeInfo'];
            $ifInfo = $_REQUEST['ifInfo'];
            $selected_fld = $_REQUEST['selected_fld'];

            $sql = sqlHostInfo($hostInfo);
            mysql_query($sql) or die("Error is SQL: <div class=code>".$sql."</div>".mysql_error());
            $sql = sqlUptimeInfo($uptimeInfo);
            mysql_query($sql) or die("Error is SQL: <div class=code>".$sql."</div>".mysql_error());
            $sql = sqlCPUInfo($cpuInfo);
            mysql_query($sql) or die("Error is SQL: <div class=code>".$sql."</div>".mysql_error());
            for($i=0; $i<count($selected_fld); $i++) {
                $sql = sqlIfInfo($ifInfo[$selected_fld[$i]]);
                mysql_query($sql) or die("Error is SQL: <div class=code>".$sql."</div>".mysql_error());
                $sql = sqlIfErrInfo($ifInfo[$selected_fld[$i]]);
                mysql_query($sql) or die("Error is SQL: <div class=code>".$sql."</div>".mysql_error());
            }
            print "Successfully added $hostname...<P>";
        break;
    default:
            print $w->snmpDiscoveryForm();
        break;
 }
} // End snmp section

#
# Functions
#

 function getHostInfo($host) {
    global $community, $status, $ip, $sysDescr;
    
    //$sysDescr = snmpget("$host","$community","SNMPv2-MIB::system.sysDescr.0"); // or warn("SNMPGET failed for $host\n");
    $sysDescr = snmpget("$host","$community","iso.3.6.1.2.1.1.1.0"); // or warn("SNMPGET failed for $host\n");
    $sysLocation = snmpget("$host","$community","SNMPv2-MIB::system.sysLocation.0");
    if( $sysLocation == '' ) { $sysLocation = 'unknown'; }

    if (strpos(strtoupper($sysDescr), 'AIX')    !== false) { $OS = 'AIX'; $vendor='IBM'; }
    if (strpos(strtoupper($sysDescr), 'SUN')    !== false) { $OS = 'Solaris'; $vendor='Sun'; }
    if (strpos(strtoupper($sysDescr), 'CISCO')  !== false) { $OS = 'IOS';   $vendor='Cisco'; }
    if (strpos(strtoupper($sysDescr), 'TRU64')  !== false) { $OS = 'Tru64'; $vendor='HP'; }
    if (strpos(strtoupper($sysDescr), 'HP-UX')  !== false) { $OS = 'HP-UX'; $vendor='HP'; }
    if (strpos(strtoupper($sysDescr), 'X86')    !== false) { $OS = 'Windows'; $vendor='HP'; }
    if (strpos(strtoupper($sysDescr), 'LINUX')  !== false) { $OS = 'Linux'; $vendor='Linux'; }

    $hostInfo->name         = $host;
    $hostInfo->sysDescr     = $sysDescr;
    $hostInfo->ip           = $ip;
    $hostInfo->vendor       = $vendor;
    $hostInfo->OS           = $OS;
    $hostInfo->location     = $sysLocation;
    $hostInfo->status       = $status;

    return $hostInfo;
 }

 function showHostInfo($hostInfo, $f) {
    $f->frmAddInput('text', array("label"=>"Host name or IP address", "name"=>"hostInfo[name]", "value"=>"$hostInfo->name"));
    $f->frmAddInput('text', array("label"=>"DNS resolved IP address", "name"=>"hostInfo[ip]", "value"=>"$hostInfo->ip"));
    $f->frmAddTextarea(array("label"=>"System description text", "name"=>"hostInfo[sysDescr]", "value"=>"$hostInfo->sysDescr", "cols"=>"60"));
    $f->frmAddInput('text', array("label"=>"Vendor", "name"=>"hostInfo[vendor]", "value"=>"$hostInfo->vendor"));
    $f->frmAddInput('text', array("label"=>"Operating system", "name"=>"hostInfo[OS]", "value"=>"$hostInfo->OS"));
    $f->frmAddInput('text', array("label"=>"System location", "note"=>"(As specified in SNMP sysLocation) Change this to something sensible if necessary.", "name"=>"hostInfo[location]", "value"=>"$hostInfo->location"));
    $f->frmAddInput('hidden', array("name"=>"hostInfo[status]", "value"=>"$hostInfo->status"));

 }

 function sqlHostInfo($hostInfo) {
    $_ql = "INSERT INTO host (`name`, `ip`, `sysDescr`, `description`, `vendor`, `OS`, `location`, `status`)
            VALUES('$hostInfo[name]', '$hostInfo[ip]', '$hostInfo[sysDescr]', '$hostInfo[host] ($hostInfo[ip])', 
                    '$hostInfo[vendor]', '$hostInfo[OS]', '$hostInfo[location]', '$hostInfo[status]');";
    return $_ql;
 }

 function getUptimeInfo($host) {
    global $community, $status, $ip, $sysDescr;
    
    $uptime = snmpget("$host","$community",".1.3.6.1.2.1.1.3.0") or print "System not giving up uptime...\n";;
    $uptimeInfo->name = $host."_availability_0";
    $uptimeInfo->module = 'availability';
    $uptimeInfo->grouping = 'Availability';
    $uptimeInfo->description = $ip." - Uptime and availability";
    $uptimeInfo->host = $host;
    $uptimeInfo->community = $community;
    if(! $uptime ) $uptimeInfo->status = 'noUptime'; else $uptimeInfo->status = 'polling';
    
    return $uptimeInfo;
 }

 function sqlUptimeInfo($uptimeInfo) {
    $_ql = "INSERT INTO info (`name`, `module`, `description`, `host`, `community`, `grouping`)
            VALUES(
                '$uptimeInfo[name]', 
                '$uptimeInfo[module]',  
                '$uptimeInfo[description]', 
                '$uptimeInfo[host]',
                '$uptimeInfo[community]', 
                '$uptimeInfo[grouping]'
                )";
    return $_ql;
 }

 function showUptimeInfo($uptimeInfo,$f) {
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[name]", "value"=>"$uptimeInfo->name"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[module]", "value"=>"$uptimeInfo->module"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[description]", "value"=>"$uptimeInfo->description"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[grouping]", "value"=>"$uptimeInfo->grouping"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[host]", "value"=>"$uptimeInfo->host"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[community]", "value"=>"$uptimeInfo->community"));
    $f->frmAddInput('hidden', array("name"=>"uptimeInfo[status]", "value"=>"$uptimeInfo->status"));
 }

 function getIfInfo($host) {
    global $community, $status, $ip, $sysDescr;

    $ifIndex = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.1") or print "No response on ifIndex<br />";
    $ifDescr = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.2") or print "No response on ifDescr<br />";
    $ifType = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.3") or print "No response on ifType<br />";
    $ifPhysAddress = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.6") or print "No response on ifPhysAddress<br />";
    $ifAdminStatus = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.7") or print "No response on ifAdminStatus<br />";
    $ifOperStatus = snmpwalk("$host","$community",".1.3.6.1.2.1.2.2.1.8") or print "No response on ifOperStatus<br />";
    # the alias
    $ifAlias = snmpwalk("$host","$community",".1.3.6.1.2.1.31.1.1.1.18") or print "No response on ifAlias<br />";
    
    #
    # Get the ip address table
    #
    $ipAdd = snmpwalk("$host","$community",".1.3.6.1.2.1.4.20.1.1") or print "No response on ipAddress<br />";
    $ipIdx = snmpwalk("$host","$community",".1.3.6.1.2.1.4.20.1.2") or print "No response on ipAddressIndex<br />";
    for($i=0; $i<count($ipIdx); $i++){
        $ifIpAddr[$ipIdx[$i]] = $ipAdd[$i];
    }
    ksort($ifIpAddr);

    #
    # fill the vars
    #
    for($i=0; $i<count($ifIndex); $i++){
        //if( $ifOperStatus[$i]!='up' ) { $status = 'ifDown'; } else { $status = 'polling'; }
        $status = 'polling'; 
        if( preg_match("/unrouted vlan/i", $ifDescr[$i]) ) { $status = 'dontPoll'; } 
        $ifInfo[$i]->name        = $host."_port_".$ifIndex[$i];
        $ifInfo[$i]->name2       = $host."_errors_".$ifIndex[$i];
        $ifInfo[$i]->module      = "port";
        $ifInfo[$i]->module2     = "errors";
        $ifInfo[$i]->grouping    = "Network";
        $ifInfo[$i]->ifIndex     = $ifIndex[$i];
        $ifInfo[$i]->ifDescr     = $ifDescr[$i];
        $ifInfo[$i]->ifAlias     = $ifAlias[$i];
        $ifInfo[$i]->ifType      = $ifType[$i];
        $ifInfo[$i]->ifPhysAddr  = $ifPhysAddress[$i];
        $ifInfo[$i]->description = $ifIpAddr[$ifIndex[$i]]." (".$ifPhysAddress[$i].") - ".$ifDescr[$i];
        $ifInfo[$i]->host        = $host;
        $ifInfo[$i]->community   = $community;
        $ifInfo[$i]->status      = $status;
    }
    return $ifInfo;
 }           

 function showIfInfo($ifInfo, $i) {

    $checked = '';
    if( $ifInfo->status == 'polling' ) { $checked = 'checked'; }
    $checkBox = "<input type='checkbox' $checked name='selected_fld[]' value='$i' id='checkbox_row_$i'  />";
    $onMouseDown = "\"document.getElementById('checkbox_row_$i').checked = (document.getElementById('checkbox_row_$i').checked ? false : true);\"";
    $html = "<div id=ifInfo_$i>
            <tr onmousedown=$onMouseDown>
                <td>$checkBox</td>
                <td>$ifInfo->ifIndex</td>
                <td>$ifInfo->name</td>
                <td>$ifInfo->description</td>
                <td>$ifInfo->ifAlias</td>
                <td>$ifInfo->ifType</td>
                <td>$ifInfo->status</td>
            </tr>
            <input type=hidden name=ifInfo[$i][name]        value='$ifInfo->name'>
            <input type=hidden name=ifInfo[$i][name2]        value='$ifInfo->name2'>
            <input type=hidden name=ifInfo[$i][module]      value='$ifInfo->module'>
            <input type=hidden name=ifInfo[$i][module2]     value='$ifInfo->module2'>
            <input type=hidden name=ifInfo[$i][grouping]    value='$ifInfo->grouping'>
            <input type=hidden name=ifInfo[$i][ifIndex]     value='$ifInfo->ifIndex'>
            <input type=hidden name=ifInfo[$i][ifDescr]     value='$ifInfo->ifDescr'>
            <input type=hidden name=ifInfo[$i][ifAlias]     value='$ifInfo->ifAlias'>
            <input type=hidden name=ifInfo[$i][description] value='$ifInfo->description'>
            <input type=hidden name=ifInfo[$i][host]        value='$ifInfo->host'>
            <input type=hidden name=ifInfo[$i][community]   value='$ifInfo->community'>
            <input type=hidden name=ifInfo[$i][status]      value='$ifInfo->status'>
            </div>";

    return $html;
 }

 function sqlIfInfo($ifInfo) {
        $_ql = "INSERT INTO info (`name`, `module`, `ifIndex`, `ifDescr`, `description`, `connection`, `host`, `community`, `status`, `grouping`)
                VALUES(
                    '$ifInfo[name]', 
                    '$ifInfo[module]',
                    '$ifInfo[ifIndex]',
                    '$ifInfo[ifDescr]',
                    '$ifInfo[description]',
                    '$ifInfo[ifAlias]',
                    '$ifInfo[host]', 
                    '$ifInfo[community]', 
                    '$ifInfo[status]',
                    '$ifInfo[grouping]'
                    )";
        return $_ql;
 }

 function sqlIfErrInfo($ifInfo) {
        $_ql = "INSERT INTO info (`name`, `module`, `ifIndex`, `ifDescr`, `description`, `connection`, `host`, `community`, `status`, `grouping`)
                VALUES(
                    '$ifInfo[name2]', 
                    '$ifInfo[module2]',
                    '$ifInfo[ifIndex]',
                    '$ifInfo[ifDescr]',
                    '$ifInfo[description]',
                    '$ifInfo[ifAlias]',
                    '$ifInfo[host]', 
                    '$ifInfo[community]', 
                    '$ifInfo[status]',
                    'Errors'
                    )";
        return $_ql;
 }

 function getCPUInfo($host) {
    global $community, $status, $ip, $hostInfo;
    $sysDescr = $hostInfo->sysDescr;

    if (strpos(strtoupper($sysDescr), 'AIX')    !== false) { $module = 'aixcpu'; }
    if (strpos(strtoupper($sysDescr), 'LINUX')  !== false) { $module = 'ucdcpu'; }
    if (strpos(strtoupper($sysDescr), 'CISCO')  !== false) { $module = 'ciscocpu'; }
    if (strpos(strtoupper($sysDescr), 'SUN')    !== false) { $module = 'ucdload'; }
    if (strpos(strtoupper($sysDescr), 'TRU64')  !== false) { $module = 'cpq4cpu'; }
    if (strpos(strtoupper($sysDescr), 'HP-UX')  !== false) { $module = 'hpuxload'; }
    if (strpos(strtoupper($sysDescr), 'X86')    !== false) { $module = 'x86_4cpu'; }

    if( $module != '' ) {

        $cpuInfo->name = $host."_".$module."_0";
        $cpuInfo->module = $module;
        $cpuInfo->grouping = "System";
        $cpuInfo->description = "$host ($ip) - CPU Load";
        $cpuInfo->host = $host;
        $cpuInfo->community = $community;
        $cpuInfo->status = $status;

        return $cpuInfo;
    }
    return;
 }

 function showCPUInfo($cpuInfo, $f) {
    $f->frmAddInput('text', array("label"=>"Name", "name"=>"cpuInfo[name]", "value"=>"$cpuInfo->name" ));
    $f->frmAddInput('text', array("label"=>"Module", "name"=>"cpuInfo[module]", "value"=>"$cpuInfo->module" ));
    $f->frmAddInput('text', array("label"=>"Description", "name"=>"cpuInfo[description]", "value"=>"$cpuInfo->description", "size"=>"100" ));
    $f->frmAddInput('hidden', array("name"=>"cpuInfo[host]", "value"=>"$cpuInfo->host" ));
    $f->frmAddInput('hidden', array("name"=>"cpuInfo[grouping]", "value"=>"$cpuInfo->grouping" ));
    $f->frmAddInput('hidden', array("name"=>"cpuInfo[community]", "value"=>"$cpuInfo->community" ));
    $f->frmAddInput('hidden', array("name"=>"cpuInfo[status]", "value"=>"$cpuInfo->status" ));
 }

 function sqlCPUInfo($cpuInfo) {
    $_ql = "INSERT INTO info (`name`, `module`, `description`, `host`, `community`, `grouping`)
            VALUES(
                '$cpuInfo[name]',
                '$cpuInfo[module]',
                '$cpuInfo[description]',
                '$cpuInfo[host]', 
                '$cpuInfo[community]',
                '$cpuInfo[grouping]'
                )";

    return $_ql;
 }

?>
</div>
