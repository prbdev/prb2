<?php

 Class availability extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'community'   => 'req',
            'limit'       => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:AV:GAUGE:600:U:U",
            "DS:UT:GAUGE:600:U:U",
            "RRA:LAST:0.5:1:600",
            "RRA:LAST:0.5:6:700",
            "RRA:LAST:0.5:24:775",
            "RRA:LAST:0.5:288:797"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts);
    }

    function Update() {
        global $cfg;

        snmp_set_quick_print(false);
        $uptime  = snmpget($this->host, $this->community, ".1.3.6.1.2.1.1.3.0");
        snmp_set_quick_print(true);
        if( $uptime=='' ) { $uptime='U'; }
        preg_match('/Timeticks: \((.*)\) /', $uptime, $match);
        $uptime = $match[1];

        $ava = ( $uptime > 30000 ) ? 100 : (1-(30000-$uptime)/30000);

        #
        # Setup database connection
        #
        $cnn_id = mysql_connect($cfg->dbHost, $cfg->dbUser, $cfg->dbPass);
        mysql_select_db($cfg->dbName, $cnn_id);
        $sql = "update host set uptime='".($uptime/100)."', lastPoll = '".time()."' where name='$this->host'";
        mysql_query($sql) or print "Error in availability mod: ".mysql_error()."\n";
        
        $this->outtext = "N:$ava:$uptime";
        $update = array("N:$ava:$uptime");
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
        "-v Availability",
        "-s ".$time,
        "DEF:val1=".$this->rrdFile.":AV:AVERAGE",
        "DEF:v5=".$this->rrdFile.":UT:LAST",
        "CDEF:tmp=v5,6000,/",
        "CDEF:down=val1,20,LE,100,UNKN,IF",
        "CDEF:crit=tmp,1440,GT,UNKN,val1,IF",
        "CDEF:warn=tmp,10080,GT,UNKN,val1,IF",
        "CDEF:norm=tmp,40320,GT,UNKN,val1,IF",
        "CDEF:good=tmp,40320,LE,UNKN,val1,IF",
        "AREA:good#CCCCFF: uptime > 30 days ",
        "AREA:norm#CCFFCC: uptime < 30 days ",
        "AREA:warn#FFF5CC: uptime < 7 days ",
        "AREA:crit#FFCCF5: uptime < 1 day\\l",
        "AREA:down#FF0000: system unreachable ",
        "HRULE:95#0000FF: = 95%  \\l",
        "LINE2:val1#00CC00: Availability  ",
        "GPRINT:val1:LAST: Current\\: %.3lf %%  ",
        "GPRINT:val1:MAX: Max\\: %.3lf %%  ",
        "GPRINT:val1:AVERAGE: Average\\: %.3lf %%\\l",
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
        "DEF:val1=".$this->rrdFile.":AV:AVERAGE",
        "DEF:v5=".$this->rrdFile.":UT:LAST",
        "CDEF:tmp=v5,6000,/",
        "CDEF:down=val1,20,LE,100,UNKN,IF",
        "CDEF:crit=tmp,1440,GT,UNKN,val1,IF",
        "CDEF:warn=tmp,10080,GT,UNKN,val1,IF",
        "CDEF:norm=tmp,40320,GT,UNKN,val1,IF",
        "CDEF:good=tmp,40320,LE,UNKN,val1,IF",
        "XPORT:val1:Availability",
        "XPORT:good:up > 30 days",
        "XPORT:norm:up < 30 days",
        "XPORT:warn:up < 7 days",
        "XPORT:crit:up < 1 day",
        "XPORT:down:Unreachable",
        );

        if(! $data = rrd_xport($opts) ) echo rrd_error();

        $this->hcOptions->title->text = $this->description;
        $this->hcOptions->subtitle->text = "Resolution: ".($data['step']/60)."min";
        $this->hcOptions->yAxis->title->text = "Availability";

        $month     = (date("m", $data['start'])-1);
        $UTC_start = date("Y,$month,d,H,i,s", $data['start']);
        $step      = $data['step'];

        $this->hcOptions->series[0]->type='line';
        $this->hcOptions->series[1]->type='area';
        $this->hcOptions->series[1]->type='area';
        $this->hcOptions->series[2]->type='area';
        $this->hcOptions->series[3]->type='area';
        $this->hcOptions->series[4]->type='area';
        $this->hcOptions->series[5]->type='area';

        $this->hcOptions->plotOptions->area->stacking=false;

        $this->prepHcDataOptions($data);

        return $this->hcOptions;
    }

}
?>
