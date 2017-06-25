<?php
// $Id: Info.php,v 1.6 2006/07/13 22:04:43 guizy Exp $
//

Class Info {

    var $reqVars;
    var $moduleVars;
    var $name;
    var $hcOptions;

    function Info($info) {

        global $cfg, $debug;

        if( $debug ) {$this->debug = $debug;}

        #
        # default variables needed for all modules
        #
        $this->reqVars = array(
            'host'        => 'req',
            'module'      => 'req',
            'description' => 'req',
	        'grouping'    => 'req'
            );

        $this->moduleVars = array();
        if($info == NULL) { return; }

        #
        # setup the vars from the database fields
        #
        foreach($info as $key => $value)
        {
            $this->$key = $value;
        }

        #
        # set the snmp print format 
        #
        snmp_set_quick_print(TRUE);

        #
        # the user and group for files
        #
        $this->user  = $cfg->user;
        $this->group = $cfg->group;

        #
        # setup the filenames
        #
        if( $this->ifIndex == '' ) $this->ifIndex = 0;
        $idx = '_'.$this->ifIndex;
        $this->workingDir = $this->host;
        $this->rrdPath    = $cfg->rrdPath."/".$this->workingDir;
        $this->pngPath    = $cfg->pngPath."/".$this->workingDir;
        $this->baseFile   = $this->host.'_'.$this->module.'_'.$this->ifIndex;
        $this->rrdFile    = $this->rrdPath.'/'.$this->baseFile.'.rrd';
        $this->pngFilePre = $this->pngPath.'/'.$this->baseFile;
        $this->pngURL     = $cfg->pngUrlPath."/".$this->workingDir.'/'.$this->baseFile;

        #
        # default RRA for the RRD databases
        #
        $this->stdrra  = array(
                'RRA:AVERAGE:0.5:1:600',
                'RRA:AVERAGE:0.5:6:700',
                'RRA:AVERAGE:0.5:24:775',
                'RRA:AVERAGE:0.5:288:797',
                'RRA:MAX:0.5:1:600',
                'RRA:MAX:0.5:6:700',
                'RRA:MAX:0.5:24:775',
                'RRA:MAX:0.5:288:797');

        #
        # default graph options
        #
        $this->graphDefOpts = array("-a", "PNG", 
                "-l", "0",
                "-n", "DEFAULT:0:".$cfg->rrdFont,
                "-R", "normal",
                "-z", 
                "-E"
                );
	    #
	    # default module name
	    #
	    $this->name = $this->baseFile;

        return;
    }

    function prepHcOptions() {
        global $cfg;
        #
        # default Highcharts options
        #
        include "$cfg->hcOptionsFile";
        $this->hcOptions = $hcOptions;

        return;
    }

    function rrdChkPath() {
        if( ! is_dir($this->rrdPath)) {
            # create dir
            if( $this->debug) {
                print "Create dir: ".$this->rrdPath."\n";
            }
            mkdir( $this->rrdPath, 0775 );
            if(! chown( $this->rrdPath, $this->user )) print "couldn't chown $this->rrdPath to $this->user\n";
            //if(! chgrp( $this->rrdPath, $this->group )) { print "couldn't chgrp $this->rrdPath to $this->group\n";}
        }
        if( ! is_file($this->rrdFile) ) {
            $ret = $this->CreateDB();
            if ( $ret == 0 ) {
                $err = rrd_error();
                if( $this->debug ) {print "Create error: $err\n";}
                exit(-1);
            }
            if(! chown( $this->rrdFile, $this->user )) { print "couldn't chown $this->rrdFile to $this->user\n";}
            //if(! chgrp( $this->rrdFile, $this->group )) { print "couldn't chgrp $this->rrdFile to $this->group\n";}
        } 
    }

    function pngChkPath() {
        if( ! is_dir($this->pngPath)) {
            # create dir
            if( $this->debug) {
                print "Create dir: ".$this->pngPath."\n";
            }
            mkdir( $this->pngPath, 0755 );
        }
    }

    function fetchData($start, $end='NOW') {
        return "fetchData() not defined for ".$this->module.".php";
    }

    function prepHcDataOptions($data) {
        $month     = (date("m", $data['start'])-1);
        $UTC_start = date("Y,$month,d,H,i,s", $data['start']);
        $step      = $data['step'];

        for($i=0; $i<count($data['data']); $i++ ) {
            $this->hcOptions->series[$i]->pointInterval = $step*1000;
            $this->hcOptions->series[$i]->pointStart    = "Date.UTC($UTC_start)";
            $this->hcOptions->series[$i]->name          = $data['data'][$i]['legend'];
            $this->hcOptions->series[$i]->data          = json_encode(array_values($data['data'][$i]['data']));
        }
    }

    function lastUpdate() {
        return ( "COMMENT:Last Updated\\: ".strftime("%d-%m-%G %H\\:%M",time())."\\l" );
    }

    function errHandler($msg, $die) {
        $this->errorMsg = $msg;
        if( $die == 'die' ) {
            print " <div class=fatalError>$msg</div>\n";
            exit;
        }
    }

} //End Class
?>
