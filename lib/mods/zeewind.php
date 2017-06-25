<?php

Class zeewind extends Info {

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
                "DS:dir:GAUGE:600:U:U",
                "DS:speed:GAUGE:600:U:U",
                "DS:gust:GAUGE:600:U:U"
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
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b-richt?template=windr";
        $client  =& new HTTP_Client();
        if($this->debug) print "Getting $url...\n";
        $client->get($url);
        $arr = $client->currentResponse();
        $html = $arr[body];
        preg_match("/windrichting.* (\d+) graden/i", $html, $res);
        $wdir = $res[1];

        # wave height
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b-snelh-stoot?template=winds";
        $client->get($url);
        if($this->debug) print "Getting $url...\n";
        $arr = $client->currentResponse();
        $html = $arr[body];
        preg_match("/windsnelheid.* (\d+\.*\d*) m\/s.*windstoot.* (\d+\.*\d*) m\/s/i", $html, $res);
        $speed = $res[1];
        $gust  = $res[2];

        $this->outtext = "N:$wdir:$speed:$gust";
        if($this->debug) { print $this->outtext; }
        $ret = rrd_update($this->rrdFile,"N:$wdir:$speed:$gust");
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
        "-v", "Windspeed (m/s)",
        "-s", "$time",
        "DEF:wdir=$this->rrdFile:dir:AVERAGE",
        "DEF:wspd=$this->rrdFile:speed:AVERAGE",
        "DEF:wgst=$this->rrdFile:gust:AVERAGE",
    "CDEF:wd1=wdir,0,GE,wdir,15,LT,*,wspd,UNKN,IF",
    "CDEF:wd2=wdir,15,GE,wdir,30,LT,*,wspd,UNKN,IF",
    "CDEF:wd3=wdir,30,GE,wdir,45,LT,*,wspd,UNKN,IF",
    "CDEF:wd4=wdir,45,GE,wdir,60,LT,*,wspd,UNKN,IF",
    "CDEF:wd5=wdir,60,GE,wdir,75,LT,*,wspd,UNKN,IF",
    "CDEF:wd6=wdir,75,GE,wdir,90,LT,*,wspd,UNKN,IF",
    "CDEF:wd7=wdir,90,GE,wdir,105,LT,*,wspd,UNKN,IF",
    "CDEF:wd8=wdir,105,GE,wdir,120,LT,*,wspd,UNKN,IF",
    "CDEF:wd9=wdir,120,GE,wdir,135,LT,*,wspd,UNKN,IF",
    "CDEF:wd10=wdir,135,GE,wdir,150,LT,*,wspd,UNKN,IF",
    "CDEF:wd11=wdir,150,GE,wdir,165,LT,*,wspd,UNKN,IF",
    "CDEF:wd12=wdir,165,GE,wdir,180,LT,*,wspd,UNKN,IF",
    "CDEF:wd13=wdir,180,GE,wdir,195,LT,*,wspd,UNKN,IF",
    "CDEF:wd14=wdir,195,GE,wdir,210,LT,*,wspd,UNKN,IF",
    "CDEF:wd15=wdir,210,GE,wdir,225,LT,*,wspd,UNKN,IF",
    "CDEF:wd16=wdir,225,GE,wdir,240,LT,*,wspd,UNKN,IF",
    "CDEF:wd17=wdir,240,GE,wdir,255,LT,*,wspd,UNKN,IF",
    "CDEF:wd18=wdir,255,GE,wdir,270,LT,*,wspd,UNKN,IF",
    "CDEF:wd19=wdir,270,GE,wdir,285,LT,*,wspd,UNKN,IF",
    "CDEF:wd20=wdir,285,GE,wdir,300,LT,*,wspd,UNKN,IF",
    "CDEF:wd21=wdir,300,GE,wdir,315,LT,*,wspd,UNKN,IF",
    "CDEF:wd22=wdir,315,GE,wdir,330,LT,*,wspd,UNKN,IF",
    "CDEF:wd23=wdir,330,GE,wdir,345,LT,*,wspd,UNKN,IF",
    "CDEF:wd24=wdir,345,GE,wdir,360,LT,*,wspd,UNKN,IF",
    "GPRINT:wdir:LAST:Wind direction Current\\: %lf  ",
    "GPRINT:wdir:MAX:Max\\: %lf  ",
    "GPRINT:wdir:AVERAGE:Average\\: %lf\\n",
    "COMMENT:NORTH      ",
    "AREA:wd1#0cc3f1:(0-15)   ",
    "AREA:wd2#0c9ef1:(15-30)  ",
    "AREA:wd3#177bec:(30-45)  \\l",
    "COMMENT:NORTH-EAST ",
    "AREA:wd4#2757e2:(45-60)  ",
    "AREA:wd5#9c2de1:(60-75)  ",
    "AREA:wd6#ba1fe7:(75-90)  \\l",
    "COMMENT:EAST       ",
    "AREA:wd7#ef2df6:(90-105) ",
    "AREA:wd8#ec37a1:(105-120)",
    "AREA:wd9#e54747:(120-135)\\l",
    "COMMENT:SOUTH-EAST ",
    "AREA:wd10#ff5400:(135-150)",
    "AREA:wd11#e86b20:(150-165)",
    "AREA:wd12#ff9e06:(165-180)\\l",
    "COMMENT:SOUTH      ",
    "AREA:wd13#ffc90f:(180-195)",
    "AREA:wd14#eccc25:(195-210)",
    "AREA:wd15#e2d727:(210-225)\\l",
    "COMMENT:SOUTH-WEST ",
    "AREA:wd16#c6d617:(225-240)",
    "AREA:wd17#9bd924:(240-255)",
    "AREA:wd18#77d146:(255-270)\\l",
    "COMMENT:WEST       ",
    "AREA:wd19#30dd2c:(270-285)",
    "AREA:wd20#1aea55:(285-300)",
    "AREA:wd21#12fd86:(300-315)\\l",
    "COMMENT:NORTH-WEST ",
    "AREA:wd22#2cffdf:(315-330)",
    "AREA:wd23#16effd:(330-345)",
    "AREA:wd24#36d1f8:(345-360)\\l",
    "LINE2:wspd#BB0000:Wind speed ",
    "GPRINT:wspd:LAST: Current\\: %lf  ",
    "GPRINT:wspd:MAX:Max\\: %lf  ",
    "GPRINT:wspd:AVERAGE:Average\\: %lf\\l",
    "LINE2:wgst#BBBBBB:Wind gust  ",
    "GPRINT:wgst:LAST: Current\\: %lf  ",
    "GPRINT:wgst:MAX:Max\\: %lf  ",
    "GPRINT:wgst:AVERAGE:Average\\: %lf\\l",
    "HRULE:8.0#BBBB00:Fce 8  ",
    "HRULE:24.5#00BBBB:Fce 10  ",
    "HRULE:32.7#FF0808:Fce 12\\l",
        $this->lastUpdate()) 
        );

        $ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
        if (! is_array($ret) && $this->debug ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }

	}
} // End class
?>
