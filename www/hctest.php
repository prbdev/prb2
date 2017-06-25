<?php
error_reporting(true);

class Highcharts
{
  private $_code;
  private $options;

  public function highcharts($name, $options, $engine='jquery')
  {
    $this->_code = $this->build_code($name, $options, $engine);
  }

  private function build_code($name, $options, $engine='jquery')
  {
    $code = "<script type=\"text/javascript\">\n";
    if ($engine == 'mootools')
      $code .= "window.addEvent('domready', function() {\n";
    else
      $code .= "$(document).ready(function() {\n";
    $code .= 'var ' . $name . " = new Highcharts.Chart({\n";
    $code .= $this->build_options($options);
    $code .= "});});\n</script>";
    return $code;
  }

  private function build_options($options)
  {
    $code = array();
    foreach ($options as $key => $option)
      $code []= $this->build_option($key, $option);
    return implode(',', $code)."\n";
  }

  private function build_option($key, $options)
  {
    $code = $key . ': ';
    if (!is_array(reset($options))) {
      $code .= $this->build_properties($options);
    }
    else
      {
        $code .= "[";
        $opts = array();
        foreach ($options as $option)
          {
            $opts []= $this->build_properties($option);
          }
        $code .= implode(',', $opts)."\n";
        $code .= "]";
      }
    return $code;
  }

  private function build_properties($options)
  {
    $code = array();
    foreach ($options as $key => $value)
      $code []= $this->build_property($key, $value);
    return "{\n" . implode(',', $code) . "\n}\n";
  }

  private function build_property($key, $value)
  {
    $code = $key . ': ';
    if ($value instanceof HighchartsArray)
      $code .= $value->get();
    else
      $code .= '\'' . $value . '\'';
    return $code;
  }

  public function getCode()
  {
    return $this->_code;
  }

}

class HighchartsArray
{
  private $_array;
  public function HighchartsArray($array)
  {
    $new_array = array();
    foreach ($array as $elem)
      if (is_string($elem))
        $new_array []= '\'' . $elem . '\'';
      else
        $new_array []= $elem;
    $this->_array = $new_array;
  }
  public function get()
  {
    $js_array = '[';
    $js_array .= implode(',', $this->_array);
    $js_array .= ']';
    return $js_array;
  }
}


include 'defch.php';

$data1 = array(134,132,243,133,276,435,223,33,334,24,145,33,24,671,87,98);
$data2 = array(23,12,24,33,76,351,233,133,314,241,45,133,124,71,187,298);

$d1 = new HighchartsArray($data1);
$d2 = new HighchartsArray($data2);

$chart[series][0][name]="first series";
$chart[series][0][type]="spline";
$chart[series][0][data]=$d1;
$chart[series][1][name]="ser2";
$chart[series][1][type]="area";
$chart[series][1][data]=$d2;

$mychart = new Highcharts("myChart", $chart, 'mootools');

?>
<script type="text/javascript" src="js/mootools-core-1.4.5.js"></script>
<script type="text/javascript" src="js/mootools-more-1.4.0.1.js"></script>
<script type='text/javascript' src='js/hc/adapters/mootools-adapter.js'></script>
<script type='text/javascript' src='js/hc/highcharts.js'></script>
<script type='text/javascript' src='js/hc/themes/grid.js'></script>
<body>
<?php echo $mychart->getCode();?>
<div id=my_chart>loading graph...</div>
</body>
