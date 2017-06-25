<?php
ini_set('display_errors', 0);

require "../../etc/prbconfig.php";
require $cfg->libPath."/Info.php";
require $cfg->libPath."/Web.php";
require $cfg->libPath."/Highcharts.php";
#
# Setup database connection
#
$cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
mysql_select_db($cfg->dbName, $cnn_id);

#
# init session
#
session_start();

# get post/get vars
$id     = $_REQUEST['infoid'];
$contnr = $_REQUEST['container'];
$start  = $_REQUEST['start'] or $start = "-2m";
$end    = $_REQUEST['end']   or $end   = "NOW";

if( $id == '' ) exit("missing infoid");

$w = new Web();

$sql  = "select * from info where id='$id'";
$res  = mysql_query($sql)       or $w->errHandler("Error: ".mysql_error()."<BR>", "die");
$info = mysql_fetch_assoc($res) or $w->errHandler("Error: No rows returned by <div class=code>$sql</div><BR> ".mysql_error()."<BR>", "die");
$mod  = $info['module'];

if(! is_file($cfg->modPath."/$mod.php") ) {
    $w->errHandler("Error: Can't load $cfg->modPath/$mod.php... It's not a file.<P>", "die");
}
require_once $cfg->modPath."/$mod.php";

$m = new $mod($info);
$m->rrdChkPath();
$m->prepHcOptions();
$m->hcOptions->chart->renderTo = $contnr;
$m->fetchData($start, $end);


$chart = new Highcharts($m->hcOptions);
header('Content-type: text/javascript');
echo $chart->build_code('myHighChart','mootools',false);

?>
