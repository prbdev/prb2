<?php

 Class brocade_fc extends Info {

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
            'in'          => 'opt',
            'out'         => 'opt',
            'limit'       => 'opt'
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:input:COUNTER:600:U:U",
            "DS:output:COUNTER:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $if  = $this->ifIndex;
        $in  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.1588.2.1.1.1.6.2.1.12.".$if);
        $out = snmpget($this->host, $this->community, ".1.3.6.1.4.1.1588.2.1.1.1.6.2.1.11.".$if);

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
        "-v RX/Tx Words in bps",
        "-s ".$time,
        "DEF:input=".$this->rrdFile.":input:AVERAGE",
        "DEF:output=".$this->rrdFile.":output:AVERAGE",
        "CDEF:inbits=input,8,*",
        "CDEF:outbits=output,8,*",
        $connection,
        "COMMENT:\\s",
        "AREA:inbits#00CC00:  Incomming traffic\\t",
        "GPRINT:inbits:LAST:Current\\: %8.3lf %sbps\\t",
        "GPRINT:inbits:MAX:Max\\: %8.3lf %sbps\\t",
        "GPRINT:inbits:AVERAGE:Average\\: %8.3lf %sbps\\l",
        "LINE2:outbits#0000FF:  Outgoing traffic\\t",
        "GPRINT:outbits:LAST:Current\\: %8.3lf %sbps\\t",
        "GPRINT:outbits:MAX:Max\\: %8.3lf %sbps\\t",
        "GPRINT:outbits:AVERAGE:Average\\: %8.3lf %sbps\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
}
?>
