<?php
 // $Id: ciscoload.php,v 1.5 2006/07/14 08:42:15 guizy Exp $
 //
 Class ciscoload extends Info {

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
            "DS:l1:GAUGE:600:U:U",
            "DS:l5:GAUGE:600:U:U",
            "DS:l15:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $l1  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.2.1.56.0");
        $l5  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.2.1.57.0");
        $l15 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.9.2.1.58.0");

        $this->outtext = "N:$user:$nice:$system";
        $ret = rrd_update($this->rrdFile, "N:$l1:$l5:$l15" );
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
        "DEF:val1=".$this->rrdFile.":l1:AVERAGE",
        "DEF:val2=".$this->rrdFile.":l5:AVERAGE",
        "DEF:val3=".$this->rrdFile.":l15:AVERAGE",
        "LINE2:val1#00CC00: User\\t\\t",
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val2#0000FF: Nice\\t\\t",
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "LINE2:val3#FF0000: System\\t",
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
