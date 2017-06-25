<?php

 Class aixdiskio extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,
            array(
            'community'   => 'req',
            'ifIndex'     => 'req',
            'ifDescr'     => 'opt',
            'connection'  => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:input:GAUGE:600:U:U",
            "DS:output:GAUGE:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $n  = $this->ifIndex;

        $fp = fsockopen("$this->host", 2000, $errno, $errstr, 10);
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
	$in = $di[$n];

        preg_match_all("/diskwrite\.rrd(.*)/", $val, $res);
        $do = explode(":", $res[1][0]);
	array_shift($do);
	$out = $do[$n];

        $this->outtext = "N:$in:$out";
	$ret = rrd_update($this->rrdFile,"N:$in:$out");
        if ( $ret == 0 ) {
            $err = rrd_error();
            echo "ERROR occurred: $err\n";
        }
        return $ret;
    }

    function Graph($ext, $time, $w=W, $h=H) {

        $connection = "COMMENT:\\l";
        if( $this->connection !== '' ) $connection = "COMMENT:$this->connection\\l";
        $graph = array_merge(
        $this->graphDefOpts, array (
        "-w", $w, "-h", $h,
        "-t", $this->description,
        "-v Bandwidth",
        "-s ".$time,
        "DEF:in=".$this->rrdFile.":input:AVERAGE",
        "DEF:out=".$this->rrdFile.":output:AVERAGE",
        $connection,
        "COMMENT:\\s",
        "AREA:in#00CC00:Disk IO  IN\\t",
        "GPRINT:in:LAST:Current\\: %8.1lf kB/s\\t",
        "GPRINT:in:MAX:Max\\: %8.1lf kB/s\\t",
        "GPRINT:in:AVERAGE:Average\\: %8.1lf kB/s\\l",
        "LINE2:out#0000FF:Disk IO OUT\\t",
        "GPRINT:out:LAST:Current\\: %8.1lf kB/s\\t",
        "GPRINT:out:MAX:Max\\: %8.1lf kB/s\\t",
        "GPRINT:out:AVERAGE:Average\\: %8.1lf kB/s\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
