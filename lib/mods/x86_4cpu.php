<?php

 Class x86_4cpu extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'description' => 'req'
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:CPU0:GAUGE:600:U:U",
            "DS:CPU1:GAUGE:600:U:U",
            "DS:CPU2:GAUGE:600:U:U",
            "DS:CPU3:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $cpu0   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.232.11.2.3.1.1.3.0");
        $cpu1   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.232.11.2.3.1.1.3.1");
        $cpu2   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.232.11.2.3.1.1.3.2");
        $cpu3   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.232.11.2.3.1.1.3.3");

        $this->outtext = "N:$cpu0:$cpu1:$cpu2:$cpu3";
        $ret = rrd_update($this->rrdFile, "N:$cpu0:$cpu1:$cpu2:$cpu3" );
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
        "-v CPU Load",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":CPU0:AVERAGE",
        "DEF:val2=".$this->rrdFile.":CPU1:AVERAGE",
        "DEF:val3=".$this->rrdFile.":CPU2:AVERAGE",
        "DEF:val4=".$this->rrdFile.":CPU3:AVERAGE",
        "LINE2:val1#00CC00: CPU0",
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val2#0000FF: CPU1",
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val3#FF0000: CPU2",
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val4#00FFFF: CPU3",
        "GPRINT:val4:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val4:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val4:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
