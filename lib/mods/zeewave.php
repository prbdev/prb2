<?php

Class zeewave extends Info {

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
            "DS:prd:GAUGE:600:U:U",
            "DS:hgt:GAUGE:600:U:U",
            "DS:mhgt:GAUGE:600:U:U"),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        require_once 'HTTP/Client.php';
        $b = $this->community;

        # wave direction
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Tm02?template=golfperfreqtemp";
        $client  =& new HTTP_Client();
        if($this->debug) print "Getting $url...\n";
        $client->get($url);
        $arr = $client->currentResponse();
        $html = $arr[body];
        #ereg("golfrichting 100-200.* ([[:alnum:]]+) graden", $html, $res);
        preg_match("/golfperiode.* (\d+\.*\d) sec/", $html, $res);
        $prd = $res[1];

        # wave height
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Hm0?template=golfperfreqtemp";
        #$client  =& new HTTP_Client();
        $client->get($url);
        if($this->debug) print "Getting $url...\n";
        $arr = $client->currentResponse();
        $html = $arr[body];
        ereg("significante golfhoogte op.* ([[:alnum:]]+) cm", $html, $res);
        $hgt = $res[1];

        # wave height
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Hmax?template=golfperfreqtemp";
        #$client  =& new HTTP_Client();
        $client->get($url);
        if($this->debug) print "Getting $url...\n";
        $arr = $client->currentResponse();
        $html = $arr[body];
        ereg("maximale golfhoogte op.* ([[:alnum:]]+) cm", $html, $res);
        $mhgt = $res[1];

        $this->outtext = "N:$dir:$hgt:$mhgt";
        if($this->debug) { print $this->outtext; }
        $ret = rrd_update($this->rrdFile,"N:$dir:$hgt:$mhgt");
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
        "-v", "Wave height (cm)",
        "-s", "$time",
        "DEF:wprd=$this->rrdFile:prd:AVERAGE",
        "DEF:whgt=$this->rrdFile:hgt:AVERAGE",
        "DEF:wmhgt=$this->rrdFile:mhgt:AVERAGE",
    "CDEF:wp1=wprd,0,GE,wprd,2,LT,*,whgt,UNKN,IF",
    "CDEF:wp2=wprd,2,GE,wprd,3,LT,*,whgt,UNKN,IF",
    "CDEF:wp3=wprd,3,GE,wprd,4,LT,*,whgt,UNKN,IF",
    "CDEF:wp4=wprd,4,GE,wprd,5,LT,*,whgt,UNKN,IF",
    "CDEF:wp5=wprd,5,GE,wprd,7,LT,*,whgt,UNKN,IF",
    "CDEF:wp6=wprd,6,GE,wprd,8,LT,*,whgt,UNKN,IF",
    "CDEF:wp7=wprd,7,GE,wprd,9,LT,*,whgt,UNKN,IF",
    "CDEF:wp8=wprd,8,GE,wprd,10,LT,*,whgt,UNKN,IF",
    "CDEF:wp9=wprd,9,GE,wprd,11,LT,*,whgt,UNKN,IF",
    "CDEF:wp10=wprd,10,GE,wprd,12,LT,*,whgt,UNKN,IF",
    "CDEF:wp11=wprd,11,GE,wprd,13,LT,*,whgt,UNKN,IF",
    "CDEF:wp12=wprd,12,GE,wprd,14,LT,*,whgt,UNKN,IF",
    "CDEF:wp13=wprd,13,GE,wprd,15,LT,*,whgt,UNKN,IF",
    "CDEF:wp14=wprd,14,GE,whgt,UNKN,IF",
    "GPRINT:wprd:LAST:Wave period Current\\: %lf  ",
    "GPRINT:wprd:MAX:Max\\: %lf  ",
    "GPRINT:wprd:AVERAGE:Average\\: %lf\\n",
    "AREA:wp1#BDEEFD:1  ",
    "AREA:wp2#B0DEF2:2  ",
    "AREA:wp3#A1CCE5:3  ",
    "AREA:wp4#90B8D7:4  ",
    "AREA:wp5#81A6CA:5  ",
    "AREA:wp6#7092BC:6  ",
    "AREA:wp7#607FAF:7  ",
    "AREA:wp8#506BA1:8  ",
    "AREA:wp9#425B95:9  ",
    "AREA:wp10#324787:10 ",
    "AREA:wp11#23367B:11 ",
    "AREA:wp12#12216C:12 ",
    "AREA:wp13#010D5E:13 ",
    "AREA:wp14#000C5D:14 \\l",
    "LINE2:whgt#BB0000:Wave height ",
    "GPRINT:whgt:LAST: Current\\: %lf  ",
    "GPRINT:whgt:MAX:Max\\: %lf  ",
    "GPRINT:whgt:AVERAGE:Average\\: %lf\\l",
    "LINE2:wmhgt#BBBBBB:Wave max height  ",
    "GPRINT:wmhgt:LAST: Current\\: %lf  ",
    "GPRINT:wmhgt:MAX:Max\\: %lf  ",
    "GPRINT:wmhgt:AVERAGE:Average\\: %lf\\l",
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
} // End class
?>
