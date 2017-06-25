<?php

 Class aixdiskbusy extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            	'ifIndex'   => 'req',
		'community' => 'opt',
            ));
    }

    function CreateDB() {
	
	$numdisks = $this->ifIndex;
	for( $n=0; $n<$numdisks; $n++) {
		$DS[$n]  = "DS:dbusy$n:GAUGE:600:U:U";
	}

        $this->createOpts = array_merge(
            	$DS,
            	$this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

	if( $this->community == '' || $this->community == 'public' ) $this->community = 2000;
        $fp = fsockopen("$this->host", "$this->community", $errno, $errstr, 10);
        if (!$fp) {

        } else {
            $val='';
            while (!feof($fp)) {
                $val.=fgets($fp, 128);
            }
            fclose($fp);
        }

	preg_match_all("/diskbusy\.rrd +[0-9]+:(.*)/", $val, $res);

	$db=$res[1][0];
	$usage = "N:$db";

        $this->outtext = $usage;
        $ret = rrd_update($this->rrdFile, $this->outtext );
        if ( $ret == 0 ) {
            $err = rrd_error();
            echo "ERROR occurred: $err\n";
        }
        return $ret;
    }

    function Graph($ext, $time, $w=W, $h=H) {

	$opts = array(
        "-w", $w, "-h", $h,
        "-t", $this->description,
        "-v Disk IOs",
        "-s ".$time);
		

	$lineDef = array();
	$numdisks = $this->ifIndex;
	for( $n=0; $n<$numdisks; $n++) {
		$def[$n]  = "DEF:db$n=".$this->rrdFile.":dbusy$n:AVERAGE";
		$lineBDef[$n] = array(
        		"LINE2:db$n#".$this->colour($n).": disk$n busy\\t",
        		"GPRINT:db$n:LAST:Current\\: %.0lf %%\\t",
        		"GPRINT:db$n:MAX:Max\\: %.0lf %%\\t",
        		"GPRINT:db$n:AVERAGE:Average\\: %.0lf %%\\l");

		$lineDef = array_merge($lineDef,$lineBDef[$n]);
	}	

        $graph = array_merge(
        	$this->graphDefOpts, 
		$opts,
		$def,
		$lineDef,
		array (
        		$this->lastUpdate()
		)
        );

	$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );

        if (! is_array($ret) && $this->debug ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
	    var_dump($graph);
        }

	}

	function colour($n) {
	$colours = array( 
		   	"0cc3f1",
    			"0c9ef1",
    			"177bec",
    			"2757e2",
    			"9c2de1",
    			"ba1fe7",
    			"ef2df6",
    			"ec37a1",
    			"e54747",
    			"ff5400",
    			"e86b20",
    			"ff9e06",
    			"ffc90f",
    			"eccc25",
    			"e2d727",
    			"c6d617",
    			"9bd924",
    			"77d146",
    			"30dd2c",
    			"1aea55",
    			"12fd86",
    			"2cffdf",
    			"16effd",
    			"36d1f8"
		);

	return $colours[$n];
	}
}
?>
