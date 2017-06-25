<?php

 $subMenu = "
<div id=subMenu>
Browse by custom views (sql queries)
</div>";

if(isset($_REQUEST['viewid'])) {
    $viewid = $_REQUEST['viewid'];
} else { 
    $viewid = 1;
}
$w = new Web();
$details = $w->sqlViewDetails($viewid);

 $pageMain .= "
<style type=\"text/css\" media=\"screen\">
/*<![CDATA[*/
#C_0 {
	/*height: 800px;*/
	overflow: auto;
}
/*]]>*/
</style>

<div id=progress></div>
<div id=\"wrapper\" style=\"border:1px solid #E0E0E0; width: 100%;\">
    <div id=\"container\">
        <div id=\"content\">
            <table id=tree width=100%>
            <tbody id=customtree class=grid>
            </tbody>
            </table>
	</div>
     </div>
	
     <div id=\"sidebar\">
          <div id=right>
           <div id=view_head> View Pane </div>
           <div id=C_0>
             <div id=topContent>
            $details
            </div>
           </div>
          </div>
     </div>
     <div class=\"clearing\">&nbsp;</div>
</div>
<script type=\"text/javascript\">
 window.addEvent('domready', function() {
    mkTree('customtree');
    addTreeEvents('customtree');
 });
 // Save treestate to php session
 window.addEvent('unload', function() {
    saveTreeState('customtree');
 });
</script>";
?>
