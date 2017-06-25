<?php

 // Change modname to you module name. It must be the same
 // as the filename without the .php extension.
 Class netbotztemp extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,                 // specify which variables are required
            array (
            'description' => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
                "DS:t1:GAUGE:600:U:U",
                "DS:t2:GAUGE:600:U:U",
                "DS:t3:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $t1 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.5528.30.10.1.0");
        $t2 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.5528.30.10.62.0");
        $t3 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.5528.30.10.63.0");

        $this->outtext = "N:$t1:$t2:$t3";
        $ret = rrd_update($this->rrdFile,"N:$t1:$t2:$t3");
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
        "-v Temperature",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":t1:AVERAGE",
        "DEF:val2=".$this->rrdFile.":t2:AVERAGE",
        "DEF:val3=".$this->rrdFile.":t3:AVERAGE",
        "HRULE:25#0000FF: Temp = 25C\\l",
        "LINE2:val1#00CC00: Temp Internal\\t",
        "GPRINT:val1:LAST:Current\\: %.2lf\\t",
        "GPRINT:val1:MAX:Max\\: %.2lf\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val2#0000FF: Temp Windows\\t",
        "GPRINT:val2:LAST:Current\\: %.2lf\\t",
        "GPRINT:val2:MAX:Max\\: %.2lf\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val3#FF0000: Temp Unix\\t\\t",
        "GPRINT:val3:LAST:Current\\: %.2lf\\t",
        "GPRINT:val3:MAX:Max\\: %.2lf\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.2lf\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
