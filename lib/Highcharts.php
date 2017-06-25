<?php

/* USAGE ****************************************************

$myChart = new Highcharts($options);
$data = array(1,2,32,12,42,5,8,12,9,2,54,65,78,99,76,102);
$d = new hcArray($data);
$myChart->hcOptions->series[0]->data = $d->get();
$myChart->hcOptions->series[0]->name = "data series 1";

echo $myChart->build_code('myHighChart','mootools');
<div id='chart1'>loading chart...</div>

************************************************************/

class Highcharts
{

 public $hcOptions;

 function Highcharts($myOptions) {
    $this->hcOptions->chart->renderTo = "chart1";
    $this->hcOptions = $myOptions;
 }

 function build_code($name,$engine='jquery',$scr=true) {
    $head = "";
    $tail = "";
    if($scr) {
        $head = "<script type=\"text/javascript\">\n";
        $tail = "</script>";
    }
    $code = $head;
    if ($engine == 'mootools')
      $code .= "window.addEvent('domready', function() {\n";
    else
      $code .= "$(document).ready(function() {\n";
    $code .= 'var ' . $name . " = new Highcharts.Chart({";
    $code .= $this->getCode($this->hcOptions);
    $code .= "});});\n";
    $code .= $tail;
    return $code;
 }

 function getCode() {
    $_code = "";
    $_code .= $this->iterObj($this->hcOptions);
    return $_code;
 }    

 function iterObj($obj, $key=true) {
    foreach($obj as $_k => $v) {
        $k="$_k:";
        if(!$key) unset($k);
        if( is_object($v) ) {
            $out[]="$k {".$this->iterObj($v)."}";
        } else {
            if(is_string($v)) $val="$k '$v'";
            if(is_numeric($v)||$_k=='data') $val= "$k $v";
            if($this->is_function($v)) $val= "$k $v";
            if(is_bool($v)&&$v) $val="$k true";
            if(is_bool($v)&& !$v) $val="$k false";
            if(is_array($v)) {
                $val="$k [".$this->iterObj($v, false)."]";
            }
            $out[] = $val;
        }
    }
    return join(',',$out);
 }

 function is_function($val) {
    $val = trim($val);
    $pos1 = strpos($val, "function()");
    $pos2 = strpos($val, "Date.UTC");
    // Note use of ===.  Simply == would not work as expected
    if ($pos1 === false && $pos2 === false) {
        return false;
    } else {
        return true;
    }
 }
}

class hcArray {

 private $_array;

 function hcArray($array) {
    $this->_init($array);
 }

 function _init($array) {
    $new_array = array();
    foreach ($array as $elem)
      if (is_string($elem))
        $new_array[] = '\'' . $elem . '\'';
      else
        $new_array[] = $elem;
    $this->_array = $new_array;
 }

 function get($array=array()) {
    if(count($array)>0) $this->_init($array);
    $js_array = '[';
    $js_array .= implode(',', $this->_array);
    $js_array .= ']';
    return $js_array;
 }
}

?>
