<?php
#
# $Id: uptimeReport.php,v 1.1 2006/08/29 05:51:31 guizy Exp $
#
 require $cfg->modPath."/availability.php";


# We'll do our own error reporting
 error_reporting(0);

 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
 $t=strftime( "%Y-%m-%d %H:%M:%S", time() );

 $ad = $_REQUEST[ad];

 $group = $_REQUEST[group];
 if( $group != '' ) $group = "host.$group, ";

#
# Select hosts which respond to the uptime OID
#
 $sql = "SELECT host.*, info.*, host.name as name, info.name as iname FROM host, info 
        WHERE host.name = info.host and info.module = 'availability' 
        ORDER BY $group uptime $ad";

# toggle sort order
 $ad == 'asc' ? $ad = 'desc' : $ad = 'asc';

# retrieve from db and present report
 $res = mysql_query($sql) or die( mysql_error() );
?>

<div id=subMenu>
Uptime of devices (based on snmp sys.Uptime)
</div>
<div style='clear:both; height:20px'></div>

<?php
# legenda
 print "<table>";
 print "<tr><td class='red'> red </td><td>host is down</td></tr>";
 print "<tr><td class='lightRed'> light red </td><td>host up less than one day</td></tr>";
 print "<tr><td class='lightYellow'> light yellow </td><td>host up less than one week</td></tr>";
 print "<tr><td class='lightGreen'> light green </td><td>host up less than one month</td></tr>";
 print "<tr><td class='lightBleu'> light blue </td><td>host up more than one month</td></tr>";
 print "<tr>
        <th><a href=?p=uptimeReport.php&group=name&ad=$ad>Host name</a></th>
        <th><a href=?p=uptimeReport.php&ad=$ad>Uptime</a></th>
        <th>Availability</th>
        <th>Status</th>
        <th><a href=?p=uptimeReport.php&group=location&ad=$ad>Location</a></th>
        <th><a href=?p=uptimeReport.php&group=OS&ad=$ad>OS</a></th>
        <th><a href=?p=uptimeReport.php&group=vendor&ad=$ad>Vendor</a></th>
        <th><a href=?p=uptimeReport.php&group=lastPoll&ad=$ad>Last Polled</a></th>
        </tr>";

 while( $info = mysql_fetch_assoc($res) ) {
    $int = 86400;
    $mod = $info['module'];
    #
    # create object
    #
    $m = new $mod($info);
    $rrdFile = $m->rrdFile;

    $graph = array ( "-s", "-$int",
                    "DEF:val1=".$rrdFile.":AV:AVERAGE",
                    "PRINT:val1:AVERAGE:%.6lf");

    $ret = rrd_graph( NULL, $graph, count($graph) );
    $avg = $ret[calcpr][0];

    $dclass = 'lightBleu';
    if( $info[uptime] == 0 ) $dclass = 'red';
    if( $info[uptime] > 0 and $info[uptime] < 86400 ) $dclass = 'lightRed';
    if( $info[uptime] > 86400 and $info[uptime] < 604800 ) $dclass = 'lightYellow';
    if( $info[uptime] > 604800 and $info[uptime] < 2592000 ) $dclass = 'lightGreen';
    print "<tr>
        <td class='$dclass'><a href=?p=browse.php&host=$info[name]>$info[name]</a></td>
        <td class='$dclass'>$info[uptime] = ".printUptime($info[uptime])."</td>
        <td>$avg %</td>
        <td>$info[status]</td>
        <td>$info[location]</td>
        <td>$info[OS]</td>
        <td>$info[vendor]</td>
        <td>".date("H:i:s Y-M-d",$info[lastPoll])."</td>
        </tr>";
 }
 print "</table>";

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
