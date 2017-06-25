<?php
 require "../../etc/prbconfig.php";
 require $cfg->libPath."/TreeGrid.php";

 session_start();
#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 $max_recurse = 8;

 $options['treename']='customtree';
 $options['cols']=1;
 $treegrid = new treeGrid($options);

 function recTree($id, $level, $offset, $pid=0, $i=0) {

    global $max_recurse, $treegrid;
    $level++;
    $offset++;
    $sql = "select * from views where parent = '$id' order by name";
    $res = mysql_query($sql);
    while( $row = mysql_fetch_object($res) ) {
        $trid = "$pid-$i";
        $rowdata[0]="<span id=$row->id class=graph>$row->name</span>";
        if( $row->status == 'folder' ) {
            $treegrid->tgAddRow($trid, $level, $rowdata);
        } else {
            $treegrid->tgAddRow($trid, $level, $rowdata, 'file');
        }

        if( ($row->status == 'folder') && ($offset < $max_recurse) ) {
            recTree($row->id, $level, $offset, $trid);
        }
        $i++;
    }
 }

 $id    = $_REQUEST['id'];
 $level = $_REQUEST['level'];
 if( $id    == '' ) $id = 0;
 if( $level == '' ) $level = -1;

 recTree($id,$level,0);

 print "<table id=customtree class=grid width=100%>";
 print "<tr><th>Name</th></tr>";
 print "<tbody>";
 print $treegrid->tgOutputHTML();
 print "</tbody>";
 print "</table>";
?>
