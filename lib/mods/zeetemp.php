<?php

Class zeetemp extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'name'        => 'req',
            'host'        => 'req',
            'community'   => 'req',
            'description' => 'opt',
            'limit'       => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array (
            "DS:wtemp:GAUGE:600:U:U",
            "DS:atemp:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        require_once 'HTTP/Client.php';
        $b = $this->community;

        # wave direction
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/HOEK.TW10?template=watertemp";
        $client  =& new HTTP_Client();
        if($this->debug) print "Getting $url...\n";
        $client->get($url);
        $arr = $client->currentResponse();
        $html = $arr[body];
        preg_match("/temperatuur.* (\d+\.\d) Gr. Cel/i", $html, $res);
        $wtemp = $res[1];

        # wave height
        $html = '';
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/LEG1-luchttemp?template=lucht";
        $client->get($url);
        if($this->debug) print "Getting $url...\n";
        $arr = $client->currentResponse();
        $html = $arr[body];
        preg_match("/temperatuur.* (-*\d+\.\d) gr. Cel/i", $html, $res);
        $atemp = $res[1];

        $this->outtext = "N:$wtemp:$atemp";
        $ret = rrd_update($this->rrdFile,"N:$wtemp:$atemp");
        if ( $ret == 0 ) {
            $err = rrd_error();
            if( $this->debug ) {echo "ERROR occurred: $err\n";}
        }
        return $ret;
    }


    function Graph($ext, $time, $w=W, $h=H) {

        $graph = array_merge(
        $this->graphDefOpts, array (
        "-w", $w, "-h", $h,
        "-t", $this->description,
        "-v", "Temperature (deg C)",
        "-s", "$time",
        "DEF:wtemp=$this->rrdFile:wtemp:AVERAGE",
        "DEF:atemp=$this->rrdFile:atemp:AVERAGE",
        "LINE2:wtemp#0000BB: Water temperature\\t",
        "GPRINT:wtemp:LAST:Current\\: %2.2lf\\t",
        "GPRINT:wtemp:MAX:Max\\: %2.2lf\\t",
        "GPRINT:wtemp:AVERAGE:Average\\: %2.2lf\\l",
        "LINE2:atemp#00BB00: Air temperature\\t",
        "GPRINT:atemp:LAST:Current\\: %2.2lf\\t",
        "GPRINT:atemp:MAX:Max\\: %2.2lf\\t",
        "GPRINT:atemp:AVERAGE:Average\\: %2.2lf\\l",

        $this->lastUpdate()) 
        );

        $ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
	}
} // End class
?>
