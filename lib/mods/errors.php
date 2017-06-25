<?php

 Class errors extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,
            array(
            'ifIndex'     => 'req',
            'description' => 'opt',
            'grouping'    => 'req',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:input:COUNTER:600:U:U",
            "DS:output:COUNTER:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {

        $if  = $this->ifIndex;
        $in  = snmpget($this->host, $this->community, ".1.3.6.1.2.1.2.2.1.14.".$if);
        $out = snmpget($this->host, $this->community, ".1.3.6.1.2.1.2.2.1.20.".$if);

        $this->outtext = "N:$in:$out";
        $ret = rrd_update($this->rrdFile,array("N:$in:$out"));
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
        "-v Errors",
        "-s ".$time,
        "DEF:input=".$this->rrdFile.":input:AVERAGE",
        "DEF:output=".$this->rrdFile.":output:AVERAGE",
        "CDEF:inbits=input,8,*",
        "CDEF:outbits=output,8,*",
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

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph );
	}

    function fetchData($start, $end='NOW') {

        $opts = array(
        "-s",$start,
        "-e",$end,
        "DEF:input=".$this->rrdFile.":input:AVERAGE",
        "DEF:output=".$this->rrdFile.":output:AVERAGE",
        "CDEF:outbits=output,8,*",
        "CDEF:inbits=input,8,*",
        "XPORT:inbits:input",
        "XPORT:outbits:output",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "Bandwidth";

        $this->prepHcDataOptions($data);

        return $this->hcOptions;
    }

}
?>
