<?php
// $Id: test.php,v 1.1.1.1 2006/07/09 11:06:15 guizy Exp $
//
#
# Config section
#
 require "../../etc/prbconfig.php";

#
# Get params
#
 $debug = true;

#
# Setup database connection
#
/*
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);
*/
#
# Get stuff from database
#
 $r = mysql_query("select * from info where id='$id'");
 $info =  mysql_fetch_assoc($r);

#
# Get the default module (parent class)
#
/*
 if(! is_file($cfg->libPath."/Info.php") ) {
    print $cfg->libPath."/Info.php not found...<br>";
    exit(-1);
 }
 require $cfg->libPath."/Info.php";
*/ 
#
# Include the required module now
#
 $mod = $info['module'];
 if( $debug ) {print "Loading module: $mod.php...<br>\n";}
 if(! is_file($cfg->modPath."/$mod.php") ) {
    print $cfg->modPath."/$mod.php not found...<br>\n";
    exit(-1);
 }
 require $cfg->modPath."/$mod.php";
#
# create object and do update in RRD database
#
 $m = new $mod($info);
 
 print "RRD file: ".basename( $m->rrdFile )."<br>\n";;
 
#
# check paths for rrd and png files
#
 print "<p>Check Update output:<br>";
 $m->rrdChkPath();
 $m->Update();
 if( $debug ) { print $m->outtext."<br>\n"; }

 print "<p>Check Graph creation (NULL = success):<br>";
 $m->pngChkPath();
 $r = $m->Graph("1day",  -86400);
 if( $debug ) var_dump($r);
 
?>
