<?php

 // Change modname to you module name. It must be the same
 // as the filename without the .php extension.
 Class postfixqueue extends Info {

    var $moduleVars;

    function setVars() {
        #
        # setup which vars are used by this module
        #
        $this->moduleVars = array_merge( 
            $this->reqVars,                 // specify which variables are required
            array(                          // and which are optional.
            'ifIndex'     => 'req',
            'description' => 'opt',
            ));
    }

    function CreateDB() {

        $this->createOpts = array_merge(
            array ( 
            "DS:active:GAUGE:600:U:U",
            "DS:incoming:GAUGE:600:U:U",
            "DS:bounce:GAUGE:600:U:U",
            "DS:corrupt:GAUGE:600:U:U",
            "DS:deferred:GAUGE:600:U:U",
            "DS:maildrop:GAUGE:600:U:U"
             ),
            $this->stdrra);

        if( $this->debug ) {print "Creating $this->rrdFile...\n";}
        return rrd_create($this->rrdFile, $this->createOpts, count($this->createOpts));
    }

    function Update() {

        $fp = fsockopen("$this->host", $this->ifIndex, $errno, $errstr, 10);
        if (!$fp) {
            if( $this->debug ) print "Unable to open $this->host:$this>ifIndex...\n";
            return;
        } else {
            fwrite($fp, $this->send);
            $val='';
            while (!feof($fp)) {
                $val.=fgets($fp, 128);
            }
            fclose($fp);
        }
        $val = explode("\n", $val);

        $this->outtext = "N:$val[1]:$val[3]:$val[5]:$val[7]:$val[9]:$val[11]";
        $ret = rrd_update($this->rrdFile,"N:$val[1]:$va[3]:$val[5]:$val[7]:$val[9]:$val[11]");
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
        "-v Bandwidth",                                         
        "-s ".$time,                                            
        "DEF:val1=".$this->rrdFile.":active:AVERAGE",         
        "DEF:val2=".$this->rrdFile.":incoming:AVERAGE",
        "DEF:val3=".$this->rrdFile.":bounce:AVERAGE",        
        "DEF:val4=".$this->rrdFile.":corrupt:AVERAGE",     
        "DEF:val5=".$this->rrdFile.":deferred:AVERAGE",  
        "DEF:val6=".$this->rrdFile.":maildrop:AVERAGE", 
        "LINE2:val1#00CC00: active\\t\\t",
        "GPRINT:val1:LAST:Current\\: %.2lf  ",
        "GPRINT:val1:MAX:Max\\: %.2lf  ",
        "GPRINT:val1:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val2#0000FF: incoming\\t",
        "GPRINT:val2:LAST:Current\\: %.2lf  ",
        "GPRINT:val2:MAX:Max\\: %.2lf  ",
        "GPRINT:val2:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val6#00CCE6: maildrop\\t",
        "GPRINT:val6:LAST:Current\\: %.2lf  ",
        "GPRINT:val6:MAX:Max\\: %.2lf  ",
        "GPRINT:val6:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val5#E6CC00: deferred\\t",
        "GPRINT:val5:LAST:Current\\: %.2lf  ",
        "GPRINT:val5:MAX:Max\\: %.2lf  ",
        "GPRINT:val5:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val4#9D4400: corrupt\\t",
        "GPRINT:val4:LAST:Current\\: %.2lf  ",
        "GPRINT:val4:MAX:Max\\: %.2lf  ",
        "GPRINT:val4:AVERAGE:Average\\: %.2lf\\l",
        "LINE2:val3#FF0000: bounce\\t",
        "GPRINT:val3:LAST:Current\\: %.2lf  ",
        "GPRINT:val3:MAX:Max\\: %.2lf  ",
        "GPRINT:val3:AVERAGE:Average\\: %.2lf\\l",
        $this->lastUpdate())
        );

		$ret =  rrd_graph( $this->pngFilePre."-".$ext.".png", $graph, count($graph) );
        if (! is_array($ret) && $this->debug ) {
            $err = rrd_error();
            print "rrd_graph() ERROR: $err<br>";
            print "<br>".$this->pngFilePre."-".$ext.".png<p>";
        }
	}
}
?>
