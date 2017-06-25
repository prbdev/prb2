<?php
// $Id: status.php,v 1.5 2006/11/01 07:47:58 guizy Exp $ 
//
 $pageMain="";

 $subMenu = "
<div id=subMenu>
Status Overview
</div>
    ";

#
# start processing
#
 $sql = "select count(*) from host,info 
            where host.name=info.host and 
                    host.status = 'polling' and 
                    info.status='polling'";

 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $inst = $s[0];

 $sql = "select count(*) from host where status = 'polling'";
 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $poll = $s[0];

 $sql = "select count(*) from host where status='maintenance'";
 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $maint = $s[0];

 $sql = "select count(*) from host where status='incident'";
 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $inc = $s[0];

 $infoBox = "<div class=header>Host statistics</div>";
 $infoBox .= "<div id=status>
        <table>
        <tr><td>Number of hosts being polled</td><td align=right>$poll</td></tr>
        <tr><td>Number of hosts in maintenance (not polled)</td><td align=right>$maint</td></tr>
        <tr><td>Number of hosts in incident status (not polled)</td><td align=right>$inc</td></tr>
        <tr><td>Total number of hosts</td><td align=right>".($poll + $maint + $inc)."</td></tr>
        <tr><td>Total number of instances being polled</td><td align=right>$inst</td></tr>
        </table>
        </div>";


 $id = $_REQUEST['id'];
 if( $id!='' ) {
    $sql  = "select * from prbStats where id='$id' ";
    $res  = mysql_query($sql)       or $w->errHandler("Error: ".mysql_error()."<BR>", "die");
    $info = mysql_fetch_assoc($res) or $w->errHandler("Error: No rows returned by <div class=code>$sql</div><BR> ".mysql_error()."<BR>", "die");
    $mod  = $info['module'];

    if(! is_file($cfg->modPath."/$mod.php") ) {
        $w->errHandler("Error: Can't load $cfg->modPath/$mod.php... It's not a file.<P>", "die");
    }
    require_once $cfg->modPath."/$mod.php";

    $m = new $mod($info);
    $m->debug = true;
    $m->rrdChkPath();
    $m->prepHcOptions();

 $pageMain .= $w->errorMsg;
 $pageMain .= $m->hostDescription ."<p>";

 $m->fetchData('-8h');
 $m->hcOptions->chart->renderTo = "container1";
 $chart = new Highcharts($m->hcOptions);
 $pageMain .= $chart->build_code('myHighChart','mootools',true);
 $pageMain .= "<div id='container1' style='min-width: 400px; height: 400px;'></div>";
 $m->fetchData('-1w');
 $m->hcOptions->chart->renderTo = "container2";
 $chart = new Highcharts($m->hcOptions);
 $pageMain .= $chart->build_code('myHighChart','mootools',true);
 $pageMain .= "<div id='container2' style='min-width: 400px; height: 400px;'></div>";
 $m->fetchData('-8w');
 $m->hcOptions->chart->renderTo = "container3";
 $chart = new Highcharts($m->hcOptions);
 $pageMain .= $chart->build_code('myHighChart','mootools',true);
 $pageMain .= "<div id='container3' style='min-width: 400px; height: 400px;'></div>";
 $m->fetchData('-6month');
 $m->hcOptions->chart->renderTo = "container4";
 $chart = new Highcharts($m->hcOptions);
 $pageMain .= $chart->build_code('myHighChart','mootools',true);
 $pageMain .= "<div id='container4' style='min-width: 400px; height: 400px;'></div>";

 }
 else {
    $sql = "select * from prbStats order by id";
    $res = mysql_query($sql);
    $jscode = "";
    $divs = "";
    while( $info = mysql_fetch_assoc($res) ) {
        $mod = $info['module'];
        if(! is_file($cfg->modPath."/$mod.php") ) {
            print "Can't load $cfg->modPath/$mod.php<P>";
        }
        require_once $cfg->modPath."/$mod.php";
        $m = new $mod($info);
        $m->rrdChkPath();
        $m->prepHcOptions();
        $m->fetchData('-8h');
        $m->hcOptions->chart->renderTo = "container-$m->id";
        $chart = new Highcharts($m->hcOptions);
        $jscode .= $chart->build_code('myHighChart','mootools',true);

        $divs .= "
        <p><h3>".$m->name." ".$m->description."</h3>
        <a href=\"".$_SERVER['PHP_SELF']."?p=status.php&id=".$m->id."\" >";
        $divs .= "<div id='container-$m->id' style='min-width: 400px; height: 400px;'>loading</div>";
        $divs .= "</a><br>\n";
    }
    $pageMain .= $jscode."\n<div>".$divs."</div>";
 }

?>
