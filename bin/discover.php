#!/usr/bin/php5 -e
<?php
// $Id: discover.php,v 1.1.1.1 2006/07/09 11:06:15 guizy Exp $
//
#
# Command line args
#
 $me = $argv[0];
 if( $argc != 2 ) {
    print "Usage: $me <host> [<community>]\n";
    print "\tYou should really use the web interface for discovery.\n";
    print "\tIt's much more friendly...\n";
    exit;
 }

#
# Config section
#
 require dirname(__FILE__)."/../etc/prbconfig.php";

#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

#
# Vars
#
 $host = $argv[1];
 $community = "public";
 if( defined($argv[2]) ) { $community =$argv[2]; }

#
# Defaults
#
 snmp_set_quick_print(TRUE);
 $status = 'polling';

#
# Host information
#
 $sql = getHostInfo();
 print "$sql\n";
 mysql_query($sql) or die(mysql_error());

#
# Uptime module
#
 $sql = getUptimeInfo();
 print "$sql\n";
 mysql_query($sql) or die(mysql_error());

#
# Interfaces (is an array)
#
 $sql = getIfInfo();
 for($i=0; $i<count($sql); $i++){
    print "$i: $sql[$i]\n";
    mysql_query($sql[$i]) or die(mysql_error());
 }

#
# CPU information
#
 $sql = getCPUInfo();
 print "$sql\n";
 if( $sql!='' ) {
    mysql_query($sql) or die(mysql_error());
 }

#
# insert SQL
#


#
# Functions
#

 function getHostInfo() {
    global $host, $community, $status, $ip, $sysDescr;
    
    $sysDescr = snmpget("$host","$community","SNMPv2-MIB::system.sysDescr.0") or die("SNMPGET failed for $host\n");
    $sysLocation = snmpget("$host","$community","SNMPv2-MIB::system.sysLocation.0");
    if( $sysLocation == '' ) { $sysLocation = 'unknown'; }
    $ip = gethostbyname($host);

    if (strpos(strtoupper($sysDescr), 'LINUX')  !== false) { $OS = 'Linux'; $vendor='Linux'; }
    if (strpos(strtoupper($sysDescr), 'CISCO')  !== false) { $OS = 'IOS';   $vendor='Cisco'; }
    if (strpos(strtoupper($sysDescr), 'SUN')    !== false) { $OS = 'Solaris'; $vendor='Sun'; }
    if (strpos(strtoupper($sysDescr), 'COMPAQ') !== false) { $OS = 'Tru64'; $vendor='HP'; }
    if (strpos(strtoupper($sysDescr), 'HP-UX')  !== false) { $OS = 'HP-UX'; $vendor='HP'; }

    $_ql = "INSERT INTO host (`name`, `sysDescr`, `description`, `vendor`, `OS`, `location`, `status`)
            VALUES('$host', '$sysDescr', '$host ($ip) - $sysDescr', '$vendor', '$OS', '$sysLocation', '$status');";

    return $_ql;
 }

 function getUptimeInfo() {
    global $host, $community, $status, $ip, $sysDescr;
    $_ql = "INSERT INTO info (`name`, `module`, `description`, `hostDescription`, `host`, `community`)
            VALUES('".$host."_availability_0', 'availability',  
           '$host: ".$ip." - Uptime and availability', 
           '$host ($ip) - $sysDescr',
           '$host', '$community')";

    return $_ql;
 }

 function getIfInfo() {
    global $host, $community, $status, $ip, $sysDescr;

    $ifDescr = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifDescr");
    $ifIndex = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifIndex");
    $ifAdminStatus = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifAdminStatus");
    $ifOperStatus = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifOperStatus");
    $ifLastChange = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifLastChange");
    $ifType = snmpwalk("$host","$community","IF-MIB::interfaces.ifTable.ifEntry.ifType");
    #
    # Get the ip address table
    #
    $ipAdd = snmpwalk("$host","$community","ip.ipAddrTable.ipAddrEntry.ipAdEntAddr");
    $ipIdx = snmpwalk("$host","$community","ip.ipAddrTable.ipAddrEntry.ipAdEntIfIndex");
    for($i=0; $i<count($ipIdx); $i++){
        $ifIpAddr[$ipIdx[$i]] = $ipAdd[$i];
    }
    ksort($ifIpAddr);

    #
    # Make the query
    #
    for($i=0; $i<count($ifIndex); $i++){

        if( $ifOperStatus[$i]!='up' ) { $status = 'ifDown'; } else { $status = 'polling'; }
        $_ql[$i] = "INSERT INTO info (`name`, `module`, `ifIndex`, `ifDescr`, `description`, `hostDescription`, `host`, `community`, `status`)
                VALUES('".$host."_port_".$ifIndex[$i]."', 'port', '".$ifIndex[$i]."', '".$ifDescr[$i]."',
                '$host: ".$ifIpAddr[$ifIndex[$i]]." - ".$ifDescr[$i]."',
                '$host ($ip) - $sysDescr',
                '$host', '$community', '$status')";
    }
    return $_ql;
 }           

 function getCPUInfo() {
    global $host, $community, $status, $ip, $sysDescr;

    if (strpos(strtoupper($sysDescr), 'LINUX')  !== false) { $module = 'lincpu'; }
    if (strpos(strtoupper($sysDescr), 'CISCO')  !== false) { $module = 'ciscocpu'; }
    if (strpos(strtoupper($sysDescr), 'SUN')    !== false) { $module = 'solload'; }
    if (strpos(strtoupper($sysDescr), 'COMPAQ') !== false) { $module = 'cpq4cpu'; }
    if (strpos(strtoupper($sysDescr), 'HP-UX')  !== false) { $module = 'hpuxload'; }


    if( $module != '' ) {
        $_ql = "INSERT INTO info (`name`, `module`, `description`, `hostDescription`, `host`, `community`)
                VALUES('".$host."_".$module."_0', '$module',  
                '$host: ".$ip." - CPU Load', 
                '$host ($ip) - $sysDescr',
                '$host', '$community')";

        return $_ql;
    }
    return;
 }

?>
