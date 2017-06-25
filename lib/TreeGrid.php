<?php

Class treeGrid {

    var $columns; // nr of columns per row in the table
    var $treename;
    var $tgHTML;

    function treeGrid($options) {
        $this->treename = $options['treename'];
        $this->indent = ($options['indent']!='')?$options['indent']:18;
        $this->columns = $options['cols'];
        $this->tgHTML  = "";
    }

    function tgAddRow($pid, $level, $row=array(), $type='folder') {
	$arr = explode('-', $pid);
	array_pop($arr);
	$parentId = implode('-', $arr);
        $rowState = $_SESSION[$this->treename]->{$pid};
        $width = $level*$this->indent;
        $imageClass = 'plsmns';
        if(! isset($rowState)) {
            $open = 'close';
            if( $_SESSION[$this->treename]->{$parentId}=='expand' ) {
            	$open = 'open';
            }
            if( $level==0 ) $open = 'open';
            $rowState = "collapse $open";
        }
        if( preg_match('/expand/', $rowState) ) {
            $image = "images/tg_expand_opened.png";
        } else {
            $image = "images/tg_expand_closed.png";
        }
        if( $type == 'file' ) {
            $image = "images/file.png";
            $imageClass = 'file';
        }

        $tgRow  = "<tr id=$pid class='".$rowState."' level='$level'>";
        $tgRow .= "<td class=clicker><img class=blank src=images/blank.gif width=$width height=0px>";
        $tgRow .= "<img class=$imageClass id=img_$pid src=$image >";
        $tgRow .= $row[0];
        $tgRow .= "</td>";
        for($i=1;$i<$this->columns;$i++) {
            $tgRow .= "<td>$row[$i]</td>";
        }
        $tgRow .= "</tr>";

        $this->tgHTML .= $tgRow;
    }

    function tgOutputHTML() {
        return $this->tgHTML;
    }

    function tgAddControls() {
        return "<script>addTreeEvents('$this->treename');</script>";
    }
} //End Class
?>
