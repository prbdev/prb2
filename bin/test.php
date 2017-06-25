#!/usr/local/bin/php -e
<?php
// $Id: test.php,v 1.1.1.1 2006/07/09 11:06:15 guizy Exp $
//
#
# Config section
#
 require dirname(__FILE__)."/../etc/prbconfig.php";

#
# Get command line options
#
 $opt = getopt('hi:');
 if( $opt["i"] ) { $id = $opt["i"]; } else { $opt['h']=true ;}
 if( is_bool($opt["h"]) ) { die("Usage: ".basename($argv[0])." -i <id>\n"); } 
 $debug = true;

#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

#
# Get stuff from database
#
 $r = mysql_query("select * from info where id='$id'");
 $info =  mysql_fetch_assoc($r);

#
# Get the default module (parent class)
#
 if(! is_file($cfg->libPath."/Info.php") ) {
    print $cfg->libPath."/Info.php not found...\n";
    exit(-1);
 }
 require $cfg->libPath."/Info.php";
 
#
# Include the required module now
#
 $mod = $info['module'];
 if( $debug ) {print "Loading $mod.php...\n";}
 if(! is_file($cfg->modPath."/$mod.php") ) {
    print $cfg->modPath."/$mod.php not found...\n";
    exit(-1);
 }
 require $cfg->modPath."/$mod.php";
#
# create object and do update in RRD database
#
 $m = new $mod($info);
 
 print basename( $m->rrdFile )."\n";;
 print basename( $m->rrdFile, ".rrd" )."\n";;
 
#
# check paths for rrd and png files
#
 $m->rrdChkPath();
 $m->Update();
 if( $debug ) { print $m->outtext."\n"; }

 $m->pngChkPath();
 $r = $m->Graph("1day",  -86400);
 if( $debug ) var_dump($r);

 exit(0);

?>
