<?php
// $Id: index.php,v 1.10 2006/08/29 05:51:31 guizy Exp $
//
 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());

 $_self = "browse.php";
 $pageMain = "";

#
# start processing
#
 $mode  = $_REQUEST['mode'];
 $query = $_REQUEST['qry'];
 $debug = $_REQUEST['debug'];
 $id    = $_REQUEST['id'];
 $host  = $_REQUEST['host'];

 $osclass = 'nosel';
 $vendorclass = 'nosel';
 $locationclass = 'nosel';
 switch( $mode ) {
    case 'OS':
        $key = 'OS';
        $modeDescr = 'Operating System';
        $osclass = 'sel';
        break;
    case 'vendor':
        $key = 'vendor';
        $modeDescr = 'Vendor';
        $vendorclass = 'sel';
        break;
    case 'location':
        $key = 'location';
        $modeDescr = 'Location';
        $locationclass = 'sel';
        break;
    default:
        $key = 'OS';
        $mode = 'OS';
        $modeDescr = 'Operating System';
        $osclass = 'sel';
        break;
 }

#
# Module specific views
#

 if( $id!='' ) {

    $sql  = "select * from info where id='$id'";
    $res  = mysql_query($sql)       or $w->errHandler("Error: ".mysql_error()."<BR>", "die");
    $info = mysql_fetch_assoc($res) or $w->errHandler("Error: No rows returned by <div class=code>$sql</div><BR> ".mysql_error()."<BR>", "die");
    $mod  = $info['module'];

    if(! is_file($cfg->modPath."/$mod.php") ) {
        $w->errHandler("Error: Can't load $cfg->modPath/$mod.php... It's not a file.<P>", "die");
    }
    require_once $cfg->modPath."/$mod.php";

    $m = new $mod($info);

    $subMenu = " <div id=subMenu>
            View statistics history:
 <a class=sel href='?p=$_self&mode=$mode'>$modeDescr</a> >
 <a class=sel href='?p=$_self&mode=$mode&host=$info[host]'>$info[host]</a> >
<b>$info[name]</b> ($info[description])
            </div>";


 print $w->errorMsg;
 $m->setVars();
 $infoBox = $w->showInfoRec($info, $m->moduleVars);

/*
 $m->hcOptions->chart->renderTo = 'container1';
 $m->fetchData('-32h','NOW');
 //print_r($m->hcOptions);

 $chart = new Highcharts($m->hcOptions);
 $pageMain .= $chart->build_code('myHighChart','mootools');
*/
 
 $pageMain .= "<script type='text/javascript' src='ajax_backend/fetch_data.php?start=-32h&infoid=$id&container=container1'></script>"; 
 $pageMain .= "<div id='container1' style='min-width: 400px; height: 400px;'></div>"; 
 $pageMain .= "<script type='text/javascript' src='ajax_backend/fetch_data.php?start=-3week&infoid=$id&container=container2'></script>"; 
 $pageMain .= "<div id='container2' style='min-width: 400px; height: 400px;'></div>"; 
 $pageMain .= "<script type='text/javascript' src='ajax_backend/fetch_data.php?start=-50weeks&infoid=$id&container=container3'></script>"; 
 $pageMain .= "<div id='container3' style='min-width: 400px; height: 400px;'></div>"; 
 $pageMain .= "<script type='text/javascript' src='ajax_backend/fetch_data.php?start=-100weeks&infoid=$id&container=container4'></script>"; 
 $pageMain .= "<div id='container4' style='min-width: 400px; height: 400px;'></div>"; 

 }


#
# Host view
#

 elseif( $host != '' ) {

     $subMenu = " <div id=subMenu>
            View host statistics: <a class=sel href='?p=$_self&mode=$mode'>$modeDescr</a> > <b>$host</b>
            </div>";

    $sql = "select * from info where host='$host' order by grouping, ifIndex, name";
    $res = mysql_query($sql);

    $infoBox = $w->showHostInfo($host, 'infoBox');

    $accordion = '';
    $i=0;
    while( $info = mysql_fetch_assoc($res) ) {
        $mod = $info['module'];

        if( $info['grouping'] == '' ) $info['grouping'] = 'No group';
        if(! is_file($cfg->modPath."/$mod.php") ) {
            print "Can't load $cfg->modPath/$mod.php<P>";
        }
        require_once $cfg->modPath."/$mod.php";
        $m = new $mod($info);
        $m->rrdChkPath();
        $m->pngChkPath();

        if( $info['grouping'] != $prev_g ) {
	$accordion .= "</div><h3 class=\"toggler\" id=\"H_$i\">";
	$accordion .= "<img id=I_$i src=\"images/tg_minus.png\">  ";
	$accordion .= $info['grouping']."</h3>\n";
	$accordion .= "<div class=\"element\" id=\"C_$i\">\n";
	$i++;
        }

	$accordion .= "<a href=\"?p=$_self&mode=$mode&id=$m->id\">";

 	$scripts .= "<script type='text/javascript' src='ajax_backend/fetch_data.php?start=-40h&infoid=".$m->id."&container=container-".$m->id."'></script>\n"; 
 	$accordion .= "<div id='container-".$m->id."' style='min-width: 400px; height: 400px;'></div></a>\n"; 

        $prev_g = $info['grouping'];
    }

 $pageMain = "
    $scripts
    <div>
    $accordion
    </div>
    <script>
        window.addEvent('domready', function() {
            addTogglers('toggler');
        });
    </script>
    ";

 }

#
# tree browsing
#

 else {
 if( $query == '' ) {
    $search = " 1 ";
 } else {
    $search = " host like '%$query%' ";
 }

 $val = $_REQUEST[$key];
 if( $val != '' ) {
    $search .= " and $key='$val'";
    $cfg->showAll = true;
 }

 $sql = "select *, host.id as hostID, host.sysDescr as hostDescr, host.status as hostStatus from host, info
        where host.name=info.host
            and $search
        order by $key, host, info.name";


 $res = mysql_query($sql) or die(mysql_error());

// Start a tree
 $treenodes = "";
 $options['treename'] = 'browse';
 $options['cols'] = 5;

 require $cfg->libPath."/TreeGrid.php";
 $treeGrid = new treeGrid($options);

 $i = -1;
 while( $info = mysql_fetch_object($res) ) {

    if( $info->$key != $prev_k ) {
        $level = 0;
        $i++;
        $pid = "0-$i";
        // The data
        $rowdata[0]="<a href='?p=$_self&mode=$mode&$key=".$info->$key."'>".$info->$key."</a>";
        $rowdata[1]="";
        $rowdata[2]="";
        $rowdata[3]="";
        $rowdata[4]="";
        $treeGrid->tgAddRow($pid, $level, $rowdata);
        $j=-1; $k=-1;
    }

    if( $info->host != $prev_host ) {
        $level = 1;
        $j++;
        $pid = "0-$i-$j";
        // The data
        $rowdata[0]="<a href=$me?p=$_self&mode=$mode&host=$info->host>".$info->host."</a>";
        $rowdata[1]=$info->ip;
        $rowdata[2]=$info->location;
        $rowdata[3]=$info->hostStatus;
	// Tooltip the long description
	$tip = nl2br($info->sysDescr);
	$descr = $info->sysDescr;
	// trim long sysDescr
	if( strlen($tip) > 100) {
		$descr = substr($info->sysDescr,0,100)." (...)";
	} 
        $rowdata[4] = "<span class=tip title=\"$info->host::".$tip."\">$descr</span>";
	if( $cfg->showAll ){ $type = 'folder'; } else { $type = 'file'; }
        $treeGrid->tgAddRow($pid, $level, $rowdata, $type);
	$k=-1;
    }

    if( $cfg->showAll ) {
        $level = 2;
        $k++;
        $pid = "0-$i-$j-$k";
        // The data
        $rowdata[0]="<a href='?p=$_self&mode=$mode&id=$info->id'>".$info->name."</a>";
        $rowdata[1]=$info->ip;
        $rowdata[2]=$info->location;
        $rowdata[3]=$info->status;
        $rowdata[4]=$info->description;
        $treeGrid->tgAddRow($pid, $level, $rowdata, 'file');
    }

        $prev_k   = $info->$key;
        $prev_host = $info->host;
 }

 $subMenu = "<div id=subMenu>
Browse by:
<a class=$osclass href='?p=$_self&mode=OS'> Operating System </a> |
<a class=$vendorclass href='?p=$_self&mode=vendor'> Vendor </a> |
<a class=$locationclass href='?p=$_self&mode=location'> Location </a>
</div>";

 $pageMain .= "
<div id=searchBox>
    <form method=post>&nbsp;Host filter <input type=text name=qry size=14></form>
</div>

<script type=\"text/javascript\">
 window.addEvent('domready', function() {
    // Tooltips
    var myTips = new Tips($$('.tip'), {
        showDelay: 200,
        hideDelay: 200,
        fixed: true
     	});
 });
 window.addEvent('beforeunload', function() {
    saveTreeState('$treeGrid->treename');
 });

</script> 

<table id=treegrid style=\"border:1px solid #E0E0E0; width: 100%;\">
<thead>
    <tr>
    <th>Name</th>
    <th>IP</th>
    <th>Location</th>
    <th>Status</th>
    <th>Description</th>
    </tr>
</thead>
<tbody id='$treeGrid->treename' class=grid>";

 $pageMain .= $treeGrid->tgOutputHTML();
 $pageMain .= "</tbody> </table>";
 $pageMain .= $treeGrid->tgAddControls("$treeGrid->treename"); 

}

?>
