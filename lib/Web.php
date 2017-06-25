<?php
// $Id: Web.php,v 1.10 2006/08/09 15:53:16 guizy Exp $

Class Form {

    var $header;
    var $footer;
    var $body;

    function Form($options) {

        $this->name   = isset($options['name'])  ?$options['name']  :'myForm';
        $this->id     = isset($options['id'])    ?$options['id']    :$this->name;
        $this->class  = isset($options['class']) ?$options['class'] :'';
        $this->action = isset($options['action'])?$options['action']:'#';
        $this->method = isset($options['method'])?$options['method']:'post';

	$containerClass = isset($options['containerClass'])?$options['containerClass']:"form-container";
	$this->header  = "<div class=$containerClass>\n";
	$this->header .= "<form id='$this->id' name='$this->name' class='$this->class' action='$this->action' method='$this->method' >\n";

	$this->footer  = "</form></div>\n";
    }

    function frmOutputHTML() {
	return $this->header . $this->body . $this->footer ."\n\n";
    }

    function frmOpenFieldset($options) {
        $this->body .= "<fieldset id='".$options['id']."' class='".$options['class']."' >\n"; 
        $this->body .= "<legend>".$options['legend']."</legend>\n";
    }

    function frmCloseFieldset() {
        $this->body .= "</fieldset>\n"; 
    }

    function frmAddInput($type, $options) {
	    $req = ($options[req]=='req')?"<em>*</em>":"";
        switch($type) {
            case 'hidden':
	    	$class = isset($options['class'])?$options['class']:"hidden";
                $this->body .= "<input type=$type id='$options[id]' name='$options[name]' value='$options[value]' class='$class' />\n";
            break;
            case 'text':
                $this->body .= "<div><label>$options[label]$req</label>";
                $this->body .= "<input type=$type id='$options[id]' name='$options[name]' value='$options[value]' class='$options[class]' />\n";
		if( $options[note]!='' ) $this->body .= "<p class='note'>$options[note]</p>\n";
		$this->body .= "</div>\n";
            break;
            case 'password':
                $this->body .= "<div><label>$options[label]$req</label>";
                $this->body .= "<input type=$type id='$options[id]' name='$options[name]' value='$options[value]' class='$options[class]' />\n";
		if( $options[note]!='' ) $this->body .= "<p class='note'>$options[note]</p>\n";
		$this->body .= "</div>\n";
            break;
            case 'submit':
            break;
            case 'button':
            break;
        }
    }

    function frmAddTextarea($options) {
	    $req = ($options[req]=='req')?"<em>*</em>":"";
        $this->body .= "<div><label>$options[label]$req</label>";
        $this->body .= "<textarea id='$options[id]' name='$options[name]'  class='$options[class]' cols='$options[cols]'>$options[value]</textarea>";
	if( $options[note]!='' ) $this->body .= "<p class='note'>$options[note]</p>\n";
        $this->body .= "</div>";
    }

    function frmAddSelect($options, $values) {
        $options[id] = isset($options[id])?$options[id]:$options[name];

        $this->body .= "<div><label for='$options[id]'>$options[label]</label>";
        $this->body .= "<select id='$options[id]' name='$options[name]'>";
        for($i=0; $i<sizeof($values); $i++ ) {
            $this->body .= "<option value='".$values[$i][value]."'>".$values[$i][text]."</option>";
        }
        $this->body .= "</select>";
        $this->body .= "</div>";
    }

    function frmAddButton($type, $options) {
        switch($type) {
            case 'submit':
                $this->body .= "<button type=$type name=$options[name] value='$options[value]' class='$options[class]' >";
	        if( isset($options[image]) ) {
                    $this->body .= "<img src=icons/$options[image] alt='' /> ";
	        } 
                $this->body .= $options[text];
                $this->body .= "</button>";
            break;
        }
    }

    function frmAddHTML($html) {
        $this->body .= $html;
    }

    function frmAjaxActivate($output) {
        $this->footer .= "<script>activateAjaxForm('$this->id', '$output');</script>";
    }

} //End Class Form

Class Web {
 function Web() {
 }

 function errHandler($msg, $die=false) {
    $this->errorMsg = "<div class=error>$msg</div>";
    if( $die == 'die' ) {
        print " <div class=fatalError>$msg</div>\n";
        exit;
    }
 }

 function statusTable() {

 $dclass = 'green';
 $dimage = 'images/success-sm.gif';
 $sql = "select count(*) FROM host, info
        WHERE host.name = info.host and info.module = 'availability' and uptime = 0";
 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $down = $s[0];
 if( $down>0 ) {
    $dclass = 'red';
    $dimage = 'images/failed-sm.gif';
 }

 $upclass = 'green';
 $uimage = 'images/success-sm.gif';
 $sql = "select count(*) FROM host, info
        WHERE host.name = info.host and info.module = 'availability' and uptime < 86400";
 $res = mysql_query($sql);
 $s = mysql_fetch_row($res);
 $up = $s[0];
 if( $up>0 ) {
    $upclass = 'amber';
    $uimage = 'images/remaining-sm.gif';
 }

 $html = "
        <table>
        <tr><td><img src=$dimage> Number of hosts not responding on snmp</td><td align=right class=$dclass><a href=?p=uptimeReport.php>$down</a></td></tr>
        <tr><td><img src=$uimage> Number of hosts with uptime < 1 day</td><td align=right class=$upclass><a href=?p=uptimeReport.php>$up</a></td></tr>
        </table>
        ";

 return $html;
 }

#
# Display information about info record
#
 function showInfoRec($info, $vars) {
    global $cfg;

    $fields = '';
    foreach( $vars as $key => $val ) {
        if( $key != 'host' and $key != 'module' ) {
            $fields .= "<tr><td>$key ($val)</td><td>".$info[$key]."</td></tr>";
        }
    }
    $id = $info['id'];
    $table = "
        <b>$info[host]</b><p/>
        <table>
        <tr><td width=50px>module</td><td>".$info['module']."</td></tr>
        $fields
        <tr><td>status</td><td>".$info['status']."</td></tr>
        </table>
    ";
    $f = new Form( array( "name"=>"infoForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Details", ));
    $f->frmAddInput('hidden', array("name"=>"infoid", "value"=>"$id"));
    $f->frmAddHTML($table);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"edit", "image"=>"application_edit.png", "text"=>"Edit"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"delete", "image"=>"delete.png", "text"=>"Delete", "class"=>"red"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"test", "image"=>"transmit.png", "text"=>"Test" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"trim", "image"=>"application.png", "text"=>"Remove Spikes" ));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

#
# Display info record form
#
 function infoRecForm($info, $vars) {
    global $cfg;

    $select = $this->hostStatus($info['status']);
    $id = $info['id'];
    $table = "
        <table>
        <tr><td>module</td><td>".$info['module']."</td></tr>
        </table> ";

    $f = new Form( array( "name"=>"infoForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Edit configuration", ));
    $f->frmAddInput('hidden', array("name"=>"infoid", "value"=>"$id"));
    $f->frmAddHTML($table);
    foreach( $vars as $key => $val ) {
        if( $key != 'host' and $key != 'module' ) {
            $f->frmAddInput('text', array("label"=>"$key", "req"=>"$val", "name"=>"form[$key]", "value" => $info[$key]) ); 
        }
    }
    $f->frmAddSelect(array('label'=>'Status','name'=>'status' ), $select);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"save", "image"=>"accept.png", "text"=>"Save"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel", "class"=>"red"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }


/* Add module stuff */

 function showAddForm($host) {
    global $cfg;

    $html = "
            <label>Select module to add for this host</label>
             ".$this->showModules("addForm[module]")."
        ";
    $modules = $this->showModules();
    $f = new Form( array( "name"=>"moduleForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Add module", ));
    $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    $f->frmAddSelect(array('label'=>'Select module to add for this host','name'=>'addForm[module]' ), $modules);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"add", "image"=>"add.png", "text"=>"Add"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel", "class"=>"red"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function showModuleForm($modVars, $addForm) {
    global $cfg;

    $f = new Form( array( "name"=>"moduleForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Enter configuration details", ));
    $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    foreach( $modVars as $key => $val ) {
        if( $key == 'host' or $key == 'module' ) {
            $value = $addForm[$key];
        } else {
            $value = '';
        }
        $f->frmAddInput('text', array("label"=>"$key", "req"=>"$val", "name"=>"form[$key]", "value" => $value) ); 
    }
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"savemodule", "image"=>"accept.png", "text"=>"Save"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel", "class"=>"red"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function showModules() {
    $i = 0;
    $res = mysql_query("select * from modules where active='TRUE' order by name");
    while( $opt = mysql_fetch_object($res) ) {
        $arr[$i][value] = $opt->name;
        $arr[$i][text]  = $opt->name;
        $i++;
    }
    return $arr;
 }


 function showHostInfo_v2($host) {
    global $cfg;

    $sql = "select * from host where name = '$host'";
    $res = mysql_query($sql) or $html = mysql_error();
    $hostInfo = mysql_fetch_object($res);
    $select = $this->hostStatus($hostInfo->status);

    $table  = "Host Info Form v2<p/>$hostInfo->description<p></p>\n";
    $table .= "<table>
    <tr><td>sysDescr: </td><td>$hostInfo->sysDescr</td></tr>
    <tr><td>Up since: </td><td>".strftime("%Y-%m-%d %H:%M:%S", $hostInfo->lastPoll - $hostInfo->uptime)."</td></tr>
    <tr><td>Vendor: </td><td>$hostInfo->vendor</td></tr>
    <tr><td>OS: </td><td>$hostInfo->OS</td></tr>
    <tr><td>Location: </td><td>$hostInfo->location</td></tr>
    <tr><td>Status: </td><td>$hostInfo->status</td></tr>
    </table>";

    $f = new Form( array( "name"=>"hostForm", "action"=>"$cfg->backEndPath/dump.php", "method"=>"post" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Host details", ));
    $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    $f->frmAddHTML($table);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"edit", "image"=>"application_edit.png", "text"=>"Edit"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"delete", "image"=>"delete.png", "text"=>"Delete", "class"=>"red"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"ping", "image"=>"transmit.png", "text"=>"Ping" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"addmodule", "image"=>"application_add.png", "text"=>"Add module" ));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function showHostInfo($host) {
    global $cfg;

    $sql = "select * from host where name = '$host'";
    $res = mysql_query($sql) or $html = mysql_error();
    $hostInfo = mysql_fetch_object($res);
    $select = $this->hostStatus($hostInfo->status);

    $table  = "$hostInfo->description<p></p>\n";
    $table .= "<table>
    <tr><td>sysDescr: </td><td>$hostInfo->sysDescr</td></tr>
    <tr><td>Up since: </td><td>".strftime("%Y-%m-%d %H:%M:%S", $hostInfo->lastPoll - $hostInfo->uptime)."</td></tr>
    <tr><td>Vendor: </td><td>$hostInfo->vendor</td></tr>
    <tr><td>OS: </td><td>$hostInfo->OS</td></tr>
    <tr><td>Location: </td><td>$hostInfo->location</td></tr>
    <tr><td>Status: </td><td>$hostInfo->status</td></tr>
    </table>";

    $html .= "<input type=submit name=action value='add module'></form>\n<br>";

    $f = new Form( array( "name"=>"hostForm", "action"=>"$cfg->backEndPath/prb_be.php", "method"=>"post" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Host details", ));
    $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    $f->frmAddHTML($table);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"edit", "image"=>"application_edit.png", "text"=>"Edit"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"delete", "image"=>"delete.png", "text"=>"Delete", "class"=>"red"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"ping", "image"=>"transmit.png", "text"=>"Ping" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"addmodule", "image"=>"application_add.png", "text"=>"Add module" ));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function formHostInfo($host) {
    global $cfg;

    $sql = "select * from host where name = '$host'";
    $res = mysql_query($sql) or $html .= mysql_error();
    $hostInfo = mysql_fetch_object($res);
    $select = $this->hostStatus($hostInfo->status);

    $f = new Form( array( "name"=>"hostForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Edit host details", ));
    $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    $f->frmAddInput('text', array("label"=>"Location", "name"=>"hostInfo[location]", "value"=>"$hostInfo->location"));
    $f->frmAddTextarea(array("label"=>"SysDescr", "name"=>"hostInfo[sysDescr]", "value"=>"$hostInfo->sysDescr", "cols"=>"35"));
    $f->frmAddInput('text', array("label"=>"Vendor", "name"=>"hostInfo[vendor]", "value"=>"$hostInfo->vendor"));
    $f->frmAddInput('text', array("label"=>"OS", "name"=>"hostInfo[OS]", "value"=>"$hostInfo->OS"));
    $f->frmAddSelect(array('label'=>'Status','name'=>'status' ), $select);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"save", "image"=>"accept.png", "text"=>"Save"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel", "class"=>"red"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function continueButton($url) {
    global $cfg;
    
    $f = new Form( array( "name"=>"continueForm", "action"=>"$url" ));
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"redirect", "image"=>"application_go.png", "text"=>"Return"));
    $f->frmCloseFieldset();

    return $f->frmOutputHTML();
 }

 function clearButton($host='', $infoid='') {
    global $cfg;
    
    $f = new Form( array( "name"=>"clearForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    if( $host!='' ) {
        $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    } elseif( $infoid!='' ) {
        $f->frmAddInput('hidden', array("name"=>"infoid", "value"=>"$infoid"));
    }
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"application_go.png", "text"=>"Return"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function snmpDiscoveryForm() {
    global $cfg;
    
    $f = new Form( array( "name"=>"discoveryForm", "action"=>"$cfg->backEndPath/discover_be.php?mode=snmp" ));
    $f->frmOpenFieldset( array( "name"=>"manualDiscovery", "legend"=>"SNMP Discovery" ));
    $f->frmAddInput('text', array("label"=>"Host name", "name"=>"host[name]", "req"=>"req"));
    $f->frmAddInput('text', array("label"=>"Community string (default is 'public')", "name"=>"host[community]", ));
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"snmpdiscovery", "image"=>"accept.png", "text"=>"Discover"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"sysinfo", "image"=>"transmit.png", "text"=>"Sysinfo"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function manualDiscoveryForm() {
    global $cfg;
    
    $f = new Form( array( "name"=>"discoveryForm", "action"=>"$cfg->backEndPath/discover_be.php?mode=manual" ));
    $f->frmOpenFieldset( array( "name"=>"manualDiscovery", "legend"=>"Manual Entry" ));
    $f->frmAddInput('text', array("label"=>"Host name", "name"=>"host[name]", "req"=>"req"));
    $f->frmAddInput('text', array("label"=>"Host description", "name"=>"host[description]", "value"=>""));
    $f->frmAddInput('text', array("label"=>"Vendor", "name"=>"host[vendor]", "req"=>"req"));
    $f->frmAddInput('text', array("label"=>"OS", "name"=>"host[OS]", "req"=>"req"));
    $f->frmAddInput('text', array("label"=>"Location", "name"=>"host[location]", "req"=>"req"));
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"manualdiscovery", "image"=>"add.png", "text"=>"Add"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }

 function confirmDeleteForm($host='', $infoid='') {
    global $cfg;
    
    $f = new Form( array( "name"=>"confirmForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    if( $host!='' ) {
        $f->frmAddInput('hidden', array("name"=>"host", "value"=>"$host"));
    } elseif( $infoid!='' ) {
        $f->frmAddInput('hidden', array("name"=>"infoid", "value"=>"$infoid"));
    }
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"deleteconfirm", "image"=>"delete.png", "text"=>"Delete", "class"=>"red"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"application_go.png", "text"=>"Return"));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('infoBox');

    return $f->frmOutputHTML();
 }
/* Host info stuff */

 function hostStatus() {
    $i = 0;
    $res = mysql_query("select name from hostStatus order by id");
    while( $opt = mysql_fetch_object($res) ) {
        $arr[$i] = array('value'=>$opt->name, 'text'=>$opt->name);
        $i++;
    }
    return $arr;
 }

 function roundBox($content, $header='') {
        $html = "
<div class='cssbox'>
   <div class='cssbox_head'>
        <h2>$header</h2>
   </div>
   <div class='cssbox_body'>
        $content
   </div>
</div> ";

        return $html;
 }

 function sqlviewDetails($id) {
    global $cfg, $trid, $chid;
    $sql = "select count(*) as childcount from views where parent='$id'"; 
    $res = mysql_query( $sql );
    $cnt = mysql_fetch_object($res);

    $sql = "select * from views where id='$id'";
    $res = mysql_query( $sql );
    $view = mysql_fetch_object($res);
    $view->childcount = $cnt->childcount;

    $html =  "<div id=viewTab>
          <table><tr>
          <td>id:</td><td>viewid=$id </td></tr>
          <td>name:</td><td>$view->name </td></tr>
          <td>description:</td><td> $view->description</td></tr>
           ";
    if($view->status=='view') {
        $html .= "<td>query:</td><td> $view->query</td></tr>";
    }
    $html .= "</table>";

    $f = new Form( array( "name"=>"infoForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Details", ));
    $f->frmAddInput('hidden', array("name"=>"viewid", "value"=>"$id"));
    $f->frmAddInput('hidden', array("name"=>"trid", "value"=>"$trid"));
    $f->frmAddInput('hidden', array("name"=>"chid", "value"=>"$chid"));
    $f->frmAddHTML($html);
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"editfolder", "image"=>"folder_edit.png", "text"=>"Edit"));
    if($view->childcount==0) {
        $f->frmAddButton('submit', array( "name"=>"act", "value"=>"rmfolder", "image"=>"bin.png", "text"=>"Delete", "class"=>"red"));
    }
    if($view->status == 'folder') {
        $f->frmAddButton('submit', array( "name"=>"act", "value"=>"addfolder", "image"=>"folder_add.png", "text"=>"Add folder"));
        $f->frmAddButton('submit', array( "name"=>"act", "value"=>"addview", "image"=>"application_add.png", "text"=>"Add view"));
    }
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('topContent');

    $html = $f->frmOutputHTML();
    if( $view->status == 'view' ) {
        $html .= "<div id=graphs>";
        $html .= $this->showHcGraphs($view->query);
        $html .= "</div>";
    }
    return $html;

 }

 function showHcGraphs($sql) {
        global $cfg;
        $nl = "\n";

        if( $sql != '' ) {
            require_once $cfg->libPath."/Highcharts.php";
            $res = mysql_query($sql);
            $jscode="";
            while( $info = mysql_fetch_assoc($res) ) {
                $mod = $info['module'];
                if(! is_file($cfg->modPath."/$mod.php") ) {
                    $err .= "Can't load $cfg->modPath/$mod.php<P>";
                }
                require_once $cfg->modPath."/$mod.php";
                $m = new $mod($info);
                $m->rrdChkPath();
                $m->prepHcOptions();
                $m->fetchData('-8h');
                $m->hcOptions->chart->renderTo = "container-$m->id";
                $chart = new Highcharts($m->hcOptions);
                $jscode .= $chart->build_code('myHighChart','mootools',true);

                $html .= "<p><h3>$m->name $m->description</h3>\n";
                if( $m->connection !== '' ) $html .= "$m->connection";
                $html .= "</p>\n";
                $html .= "<a href=?p=browse.php&id=$m->id>";
                $html .= "<div id=container-$m->id>loading graph...</div>\n";
                $html .= "</a><br>\n";
            }
       }
       if( $err ) { return $err; } else { return $jscode.$nl.$html; }
 }

 function showGraphs($sql) {
        global $cfg;

        if( $sql != '' ) {
            $res = mysql_query($sql);
            while( $info = mysql_fetch_assoc($res) ) {
                $mod = $info['module'];
                if(! is_file($cfg->modPath."/$mod.php") ) {
                    $html .= "Can't load $cfg->modPath/$mod.php<P>";
                }
                require_once $cfg->modPath."/$mod.php";
                $m = new $mod($info);
                $m->rrdChkPath();
                $m->pngChkPath();
                $m->Graph("1day_TN", -86400, $cfg->TNWidth, $cfg->TNHeight);
                $html .= "<p><b>$m->name $m->description</b>infoid = $m->id<br>\n";
                if( $m->connection !== '' ) $html .= "$m->connection";
                $html .= "<p>\n";
                $html .= "<a href=?p=browse.php&id=$m->id>";
                $html .= "<img src=$m->pngURL-1day_TN.png></a><br>\n";
                $html .= "</a><br>\n";
            }
       }
       if( $err ) { return $err; } else { return $html; }
 }

 function sqlviewForm($id) {
    global $cfg;
    $sql = "select * from views where id='$id'";
    $res = mysql_query( $sql );
    $view = mysql_fetch_object($res);

    $f = new Form( array( "name"=>"viewForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Edit folder settings", ));
    $f->frmAddInput('hidden', array("name"=>"viewid", "value"=>"$id"));
    $f->frmAddInput('text', array("label"=>"Name", "name"=>"form[name]", "value"=>"$view->name"));
    $f->frmAddInput('text', array("label"=>"Description", "name"=>"form[description]", "value"=>"$view->description"));
    if( $view->status == 'view' ) {
        $f->frmAddTextarea(array("label"=>"Query definition", "name"=>"form[query]", "value"=>"$view->query", "cols"=>"60"));
    }
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"updatefolder", "image"=>"disk.png", "text"=>"Save"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel" ));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('topContent');

    return $f->frmOutputHTML();

 }

 function addfolderForm($parent, $status) {
    global $cfg, $trid, $chid;

    $f = new Form( array( "name"=>"viewForm", "action"=>"$cfg->backEndPath/prb_be.php" ));
    $f->frmOpenFieldset( array( "name"=>"details", "legend"=>"Enter folder settings", ));
    $f->frmAddInput('hidden', array("name"=>"form[parent]", "value"=>"$parent"));
    $f->frmAddInput('hidden', array("name"=>"form[status]", "value"=>"$status"));
    $f->frmAddInput('hidden', array("name"=>"trid", "value"=>"$trid"));
    $f->frmAddInput('hidden', array("name"=>"chid", "value"=>"$chid"));
    $f->frmAddInput('text', array("label"=>"Name", "name"=>"form[name]","value"=>"unnamed"));
    $f->frmAddInput('text', array("label"=>"Description", "name"=>"form[description]", "value"=>""));
    if( $status == 'view' ) {
        $f->frmAddTextarea(array("label"=>"Query definition", "name"=>"form[query]", "value"=>"", "cols"=>"60"));
    }
    $f->frmCloseFieldset();
    $f->frmOpenFieldset( array( "name"=>"Buttons", "legend"=>"Actions" ));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"savefolder", "image"=>"disk.png", "text"=>"Save"));
    $f->frmAddButton('submit', array( "name"=>"act", "value"=>"cancel", "image"=>"cancel.png", "text"=>"Cancel" ));
    $f->frmCloseFieldset();
    $f->frmAjaxActivate('topContent');

    return $f->frmOutputHTML();
 }


} // End class
?>
