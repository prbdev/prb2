<?php
 # $Id: ucdmem.php,v 1.1 2006/08/09 06:23:49 guizy Exp $

 Class ucdmem extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge(
            $this->reqVars,
            array(
            'host'        => 'req',
            'community'   => 'req',
            'description' => 'req',
            'limit'       => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
                "DS:real:GAUGE:600:U:U",
                "DS:buffer:GAUGE:600:U:U",
                "DS:cache:GAUGE:600:U:U",
                "DS:swap:GAUGE:600:U:U",
                "DS:free:GAUGE:600:U:U"
            ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $total  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.4.5.0");
        $free   = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.4.6.0");
        $buffer = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.4.14.0");
        $cache  = snmpget($this->host, $this->community, ".1.3.6.1.4.1.2021.4.15.0");
        $swap   = snmpget($this->host, $this->community, ".1.3.6.1.2.1.25.2.3.1.6.102");

        $real = $total - $free - $buffer - $cache;

        $this->outtext = "N:$real:$buffer:$cache:$swap:$free";
        $ret = rrd_update($this->rrdFile, "N:$real:$buffer:$cache:$swap:$free" );
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
        "-v Memory usage",
        "-s ".$time,
        "DEF:def1=".$this->rrdFile.":real:AVERAGE",
        "DEF:def2=".$this->rrdFile.":buffer:AVERAGE",
        "DEF:def3=".$this->rrdFile.":cache:AVERAGE",
        "DEF:def4=".$this->rrdFile.":swap:AVERAGE",
        "DEF:def5=".$this->rrdFile.":free:AVERAGE",
        "CDEF:val1=def1,1024,*",
        "CDEF:val2=def2,1024,*",
        "CDEF:val3=def3,1024,*",
        "CDEF:val4=def4,1024,*",
        "CDEF:val5=def5,1024,*",
        "AREA:val1#00CC00: Real\\t\\t",
        "GPRINT:val1:LAST:Current\\:%6.1lf %sb\\t",
        "GPRINT:val1:MAX:Max\\:%6.1lf %sb\\t",
        "GPRINT:val1:AVERAGE:Average\\:%6.1lf %sb\\l",
        "STACK:val2#0000FF: Buffer \\t",
        "GPRINT:val2:LAST:Current\\:%6.1lf %sb\\t",
        "GPRINT:val2:MAX:Max\\:%6.1lf %sb\\t",
        "GPRINT:val2:AVERAGE:Average\\:%6.1lf %sb\\l",
        "STACK:val3#FF0000: Cache\\t",
        "GPRINT:val3:LAST:Current\\:%6.1lf %sb\\t",
        "GPRINT:val3:MAX:Max\\:%6.1lf %sb\\t",
        "GPRINT:val3:AVERAGE:Average\\:%6.1lf %sb\\l",
        "STACK:val5#AAAAAA: Free \\t\\t",
        "GPRINT:val5:LAST:Current\\:%6.1lf %sb\\t",
        "GPRINT:val5:MAX:Max\\:%6.1lf %sb\\t",
        "GPRINT:val5:AVERAGE:Average\\:%6.1lf %sb\\l",
        "STACK:val4#EEEE00: Swap  \\t",
        "GPRINT:val4:LAST:Current\\:%6.1lf %sb\\t",
        "GPRINT:val4:MAX:Max\\:%6.1lf %sb\\t",
        "GPRINT:val4:AVERAGE:Average\\:%6.1lf %sb\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
         if (! is_array($ret) and $this->debug ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print_r($graph);
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }
	}
}
?>
