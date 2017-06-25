#!/usr/local/bin/php
<?
// $Id: fetch.php,v 1.1.1.1 2006/07/09 11:06:15 guizy Exp $
//
 ##
 ## Get command line args
 ##
 $me = $argv[0];
 if( $argc != 2 ) {
    print "Usage: $me <rrd_file>\n";
    exit;
 }

 $rrdFile = $argv[1];

 if(! is_file($rrdFile) ) { die("$rrdFile not found\n"); }

 ##
 ## demonstration of the rrd_fetch() command
 ##



  $opts = array ( "AVERAGE", "--start", "-1h" );

  $ret = rrd_fetch($rrdFile, $opts, count($opts));
 
  ##
  ## if $ret is an array, rrd_fetch() succeeded
  ## 
  if ( is_array($ret) )
  {
      echo "Start time    (epoch): $ret[start]\n";
      echo "End time      (epoch): $ret[end]\n";
      echo "Step interval (epoch): $ret[step]\n";

      ##
      ## names of the DS's (data sources) will be 
      ## contained in the array $ret[ds_namv][..]
      ##
      for($i = 0; $i < count($ret[ds_namv]); $i++)
      {
          $tmp = $ret[ds_namv][$i];
          echo "$tmp \n";
      }

      ##
      ## all data will be packed into the
      ## $ret[data][..]  array
      ##
      for($i = 0; $i < count($ret[data]); $i++)
      {
          $tmp = $ret[data][$i];
          echo "$i $tmp\n";
      }
  }
  else
  {
      $err = rrd_error();
      echo "fetch() ERROR: $err\n";
  }

?>
