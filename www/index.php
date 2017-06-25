<?php
ini_set('display_errors', 0);
// Base template for application pages
$helpText = "<h3>Help</h3>This is an example help text...";
//
 $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
 require "../etc/prbconfig.php";
 require $cfg->libPath."/Info.php";
 require $cfg->libPath."/Web.php";
 require $cfg->libPath."/Highcharts.php";

 session_start();

#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 $w = new Web();

 $p = $_REQUEST['p'];
 if( !$p ) $p="$cfg->defaultPage";

    $pages['status.php'] = array( "href"=>"status.php", "title"=>"Status", "rightCol"=>true, "hide"=>false );
    $pages['uptime.php'] = array( "href"=>"uptime.php", "title"=>"Uptime report", "rightCol"=>true, "hide"=>false );
    $pages['browse.php'] = array( "href"=>"browse.php", "title"=>"Browse", "rightCol"=>true, "hide"=>false );
    $pages['tree.php'] = array( "href"=>"tree.php", "title"=>"Custom views", "rightCol"=>false, "hide"=>false );
    $pages['discover.php'] = array( "href"=>"discover.php", "title"=>"Discovery", "rightCol"=>true, "hide"=>false );

    $activePage = $pages[$p]['title'];
    if( $pages[$p]['rightCol'] ) { $col2Class  = "rightmenu"; }
    else { $col2Class  = "norightmenu"; }

    $validPage=false;
    function chkPage($page) {
        global $validPage, $p;
        $class="noSel";
        if( $page==$p ) {
            $class="current";
            $validPage=true;
        }
        return $class;
    }

    // Navigation menu
    $mainMenu    = "<ul class=solidblockmenu>\n";
    #$n  = count($pages);
    foreach($pages as $k => $v ) {
        $liclass = "menu";
        $href  = $pages[$k]['href'];
        $title = $pages[$k]['title'];
        $class = chkPage($href);
        if(! $pages["$k"]['hide'] ) $mainMenu .= "<li class='$liclass'><a class='$class' href=?p=$href><div>$title</div></a></li>\n";
    }
    $mainMenu .= "</ul>\n";

 if( $validPage ) {
    include "$p";
 } else {
    $pageMain = "<div class=warning>$p is not a valid page...</div>";
 }

print '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" >
<head>
	<title><?php echo $activePage;?></title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
	<meta name="author" content="Guillaume Fontaine" />
	<meta name="copyright" content="copyright 2006-2007 prb.sourceforge.net" />
	<meta name="description" content="prd, php rrdtool browser" />
	<meta name="keywords" content="rrdtool, snmp, graphing, php, mysql" />
	<meta name="robots" content="all" />
<link href='<?php echo $cfg->CSS;?>'   type='text/css' rel='stylesheet'>
<script type="text/javascript" src="js/mootools-core-1.4.5.js"></script>
<script type="text/javascript" src="js/mootools-more-1.4.0.1.js"></script>
<script type="text/javascript" src="js/prb.js"></script>
<script type='text/javascript' src='js/hc/adapters/mootools-adapter.js'></script>
<script type='text/javascript' src='js/hc/highcharts.js'></script>
<script type='text/javascript' src='js/hc/themes/grid.js'></script>

    <!--[if lt IE 7]>
    <style media="screen" type="text/css">
    .col1 {
	    width:100%;
	}
    </style>
    <![endif]-->
</head>
<body>

<div id="header">
  <a href=http://prb.sourceforge.net/><img style='float:left;' src=images/prb.png></a>
    <?php print "<div id=stats>".$w->statusTable()."</div>";?>
  <div style='clear:both;height:12px;'></div>
  <div id="horiz-menubar">
    <?php print $mainMenu; ?>
  </div>
  <?php global $subMenu; echo $subMenu;?>
  <div style='clear:both;height:0px;'></div>
</div>
<div class="colmask <?php echo $col2Class?>">
    <div class="colleft">
        <div class="col1wrap">
            <div class="col1">
                <!-- Column 1 start -->
                <?php
                    global $pageMain;
                    print  $pageMain;
                ?>
				<!-- Column 1 end -->
            </div>
        </div>
        <?php
        if( $pages[$p]['rightCol'] ) {
        ?>
        <div class="col2">
            <!-- Column 2 start -->
            <?php global $infoBox; print "<div id=infoBox>". $infoBox ."</div>$helpText"; ?>
			<!-- Column 2 end -->
        </div>
        <?php
        }
        ?>
    </div>
</div>
<div id="footer">
    <p>
    <?php include("footer.php"); ?>
    </p>
</div>


</body>
</html>
