<?php

 Class ciscocpu extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'community'   => 'req',
            'description' => 'req',
            'limit'       => 'opt'
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:cpu1:GAUGE:600:U:U",
            "DS:cpu2:GAUGE:600:U:U",
            "DS:cpu3:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $cpu1 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.9.109.1.1.1.1.6.1");
        $cpu2 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.9.109.1.1.1.1.7.1");
        $cpu3 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.9.109.1.1.1.1.8.1");

        $this->outtext = "N:$cpu1:$cpu2:$cpu3";
        $ret = rrd_update($this->rrdFile, "N:$cpu1:$cpu2:$cpu3" );
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
        "-v CPU Load percentage",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":cpu1:AVERAGE",
        "DEF:val2=".$this->rrdFile.":cpu2:AVERAGE",
        "DEF:val3=".$this->rrdFile.":cpu3:AVERAGE",
        "LINE2:val1#00CC00:  5 sec \\t",
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val2#0000FF:  1 min\\t",
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val3#FF0000:  5 min\\t",
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
