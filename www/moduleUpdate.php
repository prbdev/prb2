<?php
// $Id$
//
// $startTime = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
// require "../etc/prbconfig.php";
// require $cfg->libPath."/Info.php";
// require $cfg->libPath."/Web.php";
#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 $w = new Web();

#
# start processing
#
 $id   = $_REQUEST[id];
 $name = $_REQUEST[name];
 $task = $_REQUEST[task];

 if( ($task=='add') && ($name!='') ) {
	addModule($name);
 }
 if( ($task=='del') && ($id!='') ) {
	delModule($id);
 }

#
# list modules
#

 if ($handle = opendir($cfg->modPath)) {
   $i=0;
   while (false !== ($file = readdir($handle))) {
       if (preg_match("/.+\.php/", $file, $match) && $module = basename($file, '.php')) {
           $modfile[$i]=$module;
           $i++; 
       }
   }
   closedir($handle);
 }
 sort($modfile);

#
# Get mods from database and show delta's
#
 $sql = "select name, id from modules order by name";
 $res = mysql_query($sql) or die(mysql_error());
 $i=0;
 while( $row = mysql_fetch_row($res) ) {
    $moddb[$i]   = $row[0];
    $moddbid[$i] = $row[1];
    $i++;
 }

 $i=0;
 $j=0;
 $k=0;
 $l=0;
 while( $i<count($modfile) or $j<count($moddb) ) {
    if( $modfile[$i] != $moddb[$j] ) {
        if( $modfile[$i] < $moddb[$j] ) {
            $str1[$k] = $modfile[$i];
            $str2[$l] = "";
            $str2[$l+1] = $moddb[$j];
            $str3[$l] = "";
            $str3[$l+1] = $moddbid[$j];
            $l=$l+1;
            $k=$k+1;
            $i++;
        } else {
            $str1[$k] = "";
            $str1[$k+1] = $modfile[$i];
            $str2[$l] = $moddb[$j];
            $str3[$l] = $moddbid[$j];
            $l=$l+1;
            $k=$k+1;
            $j++;
        }
    } else {
        $str1[$k]=$modfile[$i];
        $str2[$l]=$moddb[$j];
        $str3[$l]=$moddbid[$j];
        $i++;
        $j++;
        $l++;
        $k++;
    }
 }

 for($i=0; $i<count($str1); $i++) {
    $add = "";
    if( $str2[$i] == "" ) { 
	$add = "<a href=?p=moduleUpdate.php&task=add&name=$str1[$i]> ADD => </a>";
    } 	
    if( $str1[$i] == "" ) { 
	$add = "<a href=?p=moduleUpdate.php&task=del&id=$str3[$i]> <= REMOVE </a>";
    }

    $html .= "<tr><td>$str1[$i]</td><td>$add</td><td>$str3[$i]</td><td>$str2[$i]</td></tr>";
 }

 print " <div id=subMenu>Add or remove modules from database
         </div>
 <div style='clear:both; height:20px'></div>";

 echo "<table><th>In dir</th><th>Add/Remove</th><th>Index</th><th>In db</th>$html</table>";

#
# FUNCTIONS
#

#
# Add module to database
#
 function addModule($name) {
	$sql = "insert into modules (`name`) values ('$name')";
	$res = mysql_query($sql);
 }

#
# Delete module from database
#
 function delModule($id) {
	$sql = "delete from modules where id = '$id'";
	$res = mysql_query($sql);
 }

?>
