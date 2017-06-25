<?php

Class wavedir extends Info {

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
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Th0_B3?template=golfperfreqtemp";
        $client  =& new HTTP_Client();
        if($this->debug) print "Getting $url...\n";
        $client->get($url);
        $arr = $client->currentResponse();
        $html = $arr[body];
        preg_match("/golfrichting 100-200.* (\d+) graden/i", $html, $res);
        $dir = $res[1];
        if( preg_match("/golfrichting 100-200.*onbekend/i", $html, $res) ) $res[1]='U';

        # wave height
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Hm0?template=golfperfreqtemp";
        $client->get($url);
        if($this->debug) print "Getting $url...\n";
        $arr = $client->currentResponse();
        $html = $arr[body];
        ereg("significante golfhoogte op.* ([[:alnum:]]+) cm", $html, $res);
        $hgt = $res[1];

        # wave height
        $res[1] = 'U';
        $url = "http://www.actuelewaterdata.nl/cgi-bin/measurements/$b.Hmax?template=golfperfreqtemp";
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
        "DEF:wdir=$this->rrdFile:dir:AVERAGE",
        "DEF:whgt=$this->rrdFile:hgt:AVERAGE",
        "DEF:wmhgt=$this->rrdFile:mhgt:AVERAGE",
        "CDEF:wd1=wdir,0,GE,wdir,15,LT,*,whgt,UNKN,IF",
        "CDEF:wd2=wdir,15,GE,wdir,30,LT,*,whgt,UNKN,IF",
        "CDEF:wd3=wdir,30,GE,wdir,45,LT,*,whgt,UNKN,IF",
        "CDEF:wd4=wdir,45,GE,wdir,60,LT,*,whgt,UNKN,IF",
        "CDEF:wd5=wdir,60,GE,wdir,75,LT,*,whgt,UNKN,IF",
        "CDEF:wd6=wdir,75,GE,wdir,90,LT,*,whgt,UNKN,IF",
        "CDEF:wd7=wdir,90,GE,wdir,105,LT,*,whgt,UNKN,IF",
        "CDEF:wd8=wdir,105,GE,wdir,120,LT,*,whgt,UNKN,IF",
        "CDEF:wd9=wdir,120,GE,wdir,135,LT,*,whgt,UNKN,IF",
        "CDEF:wd10=wdir,135,GE,wdir,150,LT,*,whgt,UNKN,IF",
        "CDEF:wd11=wdir,150,GE,wdir,165,LT,*,whgt,UNKN,IF",
        "CDEF:wd12=wdir,165,GE,wdir,180,LT,*,whgt,UNKN,IF",
        "CDEF:wd13=wdir,180,GE,wdir,195,LT,*,whgt,UNKN,IF",
        "CDEF:wd14=wdir,195,GE,wdir,210,LT,*,whgt,UNKN,IF",
        "CDEF:wd15=wdir,210,GE,wdir,225,LT,*,whgt,UNKN,IF",
        "CDEF:wd16=wdir,225,GE,wdir,240,LT,*,whgt,UNKN,IF",
        "CDEF:wd17=wdir,240,GE,wdir,255,LT,*,whgt,UNKN,IF",
        "CDEF:wd18=wdir,255,GE,wdir,270,LT,*,whgt,UNKN,IF",
        "CDEF:wd19=wdir,270,GE,wdir,285,LT,*,whgt,UNKN,IF",
        "CDEF:wd20=wdir,285,GE,wdir,300,LT,*,whgt,UNKN,IF",
        "CDEF:wd21=wdir,300,GE,wdir,315,LT,*,whgt,UNKN,IF",
        "CDEF:wd22=wdir,315,GE,wdir,330,LT,*,whgt,UNKN,IF",
        "CDEF:wd23=wdir,330,GE,wdir,345,LT,*,whgt,UNKN,IF",
        "CDEF:wd24=wdir,345,GE,wdir,360,LT,*,whgt,UNKN,IF",
        "COMMENT:NORTH\\t\\t",
        "AREA:wd1#0cc3f1:(0-15)    ",
        "AREA:wd2#0c9ef1:(15-30)   ",
        "AREA:wd3#177bec:(30-45)\\l",
        "COMMENT:NORTH-EAST\\t",
        "AREA:wd4#2757e2:(45-60)   ",
        "AREA:wd5#9c2de1:(60-75)   ",
        "AREA:wd6#ba1fe7:(75-90)  \\l",
        "COMMENT:EAST\\t\\t",
        "AREA:wd7#ef2df6:(90-105)  ",
        "AREA:wd8#ec37a1:(105-120) ",
        "AREA:wd9#e54747:(120-135)\\l",
        "COMMENT:SOUTH-EAST\\t",
        "AREA:wd10#ff5400:(135-150) ",
        "AREA:wd11#e86b20:(150-165) ",
        "AREA:wd12#ff9e06:(165-180) \\l",
        "COMMENT:SOUTH\\t\\t",
        "AREA:wd13#ffc90f:(180-195) ",
        "AREA:wd14#eccc25:(195-210) ",
        "AREA:wd15#e2d727:(210-225) \\l",
        "COMMENT:SOUTH-WEST\\t",
        "AREA:wd16#c6d617:(225-240) ",
        "AREA:wd17#9bd924:(240-255) ",
        "AREA:wd18#77d146:(255-270) \\l",
        "COMMENT:WEST\\t\\t",
        "AREA:wd19#30dd2c:(270-285) ",
        "AREA:wd20#1aea55:(285-300) ",
        "AREA:wd21#12fd86:(300-315) \\l",
        "COMMENT:NORTH-WEST\\t",
        "AREA:wd22#2cffdf:(315-330) ",
        "AREA:wd23#16effd:(330-345) ",
        "AREA:wd24#36d1f8:(345-360) \\l",
        "LINE2:whgt#BB0000:Wave height\\l",
        "GPRINT:whgt:LAST:Current\\: %3.0lf\\t",
        "GPRINT:whgt:MAX:Max\\: %3.0lf\\t",
        "GPRINT:whgt:AVERAGE:Average\\: %3.0lf\\l",
        "LINE2:wmhgt#BBBBBB:Wave max height\\l",
        "GPRINT:wmhgt:LAST:Current\\: %3.0lf\\t",
        "GPRINT:wmhgt:MAX:Max\\: %3.0lf\\t",
        "GPRINT:wmhgt:AVERAGE:Average\\: %3.0lf\\l",
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
