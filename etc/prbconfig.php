<?php
// Configuration file
// $Id: prbconfig.php.dist,v 1.2 2006/08/03 09:20:19 guizy Exp $
// Copy this file to prbconfig.php and
// edit to your requirements

##
## user and group your webserver runs under
## typically nobody, apache or wwwrun
##
 $cfg->user  = "www-data";
 $cfg->group = "www-data";

##
## DB Access
##
 $cfg->dbUser = "root";
 $cfg->dbPass = "mysql150k";
 $cfg->dbHost = "127.0.0.1";
 $cfg->dbName = "prbdb2";

##
## Max child processes to spawn
##
 $cfg->maxChld = 75;

##
## Paths
##
 $cfg->basePath   = "/var/www/default/prb2";
 $cfg->relUrlPath = "/prb2/www";
    // these are relative to the above
 $cfg->libPath  = $cfg->basePath."/lib";
 $cfg->rrdPath  = $cfg->basePath."/rrd";
 $cfg->pngPath  = $cfg->basePath."/www/png";
 $cfg->logPath  = $cfg->basePath."/log";
 $cfg->modPath  = $cfg->libPath."/mods";
 $cfg->pngUrlPath  = $cfg->relUrlPath."/png";
 $cfg->backEndPath  = $cfg->relUrlPath."/ajax_backend";

##
## Default Highcharts options file
##
 $cfg->hcOptionsFile = $cfg->basePath."/etc/hcOptions.php";

##
## Image sizes
##
 $cfg->Width  = 560;
 $cfg->Height = 260;
 define("W", "560");
 define("H", "260");
 $cfg->TNWidth  = 420;
 $cfg->TNHeight = 140;

##
## Default font for rrdgraph
##
   $cfg->rrdFont = "/usr/share/fonts/truetype/ubuntu-font-family/UbuntuMono-R.ttf";
 //$cfg->rrdFont = "/usr/X11R6/lib/X11/fonts/TTF/luximr.ttf";
 //$cfg->rrdFont = "/usr/X11R6/lib/X11/fonts/TTF/Vera.ttf";
 //$cfg->rrdFont = "/usr/X11R6/lib/X11/fonts/TTF/luxisr.ttf";

##
## CSS file
##
 $cfg->CSS = $cfg->relUrlPath."/css/prb.css";

##
## Default page to show in index.php
##
 $cfg->defaultPage = "uptime.php";
 //$cfg->defaultPage = "browse.php";

##
## Show all nodes or not (true or false)
##
 $cfg->showAll = true;

##
## The script name 
##
 $me = $_SERVER['PHP_SELF'];

?>
