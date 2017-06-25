<?php
 // $Id: ping.php,v 1.8 2006/08/25 15:00:58 guizy Exp $

 Class ping extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,                 // specify which variables are required
            array(                          // and which are optional.
            'description' => 'opt',
            'limit'       => 'opt')
            );
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
                "DS:MIN:GAUGE:600:U:U",
                "DS:AVG:GAUGE:600:U:U",
                "DS:MAX:GAUGE:600:U:U",
                "DS:DEV:GAUGE:600:U:U",
                "DS:LOSS:GAUGE:600:U:U",
                "RRA:MIN:0.5:1:600",
                "RRA:MIN:0.5:6:700",
                "RRA:MIN:0.5:24:775",
                "RRA:MIN:0.5:288:797"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {

        $loss = 'U';
        $min  = 'U';
        $avg  = 'U';
        $max  = 'U';
        $dev  = 'U';

        $ping = `ping -nqc20 -W1 -i0.2 $this->host`;
        preg_match("/(\d+)% packet loss/ims", $ping, $res);
        if(isset($res[1])) $loss = $res[1];
        preg_match("/min\/avg\/max\/mdev = (\d+\.\d+)\/(\d+\.\d+)\/(\d+.\d+)\/(\d+\.\d+)/ims", $ping, $res);
        if(isset($res[1])) $min = $res[1];
        if(isset($res[2])) $avg = $res[2];
        if(isset($res[3])) $max = $res[3];
        if(isset($res[4])) $dev = $res[4];

        $this->outtext = "N:$min:$avg:$max:$dev:$loss";
        $ret = rrd_update($this->rrdFile,array("N:$min:$avg:$max:$dev:$loss"));
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
        "-v Ping response time",                                         
        "-s ".$time,                                            
        "DEF:d1=".$this->rrdFile.":DEV:AVERAGE",
        "DEF:v1=".$this->rrdFile.":MIN:AVERAGE",
        "DEF:v2=".$this->rrdFile.":AVG:AVERAGE",
        "DEF:v3=".$this->rrdFile.":MAX:AVERAGE",
        "DEF:loss=".$this->rrdFile.":LOSS:AVERAGE",
        "CDEF:dev=d1,1000,/",
        "CDEF:min=v1,1000,/",
        "CDEF:avg=v2,1000,/",
        "CDEF:max=v3,1000,/",
        "CDEF:mdev=dev,avg,+",
        "CDEF:ddev=dev,-2,*",
        "CDEF:d=min,max,-",
        "CDEF:a1=loss,5,GE,loss,20,LT,*,INF,UNKN,IF",
        "CDEF:a2=loss,20,GE,loss,40,LT,*,INF,UNKN,IF",
        "CDEF:a3=loss,40,GE,loss,60,LT,*,INF,UNKN,IF",
        "CDEF:a4=loss,60,GE,loss,80,LT,*,INF,UNKN,IF",
        "CDEF:a5=loss,80,GE,loss,95,LT,*,INF,UNKN,IF",
        "CDEF:a6=loss,95,GE,loss,100,LT,*,INF,UNKN,IF",
        "CDEF:wipeout=loss,101,GE,INF,UNKN,IF",
        "GPRINT:loss:LAST:Packet Loss\\tCurrent\\: %.1lf %%\\t",
        "GPRINT:loss:MAX:Max\\: %.1lf %%\\t",
        "GPRINT:loss:AVERAGE:Average\\: %.1lf %%\\l",
        "AREA:a1#FF0: (5% < loss < 20%)\\t\\t",
        "AREA:a2#FB0: (20% < loss < 40%)\\t",
        "AREA:a3#F70: (40% < loss < 60%)\\l",
        "AREA:a4#B07: (60% < loss < 80%)\\t",
        "AREA:a5#F0B: (80% < loss < 95%)\\t",
        "AREA:a6#F00: (loss > 95%)\\l",
        "AREA:wipeout#EEE:",
        "LINE1:max#0FF: Maximum\\:\\t",
        "LINE2:max",
        "GPRINT:max:LAST:Current\\: %.3lf %ss\\t",
        "GPRINT:max:MAX:Max\\: %.3lf %ss\\t",
        "GPRINT:max:AVERAGE:Average\\: %.3lf %ss\\l",
        "AREA:d#BBB8::STACK",
        "LINE1:min#00F: Minimum\\:\\t",
        "GPRINT:min:LAST:Current\\: %.3lf %ss\\t",
        "GPRINT:min:MAX:Max\\: %.3lf %ss\\t",
        "GPRINT:min:AVERAGE:Average\\: %.3lf %ss\\l",
        "LINE2:mdev:",
        "AREA:ddev#BBB::STACK",
        "LINE2:avg#0B0: Average\\:\\t",
        "GPRINT:avg:LAST:Current\\: %.3lf %ss\\t",
        "GPRINT:avg:MAX:Max\\: %.3lf %ss\\t",
        "GPRINT:avg:AVERAGE:Average\\: %.3lf %ss\\l",
        $this->lastUpdate())
        );

        $ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph );
        if (! is_array($ret) && $this->debug ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }
    }

    function fetchData($start, $end='NOW') {

        $opts = array(
        "-s",$start,
        "-e",$end,
        "DEF:d1=".$this->rrdFile.":DEV:AVERAGE",
        "DEF:v1=".$this->rrdFile.":MIN:AVERAGE",
        "DEF:v2=".$this->rrdFile.":AVG:AVERAGE",
        "DEF:v3=".$this->rrdFile.":MAX:AVERAGE",
        "DEF:loss=".$this->rrdFile.":LOSS:AVERAGE",
        "CDEF:dev=d1,1000,/",
        "CDEF:min=v1,1000,/",
        "CDEF:avg=v2,1000,/",
        "CDEF:max=v3,1000,/",
        "CDEF:mdev=dev,avg,+",
        "CDEF:ddev=dev,-2,*",
        "CDEF:d=min,max,-",
        "CDEF:a1=loss,5,GE,loss,20,LT,*,INF,UNKN,IF",
        "CDEF:a2=loss,20,GE,loss,40,LT,*,INF,UNKN,IF",
        "CDEF:a3=loss,40,GE,loss,60,LT,*,INF,UNKN,IF",
        "CDEF:a4=loss,60,GE,loss,80,LT,*,INF,UNKN,IF",
        "CDEF:a5=loss,80,GE,loss,95,LT,*,INF,UNKN,IF",
        "CDEF:a6=loss,95,GE,loss,100,LT,*,INF,UNKN,IF",
        "CDEF:wipeout=loss,101,GE,INF,UNKN,IF",
        "XPORT:avg:average",
        "XPORT:mdev:mdev",
        "XPORT:min:min",
        "XPORT:max:max",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "Bandwidth";

        $this->hcOptions->series[0]->type='line';
        $this->hcOptions->series[1]->type='line';
        $this->hcOptions->series[2]->type='line';
        $this->hcOptions->plotOptions->line->stacking=false;

        $this->prepHcDataOptions($data);

        return $this->hcOptions;
    }

}
?>
