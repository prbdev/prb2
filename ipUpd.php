#!/usr/local/bin/php
<?php
#
# $Id$
#

#
# Config section
#
 require dirname(__FILE__)."/etc/prbconfig.php";

# We'll do our own error reporting
 error_reporting(0);

#
# Get command line option
#
 if( getopt('d') ) { $debug=TRUE; } else { $debug=FALSE; }
 if( $debug ) { print "Debugging is ON\n"; }

#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
 $t=strftime( "%Y-%m-%d %H:%M:%S", time() );

#
# select hosts without ip address

 $sql = "select * from host ";
 $res = mysql_query($sql);
 while( $host = mysql_fetch_object($res) ) {
	print "Host: $host->name, IP in db: $host->ip\n";
	$ip = GetHostByName( $host->name );
	if( $host->ip!=$ip ) { print "MISMATCH: $host->ip -- $ip\n" ; }
	$host->ip = $ip;
	print "IP Address: $host->ip\n";
 
	$sql = "update host set ip='$host->ip' where name='$host->name'";
	print "SQL: $sql\n\n";
	//mysql_query($sql);
 }
?>



