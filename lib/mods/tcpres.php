<?php

 Class tcpres extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'description' => 'req',
            'ifIndex'     => 'req',
            'send'        => 'req',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:time:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {


        $time_start = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
        $fp = fsockopen("$this->host", $this->ifIndex, $errno, $errstr, 10);
        if (!$fp) {
            $time = "U";
        } else {
            fwrite($fp, $this->send);
            $val='';
            while (!feof($fp)) {
                $val.=fgets($fp, 128);
            }
            fclose($fp);
            $time_end = preg_replace('/^0?(\S+) (\S+)$/X', '$2$1', microtime());
            $time = $time_end - $time_start;
        }

        $this->outtext = "N:$time";
        $ret = rrd_update($this->rrdFile, "N:$time" );
        if ( $ret == 0 ) {
            $err = rrd_error();
            echo "ERROR occurred: $err\n";
        }
        return $ret;
    }

    function Graph($ext, $time, $w=W, $h=H) {

        $graph = array_merge(
        $this->graphDefOpts, array (
        "-w", $w, "-h", $h,
        "-t", $this->description,
        "-v Response time",
        "-s ".$time,
        "DEF:time=".$this->rrdFile.":time:AVERAGE",
        "LINE2:time#FF0000: Response time",
        "GPRINT:time:LAST:Current\\: %7.1lf %ss ",
        "GPRINT:time:MAX:Max\\: %7.1lf %ss ",
        "GPRINT:time:AVERAGE:Average\\: %7.1lf %ss\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
