<?php

 Class aixcpu extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'ifIndex' => 'req',
            'limit'   => 'opt',
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

        $offset = $this->ifIndex*4;
        $cpu0   = snmpget($this->host, $this->community, ".1.3.6.1.2.1.25.3.3.1.2.".(1+$offset) );
        $cpu1   = snmpget($this->host, $this->community, ".1.3.6.1.2.1.25.3.3.1.2.".(2+$offset) );
        $cpu2   = snmpget($this->host, $this->community, ".1.3.6.1.2.1.25.3.3.1.2.".(3+$offset) );
        $cpu3   = snmpget($this->host, $this->community, ".1.3.6.1.2.1.25.3.3.1.2.".(4+$offset) );

        $this->outtext = "N:$cpu0:$cpu1:$cpu2:$cpu3";
        $ret = rrd_update($this->rrdFile, "N:$cpu0:$cpu1:$cpu2:$cpu3" );
        if ( $ret == 0 ) {
            $err = rrd_error();
            echo "ERROR occurred: $err\n";
        }
        return $ret;
    }

    function Graph($ext, $time, $w=W, $h=H) {

        $offset = $this->ifIndex*4;

	if( $this->limit and $this->limit != 0 ) {
		$max = $this->limit;
		$limiter = array (
			"CDEF:val1=v1,0,$max,LIMIT",
			"CDEF:val2=v2,0,$max,LIMIT",
			"CDEF:val3=v3,0,$max,LIMIT",
			"CDEF:val4=v4,0,$max,LIMIT"
			);
	} else {
		$limiter = array (
			"CDEF:val1=v1",
			"CDEF:val2=v2",
			"CDEF:val3=v3",
			"CDEF:val4=v4"
			);
	}

        $graph = array_merge(
        $this->graphDefOpts, array (
        "-w", $w, "-h", $h,
        "-t", $this->description,
        "-v CPU Load",
        "-s ".$time,
        "DEF:v1=".$this->rrdFile.":CPU0:AVERAGE",
        "DEF:v2=".$this->rrdFile.":CPU1:AVERAGE",
        "DEF:v3=".$this->rrdFile.":CPU2:AVERAGE",
        "DEF:v4=".$this->rrdFile.":CPU3:AVERAGE",
	$limiter[0],
	$limiter[1],
	$limiter[2],
	$limiter[3],
        "LINE2:val1#00CC00: CPU".(0+$offset),
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val2#0000FF: CPU".(1+$offset),
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val3#FF0000: CPU".(2+$offset),
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val4#00FFFF: CPU".(3+$offset),
        "GPRINT:val4:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val4:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val4:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
