#!/usr/local/bin/php
<?php
// $Id: last.php,v 1.1.1.1 2006/07/09 11:06:15 guizy Exp $
//
 require dirname(__FILE__)."/../etc/prbconfig.php";
 require $cfg->libPath."/Info.php";
#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 $opt = getopt('i:');
 $id   = $opt['i'];
 print "ID: $id\n";

 if( $id=='' ) {
    print "set ID...";
    exit;
 }

 $sql = "select * from info where id='$id'";
 $res = mysql_query($sql);
 $info = mysql_fetch_assoc($res);
 $mod = $info['module'];

 if(! is_file($cfg->modPath."/$mod.php") ) {
    print "Can't load $cfg->modPath/$mod.php<P>";
 }
 require $cfg->modPath."/$mod.php";

 $m = new $mod($info);

 $ret = rrd_last($m->rrdFile);
 print_r($ret);

?>
