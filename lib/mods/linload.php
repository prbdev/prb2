<?php

 Class linload extends Info {

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
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {

        $l1  = trim(snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.1"), '"');
        $l5  = trim(snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.2"), '"');
        $l15 = trim(snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.10.1.3.3"), '"');

        $this->outtext = "N:$l1:$l5:$l15";
        $update = array("N:$l1:$l5:$l15");
        $ret = rrd_update($this->rrdFile, $update);
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
        "LINE2:val1#00CC00: 1 min",
        "GPRINT:val1:LAST:Current\\: %.2lf\\t",
        "GPRINT:val1:MAX:Max\\: %.2lf\\t",
        "GPRINT:val1:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val2#0000FF: 5 min",
        "GPRINT:val2:LAST:Current\\: %.2lf\\t",
        "GPRINT:val2:MAX:Max\\: %.2lf\\t",
        "GPRINT:val2:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val3#FF0000: 15 min",
        "GPRINT:val3:LAST:Current\\: %.2lf\\t",
        "GPRINT:val3:MAX:Max\\: %.2lf\\t",
        "GPRINT:val3:AVERAGE:Average\\: %.2lf\\l",
        $this->lastUpdate())
        );

	$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph );
         if (! is_array($ret) ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print_r($graph);
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }
    }

    function fetchData($start, $end='NOW') {

        $opts = array(
        "-s",$start,
        "-e",$end,
        "DEF:val1=".$this->rrdFile.":l1:AVERAGE",
        "DEF:val2=".$this->rrdFile.":l5:AVERAGE",
        "DEF:val3=".$this->rrdFile.":l15:AVERAGE",
        "XPORT:val1:1 minute average",
        "XPORT:val2:5 minute average",
        "XPORT:val3:15 minute average",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "Load Average";

        $this->hcOptions->series[0]->type='line';
        $this->hcOptions->series[1]->type='line';
        $this->hcOptions->series[2]->type='line';

        $this->prepHcDataOptions($data);

        return $this->hcOptions;
    }
}
?>
