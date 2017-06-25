<?php
// $Id: rpcInfo.php,v 1.1 2006/08/03 09:20:19 guizy Exp $
// Process ajax calls related to info records

 require "../../etc/prbconfig.php";
 require $cfg->libPath."/Info.php";
 require $cfg->libPath."/Web.php";
#
# Setup database connection
#
 $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
 mysql_select_db($cfg->dbName, $cnn_id);

 session_start();

 $action = $_REQUEST['act'];
 $host   = $_REQUEST['host'];
 $infoid = $_REQUEST['infoid'];
 $viewid = $_REQUEST['viewid'];
 $trid   = $_REQUEST['trid'];
 $chid   = $_REQUEST['chid'];

 $w = new Web();

 switch( $action ) {
    case 'savetree';
        $treename = $_REQUEST[treename];
        saveTreeState($treename);
    break;
    case 'details':
        print $w->sqlviewDetails($viewid);
    break;
    case 'editfolder':
        print $w->sqlviewForm($viewid);
    break;
    case 'updatefolder': 
        //existing folder or view
        $form = $_REQUEST[form];
        $sql = "update views set `name`='$form[name]', `description`='$form[description]', 
                    `query`='".mysql_escape_string($form[query])."'
                where id='$viewid'";
        mysql_query($sql);  $html .= mysql_error(); 
        $html .= "<script>$('$viewid').innerHTML='".$form[name]."';";
        $html .= $w->sqlviewDetails($viewid);
        print $html;
    break;
    case 'addfolder':
        print $w->addfolderForm($viewid, 'folder');
        print "<script>doExpandAll('$trid','customtree');</script>";
    break;
    case 'addview':
        print $w->addfolderForm($viewid, 'view');
        print "<script>doExpandAll('$trid','customtree');</script>";
    break;
    case 'savefolder':
        //new folder
        $folderForm = $_REQUEST[form];
        $sql = "insert into views (`name`, `parent`, `status`, `query`) values('$folderForm[name]', '$folderForm[parent]', '$folderForm[status]', '".mysql_escape_string($folderForm[query])."');";
        mysql_query($sql);
	$id = mysql_insert_id();
        print $w->sqlviewDetails($id);
        print "<script>mkTree('content');</script>";
        print "<script>doExpandAll('$trid','customtree');</script>";
    break;
    case 'rmfolder':
        $sql = "delete from views where id='$viewid'";
        mysql_query($sql);
        print  "<script>$('$trid').remove();</script>";
    break;
    case 'save':
        if( $infoid!='' ) {
            $form       = $_REQUEST['form'];
            $status     = $_REQUEST['status'];
            foreach( $form as $field => $val ) {
                $update .= "`$field` = '$val', ";
            }
            $update .= "`status` = '$status' ";
            $fields     = "(`".implode("`, `", array_keys($form))."`)";
            $values     = "('".implode("', '", array_values($form))."')";
            $sql        = "update info set $update where id='$infoid'";
            mysql_query($sql) or print mysql_error();
        } elseif($host!='') {
            $hostInfo = $_REQUEST['hostInfo'];
            $status   = $_REQUEST['status'];
            $sql = "update host set
                `location`='$hostInfo[location]',
                `sysDescr`='$hostInfo[sysDescr]',
                `vendor`='$hostInfo[vendor]',
                `OS`='$hostInfo[OS]',
                `status`='$status'
                where name='$host' ";
            mysql_query($sql);
        }
    case 'cancel':
        if( $infoid!='' ) {
            $info = getInfo($infoid);
            $m    = initModule($info);
            print $w->showInfoRec($info, $m->moduleVars);
        } elseif( $host!='' ) {
            print $w->showHostInfo($host, 'infoBox');
        } elseif ( $viewid!='' ) {
            print $w->sqlviewDetails($viewid);
        }
    break;
    case 'delete':
        if( $infoid!='' ) {
            $html  = "<span class=warning>This will permanently delete this module from the database!</span><p></p>";
            $html .= $w->confirmDeleteForm('', $infoid);
        } elseif( $host!='' ) {
            $html  = "<span class=warning>This will permanently delete this host and related data from the database!</span><p></p>";
            $html .= $w->confirmDeleteForm($host, '');
        }
        echo $html;
    break;
    case 'deleteconfirm':
        if( $infoid!='' ) {
            $info = getInfo($infoid);
            $sql = "delete from info where id='$infoid'";
            if( mysql_query($sql) ) {
                print "Module deleted successfully...";
                $url = "?p=browse.php&host=$info[host]";
                print $w->continueButton($url);
            } else {
                print mysql_error();
            }
        } elseif( $host!='' ) {
            $sql = "delete from host where name='$host'";
            $res = mysql_query($sql) or die(mysql_error());
            $sql = "delete from info where host='$host'";
            $res = mysql_query($sql) or die(mysql_error());
            print "All information pertaining to $host has been deleted from the database...\n";
            $url = "?p=browse.php";
            print $w->continueButton($url);
        }
    break;
    case 'edit':
	    if( $infoid!='' ) {
            $info = getInfo($infoid);
            $m    = initModule($info);
            echo $w->infoRecForm($info, $m->moduleVars);
        } elseif( $host!='' ) {
            print $w->formHostInfo($host);
	    } elseif( $viewid!='' ) {
            print $w->sqlviewForm($viewid);
        }
    break;
    case 'test':
	    if( $infoid!='' ) {
	        $id=$infoid;
            include "test.php";
            print $w->clearButton('', $infoid);
	    }
    break;
    case 'trim':
	    if( $infoid!='' ) {
	        $id=$infoid;
            include "rmspikes.php";
            print $w->clearButton('', $infoid);
	    }
    break;
    case 'ping':
        print "Ping $host...";
        if( $host=='' ) break;
            print "<div class=term>";
            print "ping -nc6 -W1 -i0.2 $host\n";
            print nl2br(`ping -nc6 -W1 -i0.2 $host`);
            print "</div>";
            print $w->clearButton($host);
    break;
    case 'addmodule':
        print $w->showAddForm($host);
    break;
    case 'add':
        $addForm = $_REQUEST[addForm];
        $module  = $addForm[module];
        $addForm[host] = $host;
        if( $module == '' ) break;
        require_once $cfg->modPath."/$module.php";
        $m = new $module(NULL);
        $m->setVars();
        print $w->showModuleForm($m->moduleVars, $addForm);
    break;
    case 'savemodule':
        $form   = $_REQUEST[form];
        $module = $form[module];
        $host   = $form[host];
        if( $module == '' ) break;
        require_once $cfg->modPath."/$module.php";
        $m = new $module($form);
        $m->setVars();
        $m->name = $m->baseFile;
        $fields = "`name`, ";
        $values = "'$m->name', ";
        foreach( $m->moduleVars as $key => $var ) {
           $fields .= "`$key`, ";
           $values .= "'".$m->$key."', ";
        }
        $fields .= "`status` ";
        $values .= "'polling' ";
        $sql = "insert into info ($fields) values($values);";
        mysql_query($sql) or die("Error: ".mysql_error()."<p>SQL: $sql");
        echo $w->showHostInfo($host, 'hostInfo');
    break;
	
 }

 function getInfo($id) {
    $sql    = "select * from info where id='$id'";
    $res    = mysql_query($sql) or print mysql_error();
    $info   = mysql_fetch_assoc($res);
    return  $info;
 }

 function initModule($info) {
    global $cfg;
    $mod = $info['module'];
    require $cfg->modPath."/$mod.php";
    $m = new $mod($info);
    $m->setVars();
    return $m;
 }

 function saveTreeState($treename) {
    $tr = $_REQUEST['tr'];
    if($tr=='') return;
    foreach( $tr as $k => $v ) {
        $treestate->$k = $v;
    }
    $_SESSION[$treename] = $treestate;
 }

?>
