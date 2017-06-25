<?php

 Class aixiostat extends Info {

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
		$DSi[$n]  = "DS:diski$n:GAUGE:600:U:U";
		$DSo[$n]  = "DS:disko$n:GAUGE:600:U:U";
	}

        $this->createOpts = array_merge(
            	$DSi,
            	$DSo,
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

	preg_match_all("/diskread\.rrd(.*)/", $val, $res);
	$di = explode(":", $res[1][0]);
	array_shift($di);

	preg_match_all("/diskwrite\.rrd(.*)/", $val, $res);
	$do = explode(":", $res[1][0]);
	array_shift($do);

	$dis = join(":", $di);
	$dos = join(":", $do);

	$usage = "N:$dis:$dos";

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
		$defi[$n]  = "DEF:di$n=".$this->rrdFile.":diski$n:AVERAGE";
		$defo[$n]  = "DEF:dout$n=".$this->rrdFile.":disko$n:AVERAGE";
		$cdefo[$n] = "CDEF:do$n=dout$n,-1,*";
		$lineIDef[$n] = array(
        		"LINE2:di$n#".$this->colour($n).": disk$n in\\t",
        		"GPRINT:di$n:LAST:Current\\: %.0lf\\t",
        		"GPRINT:di$n:MAX:Max\\: %.0lf\\t",
        		"GPRINT:di$n:AVERAGE:Average\\: %.0lf\\l");
		$lineODef[$n] = array(
        		"LINE2:do$n#".$this->colour($n).": disk$n out\\t",
        		"GPRINT:do$n:LAST:Current\\: %.0lf\\t",
        		"GPRINT:do$n:MAX:Max\\: %.0lf\\t",
        		"GPRINT:do$n:AVERAGE:Average\\: %.0lf\\l");

		$lineDef = array_merge($lineDef,$lineIDef[$n],$lineODef[$n]);
	}	

        $graph = array_merge(
        	$this->graphDefOpts, 
		$opts,
		$defi,
		$defo,
		$cdefo,
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
