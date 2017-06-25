<?php

 Class lincpu extends Info {

    var $moduleVars;
    var $chart;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'description' => 'req',
            'limit'       => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:user:COUNTER:600:U:U",
            "DS:nice:COUNTER:600:U:U",
            "DS:system:COUNTER:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {

        $user   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.11.50.0");
        $nice   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.11.51.0");
        $system = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.11.52.0");

        $this->outtext = "N:$user:$nice:$system";
        $update = array("N:$user:$nice:$system");
        $ret = rrd_update($this->rrdFile, $update );
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
        "DEF:v1=".$this->rrdFile.":user:AVERAGE",
        "DEF:v2=".$this->rrdFile.":nice:AVERAGE",
        "DEF:v3=".$this->rrdFile.":system:AVERAGE",
        "CDEF:val1=v1,0,200,LIMIT",
        "CDEF:val2=v2,0,200,LIMIT",
        "CDEF:val3=v3,0,200,LIMIT",
        "AREA:val1#00CC00: User\\t\\t",
        "GPRINT:val1:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val1:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.0lf %%\\l",
        "STACK:val2#0000FF: Nice\\t\\t",
        "GPRINT:val2:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val2:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.0lf %%\\l",
        "STACK:val3#FF0000: System\\t",
        "GPRINT:val3:LAST:Current\\: %.0lf %%\\t",
        "GPRINT:val3:MAX:Max\\: %.0lf %%\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.0lf %%\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph );
	}

    function fetchData($start, $end='NOW') {

        $opts = array(
        "-s",$start,
        "-e",$end,
        "DEF:val1=".$this->rrdFile.":user:AVERAGE",
        "DEF:val2=".$this->rrdFile.":nice:AVERAGE",
        "DEF:val3=".$this->rrdFile.":system:AVERAGE",
        "XPORT:val3:system average",
        "XPORT:val2:nice average",
        "XPORT:val1:user average",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        // Try some different colors -- wtf?
        $this->hcOptions->colors = array("'#ff0000'",'#00cc00','#03a1cd');

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "CPU Load";

        $this->hcOptions->series[0]->type='area';
        $this->hcOptions->series[1]->type='area';
        $this->hcOptions->series[2]->type='area';
        $this->hcOptions->plotOptions->stacking='normal';

        $this->prepHcDataOptions($data);

        return $this->hcOptions;
    }

}
?>
