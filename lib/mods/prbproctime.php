<?php

 Class prbproctime extends Info {

    var $moduleVars;
    var $varVal;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,
            array(
            'description' => 'opt'
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:proct:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {

        $proct = $this->varVal;

        $this->outtext = "N:$proct";
        $ret = rrd_update($this->rrdFile,array("N:$proct"));
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
        "-v Time (s)",
        "-s ".$time,
        "DEF:proct=".$this->rrdFile.":proct:AVERAGE",
        "AREA:proct#00CC00: PRB processing time\\t",
        "GPRINT:proct:LAST:Current\\: %8.3lf %ss\\t",
        "GPRINT:proct:MAX:Max\\: %8.3lf %ss\\t",
        "GPRINT:proct:AVERAGE:Average\\: %8.3lf %ss\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph );
        if (! is_array($ret) and $this->debug ) {
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
        "DEF:proct=".$this->rrdFile.":proct:AVERAGE",
        "XPORT:proct:processing time",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "time";

        $this->hcOptions->series[0]->type='area';

        $this->prepHcDataOptions($data);
        return $this->hcOptions;
    }

}
?>
