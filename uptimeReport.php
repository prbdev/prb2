#!/usr/local/bin/php -e
<?php
#
# $Id$
#

#
# Config section
#
 require dirname(__FILE__)."/etc/prbconfig.php";
 require $cfg->libPath."/Info.php";
 require $cfg->modPath."/availability.php";
 $webserver = "protss01";

# We'll do our own error reporting
// error_reporting(0);

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
# Select hosts which respond to the uptime OID
#
 $uptimes = array();
 $sql = "SELECT host, uptime, OS, location, module
	    FROM info, host WHERE info.module = 'availability' 
            AND info.host = host.name
            AND 
            (host.OS = 'Tru64' OR
             host.OS = 'Solaris' OR
             host.OS = 'Linux')
	     ORDER BY uptime
	";

 $myresult = mysql_query($sql);
 print "Uptime report for ".date("Y-M-d H:i:s", time())."\n
 See http://$webserver/$cfg->relUrlPath/?uptimeReport.php\n\n";

 while( $info = mysql_fetch_assoc($myresult) ) {

    $int = 86400;
    $mod = $info['module'];
    $m = new $mod($info);
    $rrdFile = $m->rrdFile;

    $graph = array ( "-s", "-$int",
                    "DEF:val1=".$rrdFile.":AV:AVERAGE",
                    "PRINT:val1:AVERAGE:%.6lf");

    $ret = rrd_graph( NULL, $graph, count($graph) );
    $avg = $ret[calcpr][0];

    print "$info[host]\t$avg% \t".printUptime($info[uptime])."\t$info[OS]\t $info[location]\t\n";

    if (! is_array($ret) ) {
        $err = rrd_error();
        print "rrd_graph() ERROR: $err\n";
        print_r($graph);
    }
 }

 function printUptime( $uptime ) {

    $d = floor( $uptime/86400 );
    $t = $uptime%86400;
    $h = floor( $t/3600 );
    $t = $t%3600;
    $m = floor( $t/60 );
    $s = $t%60;

    return sprintf("$d day(s), %02d:%02d:%02d",$h,$m,$s);
 }

?>
