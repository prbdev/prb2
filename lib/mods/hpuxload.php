<?php

 Class hpuxload extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'description' => 'req',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:user:COUNTER:600:U:U",
            "DS:system:COUNTER:600:U:U",
            "DS:nice:COUNTER:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $user    = snmpget($this->host, $this->community, ".1.3.6.1.4.1.11.2.3.1.1.13.0");
        $system  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.11.2.3.1.1.14.0");
        $nice    = snmpget($this->host, $this->community, ".1.3.6.1.4.1.11.2.3.1.1.16.0");

        $this->outtext = "N:$user:$nice:$system";
        $ret = rrd_update($this->rrdFile, "N:$user:$system:$nice" );
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
        "-v Load average",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":user:AVERAGE",
        "DEF:val2=".$this->rrdFile.":system:AVERAGE",
        "DEF:val3=".$this->rrdFile.":nice:AVERAGE",
        "LINE2:val1#00CC00: User\\t\\t",
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val2#0000FF: System\\t",
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val3#FF0000: Nice\\t\\t",
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
