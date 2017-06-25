<?php

 # $Id: ucdload.php,v 1.1 2006/08/09 06:23:49 guizy Exp $

 Class ucdload extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'description' => 'req',
            'limit'       => 'opt')
            );
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:l1:GAUGE:600:U:U",
            "DS:l5:GAUGE:600:U:U",
            "DS:l15:GAUGE:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $l1  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.1");
        $l5  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.2");
        $l15 = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.3");

        $this->outtext = "N:".$l1.":".$l5.":".$l15;
        $ret = rrd_update($this->rrdFile, "N:".$l1.":".$l5.":".$l15 );
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
        "-v Load Average",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":l1:AVERAGE",
        "DEF:val2=".$this->rrdFile.":l5:AVERAGE",
        "DEF:val3=".$this->rrdFile.":l15:AVERAGE",
        "LINE2:val1#00CC00: 1 min\\t\\t",
        "GPRINT:val1:LAST:Current\\: %.2lf\\t",
        "GPRINT:val1:MAX:Max\\: %.2lf\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val2#0000FF: 5 min\\t\\t",
        "GPRINT:val2:LAST:Current\\: %.2lf\\t",
        "GPRINT:val2:MAX:Max\\: %.2lf\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val3#FF0000: 15 min\\t",
        "GPRINT:val3:LAST:Current\\: %.2lf\\t",
        "GPRINT:val3:MAX:Max\\: %.2lf\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.2lf\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
         if (! is_array($ret) ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print_r($graph);
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }
	}
}
?>
