<?php

# Utility function for php 4
if (!function_exists('file_put_contents') && !defined('FILE_APPEND') ) {
 define('FILE_APPEND', 1);
 function file_put_contents($n, $d, $flag = false) {
    $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
    $f = @fopen($n, $mode);
    if ($f === false) {
        return false;
    } else {
        if (is_array($d)) $d = implode($d);
        $bytes_written = fwrite($f, $d);
        fclose($f);
        return $bytes_written;
    }
 }
}

 $sql  = "select * from info where id='$id'";
 $res  = mysql_query($sql)       or $w->errHandler("Error: ".mysql_error()."<BR>", "die");
 $info = mysql_fetch_assoc($res) or $w->errHandler("Error: No rows returned by <div class=code>$sql</div><BR> ".mysql_error()."<BR>", "die");
 $mod  = $info['module'];
 
 if(! is_file($cfg->modPath."/$mod.php") ) {
    $w->errHandler("Error: Can't load $cfg->modPath/$mod.php... It's not a file.<P>", "die");
 }
 require_once $cfg->modPath."/$mod.php";

 $m = new $mod($info);
 $m->rrdChkPath();

 print "Removing spikes from $m->rrdFile<p>";

 $using_cacti = false;

/* setup defaults */
$debug     = FALSE;
$dryrun    = FALSE;
$avgnan    = 'avg';
$rrdfile   = "";
$std_kills = FALSE;
$var_kills = FALSE;
$html      = TRUE;
$backup    = TRUE;

$method   = 1; // Standard Deviation
$numspike = 10;
$stddev   = 10;
$percent  = 500;
$outliers = 5;

 $rrdfile = $m->rrdFile;

 if (!file_exists($rrdfile)) {
    echo "FATAL: File '$rrdfile' does not exist.\n";
    exit(-9);
 }
 
 if (!is_writable($rrdfile)) {
    echo "FATAL: File '$rrdfile' is not writable by this account.\n";
    exit(-8);
 }


/* additional error check */
if ($rrdfile == "") {
	echo "FATAL: You must specify an RRDfile!\n\n";
	display_help();
	exit(-2);
}

/* let's see if we can find rrdtool */
if (!$using_cacti) {
	if (substr_count(PHP_OS, "WIN")) {
		$response = shell_exec("rrdtool.exe");
	}else{
		$response = shell_exec("rrdtool");
	}

	if (strlen($response)) {
		$response_array = explode(" ", $response);
		echo "NOTE: Using " . $response_array[0] . " Version " . $response_array[1] . "\n";
	}else{
		echo "FATAL: RRDTool not found in path.  Please insure RRDTool can be found in your path!\n";
		exit(-1);
	}
}

/* determine the temporary file name */
$seed = mt_rand();
if ($using_cacti) {
	if ($config["cacti_server_os"] == "win32") {
		$tempdir  = getenv("TEMP");
		$xmlfile = $tempdir . "/" . str_replace(".rrd", "", basename($rrdfile)) . ".dump." . $seed;
		$bakfile = $tempdir . "/" . str_replace(".rrd", "", basename($rrdfile)) . ".backup." . $seed . ".rrd";
	}else{
		$tempdir = "/tmp";
		$xmlfile = "/tmp/" . str_replace(".rrd", "", basename($rrdfile)) . ".dump." . $seed;
		$bakfile = "/tmp/" . str_replace(".rrd", "", basename($rrdfile)) . ".backup." . $seed . ".rrd";
	}
}elseif (substr_count(PHP_OS, "WIN")) {	$tempdir  = getenv("TEMP");
	$xmlfile = $tempdir . "/" . str_replace(".rrd", "", basename($rrdfile)) . ".dump." . $seed;
	$bakfile = $tempdir . "/" . str_replace(".rrd", "", basename($rrdfile)) . ".backup." . $seed . ".rrd";
}else{	$tempdir = "/tmp";
	$xmlfile = "/tmp/" . str_replace(".rrd", "", basename($rrdfile)) . ".dump." . $seed;
	$bakfile = "/tmp/" . str_replace(".rrd", "", basename($rrdfile)) . ".backup." . $seed . ".rrd";
}

if ($html) {
	echo "<table cellpadding='3' cellspacing='0' class='spikekill_data' id='spikekill_data'>";
}

if ($using_cacti) {
	cacti_log("NOTE: Removing Spikes for '$rrdfile', Method:'$method'", false, "WEBUI");
}

/* execute the dump command */
echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: Creating XML file '$xmlfile' from '$rrdfile'" . ($html ? "</td></tr>\n":"\n");

if ($using_cacti) {
	shell_exec(read_config_option("path_rrdtool") . " dump $rrdfile > $xmlfile");
}else{
    exec("rrdtool dump $rrdfile", $output);
}

/* backup the rrdfile if requested */
if ($backup && !$dryrun) {
	if (copy($rrdfile, $bakfile)) {
		echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: RRDfile '$rrdfile' backed up to '$bakfile'" . ($html ? "</td></tr>\n":"\n");
	}else{
		echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "FATAL: RRDfile Backup of '$rrdfile' to '$bakfile' FAILED!" . ($html ? "</td></tr>\n":"\n");
		exit(-13);
	}
}

/* process the xml file and remove all comments */
$output = removeComments($output);

/* Read all the rra's ds values and obtain the following pieces of information from each
   rra archive.

   * numsamples - The number of 'valid' non-nan samples
   * sumofsamples - The sum of all 'valid' samples.
   * average - The average of all samples
   * standard_deviation - The standard deviation of all samples
   * max_value - The maximum value of all samples
   * min_value - The minimum value of all samples
   * max_cutoff - Any value above this value will be set to the average.
   * min_cutoff - Any value lower than this value will be set to the average.

   This will end up being a n-dimensional array as follows:
   rra[x][ds#]['totalsamples'];
   rra[x][ds#]['numsamples'];
   rra[x][ds#]['sumofsamples'];
   rra[x][ds#]['average'];
   rra[x][ds#]['stddev'];
   rra[x][ds#]['max_value'];
   rra[x][ds#]['min_value'];
   rra[x][ds#]['max_cutoff'];
   rra[x][ds#]['min_cutoff'];

   There will also be a secondary array created with the actual samples.  This
   array will be used to calculate the standard deviation of the sample set.
   samples[rra_num][ds_num][];

   Also track the min and max value for each ds and store it into the two
   arrays: ds_min[ds#], ds_max[ds#].

   The we don't need to know the type of rra, only it's number for this analysis
   the same applies for the ds' as well.
*/
$rra     = array();
$rra_cf  = array();
$rra_pdp = array();
$rra_num = 0;
$ds_num  = 0;
$total_kills = 0;
$in_rra  = false;
$in_db   = false;
$ds_min  = array();
$ds_max  = array();
$ds_name = array();

/* perform a first pass on the array and do the following:
   1) Get the number of good samples per ds
   2) Get the sum of the samples per ds
   3) Get the max and min values for all samples
   4) Build both the rra and sample arrays
   5) Get each ds' min and max values
*/
if (sizeof($output)) {
foreach($output as $line) {
	if (substr_count($line, "<v>")) {
		$linearray = explode("<v>", $line);
		/* discard the row */
		array_shift($linearray);
		$ds_num = 0;
		foreach($linearray as $dsvalue) {
			/* peel off garbage */
			$dsvalue = trim(str_replace("</row>", "", str_replace("</v>", "", $dsvalue)));
			if (strtolower($dsvalue) != "nan") {
				if (!isset($rra[$rra_num][$ds_num]["numsamples"])) {
					$rra[$rra_num][$ds_num]["numsamples"] = 1;
				}else{
					$rra[$rra_num][$ds_num]["numsamples"]++;
				}

				if (!isset($rra[$rra_num][$ds_num]["sumofsamples"])) {
					$rra[$rra_num][$ds_num]["sumofsamples"] = $dsvalue;
				}else{
					$rra[$rra_num][$ds_num]["sumofsamples"] += $dsvalue;
				}

				if (!isset($rra[$rra_num][$ds_num]["max_value"])) {
					$rra[$rra_num][$ds_num]["max_value"] = $dsvalue;
				}else if ($dsvalue > $rra[$rra_num][$ds_num]["max_value"]) {
					$rra[$rra_num][$ds_num]["max_value"] = $dsvalue;
				}

				if (!isset($rra[$rra_num][$ds_num]["min_value"])) {
					$rra[$rra_num][$ds_num]["min_value"] = $dsvalue;
				}else if ($dsvalue < $rra[$rra_num][$ds_num]["min_value"]) {
					$rra[$rra_num][$ds_num]["min_value"] = $dsvalue;
				}

				/* store the sample for standard deviation calculation */
				$samples[$rra_num][$ds_num][] = $dsvalue;
			}

			if (!isset($rra[$rra_num][$ds_num]["totalsamples"])) {
				$rra[$rra_num][$ds_num]["totalsamples"] = 1;
			}else{
				$rra[$rra_num][$ds_num]["totalsamples"]++;
			}

			$ds_num++;
		}
	} elseif (substr_count($line, "<rra>")) {
		$in_rra = true;
	} elseif (substr_count($line, "<min>")) {
		$ds_min[] = trim(str_replace("<min>", "", str_replace("</min>", "", trim($line))));
	} elseif (substr_count($line, "<max>")) {
		$ds_max[] = trim(str_replace("<max>", "", str_replace("</max>", "", trim($line))));
	} elseif (substr_count($line, "<name>")) {
		$ds_name[] = trim(str_replace("<name>", "", str_replace("</name>", "", trim($line))));
	} elseif (substr_count($line, "<cf>")) {
		$rra_cf[] = trim(str_replace("<cf>", "", str_replace("</cf>", "", trim($line))));
	} elseif (substr_count($line, "<pdp_per_row>")) {
		$rra_pdp[] = trim(str_replace("<pdp_per_row>", "", str_replace("</pdp_per_row>", "", trim($line))));
	} elseif (substr_count($line, "</rra>")) {
		$in_rra = false;
		$rra_num++;
	} elseif (substr_count($line, "<step>")) {
		$step = trim(str_replace("<step>", "", str_replace("</step>", "", trim($line))));
	}
}
}

/* For all the samples determine the average with the outliers removed */
calculateVarianceAverages($rra, $samples);

/* Now scan the rra array and the samples array and calculate the following
   1) The standard deviation of all samples
   2) The average of all samples per ds
   3) The max and min cutoffs of all samples
   4) The number of kills in each ds based upon the thresholds
*/
echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: Searching for Spikes in XML file '$xmlfile'" . ($html ? "</td></tr>\n":"\n");
calculateOverallStatistics($rra, $samples);

/* debugging and/or status report */
if ($debug || $dryrun) {
	outputStatistics($rra);
}

/* create an output array */
if ($method == 1) {
	/* standard deviation subroutine */
	if ($std_kills) {
		if (!$dryrun) {
			$new_output = updateXML($output, $rra);
		}
	}else{
		echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: NO Standard Deviation Spikes found in '$rrdfile'" . ($html ? "</td></tr>\n":"\n");
	}
}else{
	/* variance subroutine */
	if ($var_kills) {
		if (!$dryrun) {
			$new_output = updateXML($output, $rra);
		}
	}else{
		echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: NO Variance Spikes found in '$rrdfile'" . ($html ? "</td></tr>\n":"\n");
	}
}

/* finally update the file XML file and Reprocess the RRDfile */
if (!$dryrun) {
	if ($total_kills) {
		if (writeXMLFile($new_output, $xmlfile)) {
			if (backupRRDFile($rrdfile)) {
				createRRDFileFromXML($xmlfile, $rrdfile);
			}else{
				echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "FATAL: Unable to backup '$rrdfile'" . ($html ? "</td></tr>\n":"\n");
			}
		}else{
			echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "FATAL: Unable to write XML file '$xmlfile'" . ($html ? "</td></tr>\n":"\n");
		}
	}
}else{
	echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: Dryrun requested.  No updates performed" . ($html ? "</td></tr>\n":"\n");
}

if ($html) {
	echo "</table>";
}

/* All Functions */
function createRRDFileFromXML($xmlfile, $rrdfile) {
	global $using_cacti, $html;

	/* execute the dump command */
	echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: Re-Importing '$xmlfile' to '$rrdfile'" . ($html ? "</td></tr>\n":"\n");
	if ($using_cacti) {
		$response = shell_exec(read_config_option("path_rrdtool") . " restore -f -r $xmlfile $rrdfile");
	}else{
		$response = shell_exec("rrdtool restore -f -r $xmlfile $rrdfile");
	}
	if (strlen($response)) echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . $response . ($html ? "</td></tr>\n":"\n");
}

function writeXMLFile($output, $xmlfile) {
	return file_put_contents($xmlfile, $output);
}

function backupRRDFile($rrdfile) {
	global $using_cacti, $tempdir, $seed, $html;

	if ($using_cacti) {
		$backupdir = read_config_option("spikekill_backupdir");

		if ($backupdir == "") {
			$backupdir = $tempdir;
		}
	}else{
		$backupdir = $tempdir;
	}

	if (file_exists($backupdir . "/" . basename($rrdfile))) {
		$newfile = basename($rrdfile) . "." . $seed;
	}else{
		$newfile = basename($rrdfile);
	}

	echo ($html ? "<tr><td colspan='20' class='spikekill_note'>":"") . "NOTE: Backing Up '$rrdfile' to '" . $backupdir . "/" .  $newfile . "'" . ($html ? "</td></tr>\n":"\n");

	return copy($rrdfile, $backupdir . "/" . $newfile);
}

function calculateVarianceAverages(&$rra, &$samples) {
	global $outliers;

	if (sizeof($samples)) {
	foreach($samples as $rra_num => $dses) {
		if (sizeof($dses)) {
		foreach($dses as $ds_num => $ds) {
			if (sizeof($ds) < $outliers * 3) {
				$rra[$rra_num][$ds_num]["variance_avg"] = "NAN";
			}else{
				rsort($ds, SORT_NUMERIC);
				$ds = array_slice($ds, $outliers);

				sort($ds, SORT_NUMERIC);
				$ds = array_slice($ds, $outliers);

				$rra[$rra_num][$ds_num]["variance_avg"] = array_sum($ds) / sizeof($ds);
			}
		}
		}
	}
	}
}

function calculateOverallStatistics(&$rra, &$samples) {
	global $percent, $stddev, $ds_min, $ds_max, $var_kills, $std_kills;

	$rra_num = 0;
	if (sizeof($rra)) {
	foreach($rra as $dses) {
		$ds_num = 0;

		if (sizeof($dses)) {
		foreach($dses as $ds) {
			if (isset($samples[$rra_num][$ds_num])) {
				$rra[$rra_num][$ds_num]["standard_deviation"] = standard_deviation($samples[$rra_num][$ds_num]);
				if ($rra[$rra_num][$ds_num]["standard_deviation"] == "NAN") {
					$rra[$rra_num][$ds_num]["standard_deviation"] = 0;
				}
				$rra[$rra_num][$ds_num]["average"]    = $rra[$rra_num][$ds_num]["sumofsamples"] / $rra[$rra_num][$ds_num]["numsamples"];

				$rra[$rra_num][$ds_num]["min_cutoff"] = $rra[$rra_num][$ds_num]["average"] - ($stddev * $rra[$rra_num][$ds_num]["standard_deviation"]);
				if ($rra[$rra_num][$ds_num]["min_cutoff"] < $ds_min[$ds_num]) {
					$rra[$rra_num][$ds_num]["min_cutoff"] = $ds_min[$ds_num];
				}

				$rra[$rra_num][$ds_num]["max_cutoff"] = $rra[$rra_num][$ds_num]["average"] + ($stddev * $rra[$rra_num][$ds_num]["standard_deviation"]);
				if ($rra[$rra_num][$ds_num]["max_cutoff"] > $ds_max[$ds_num]) {
					$rra[$rra_num][$ds_num]["max_cutoff"] = $ds_max[$ds_num];
				}

				$rra[$rra_num][$ds_num]["numnksamples"] = 0;
				$rra[$rra_num][$ds_num]["sumnksamples"] = 0;
				$rra[$rra_num][$ds_num]["avgnksamples"] = 0;

				/* go through values and find cutoffs */
				$rra[$rra_num][$ds_num]["stddev_killed"]    = 0;
				$rra[$rra_num][$ds_num]["variance_killed"]  = 0;

				if (sizeof($samples[$rra_num][$ds_num])) {
				foreach($samples[$rra_num][$ds_num] as $sample) {
					if (($sample > $rra[$rra_num][$ds_num]["max_cutoff"]) ||
						($sample < $rra[$rra_num][$ds_num]["min_cutoff"])) {
						debug(sprintf("Std Kill: Value '%.4e', StandardDev '%.4e', StdDevLimit '%.4e'", $sample, $rra[$rra_num][$ds_num]["standard_deviation"], ($rra[$rra_num][$ds_num]["max_cutoff"] * (1+$percent))));
						$rra[$rra_num][$ds_num]["stddev_killed"]++;
						$std_kills = true;
					}else{
						$rra[$rra_num][$ds_num]["numnksamples"]++;
						$rra[$rra_num][$ds_num]["sumnksamples"] += $sample;
					}

					if ($rra[$rra_num][$ds_num]["variance_avg"] == "NAN") {
						/* not enought samples to calculate */
					}else if ($sample > ($rra[$rra_num][$ds_num]["variance_avg"] * (1+$percent))) {
						/* kill based upon variance */
						debug(sprintf("Var Kill: Value '%.4e', VarianceDev '%.4e', VarianceLimit '%.4e'", $sample, $rra[$rra_num][$ds_num]["variance_avg"], ($rra[$rra_num][$ds_num]["variance_avg"] * (1+$percent))));
						$rra[$rra_num][$ds_num]["variance_killed"]++;
						$var_kills = true;
					}
				}
				}

				if ($rra[$rra_num][$ds_num]["numnksamples"] > 0) {
					$rra[$rra_num][$ds_num]["avgnksamples"] = $rra[$rra_num][$ds_num]["sumnksamples"] / $rra[$rra_num][$ds_num]["numnksamples"];
				}
			}else{
				$rra[$rra_num][$ds_num]["standard_deviation"] = "N/A";
				$rra[$rra_num][$ds_num]["average"]            = "N/A";
				$rra[$rra_num][$ds_num]["min_cutoff"]         = "N/A";
				$rra[$rra_num][$ds_num]["max_cutoff"]         = "N/A";
				$rra[$rra_num][$ds_num]["numnksamples"]       = "N/A";
				$rra[$rra_num][$ds_num]["sumnksamples"]       = "N/A";
				$rra[$rra_num][$ds_num]["avgnksamples"]       = "N/A";
				$rra[$rra_num][$ds_num]["stddev_killed"]      = "N/A";
				$rra[$rra_num][$ds_num]["variance_killed"]    = "N/A";
				$rra[$rra_num][$ds_num]["stddev_killed"]      = "N/A";
				$rra[$rra_num][$ds_num]["numnksamples"]       = "N/A";
				$rra[$rra_num][$ds_num]["sumnksamples"]       = "N/A";
				$rra[$rra_num][$ds_num]["variance_killed"]    = "N/A";
				$rra[$rra_num][$ds_num]["avgnksamples"]       = "N/A";
			}

			$ds_num++;
		}
		}

		$rra_num++;
	}
	}
}

function outputStatistics($rra) {
	global $rra_cf, $rra_name, $ds_name, $rra_pdp, $html;

	if (sizeof($rra)) {
		if (!$html) {
			echo "\n";
			printf("%10s %16s %10s %7s %7s %10s %10s %10s %10s %10s %10s %10s %10s %10s %10s\n",
				"Size", "DataSource", "CF", "Samples", "NonNan", "Avg", "StdDev",
				"MaxValue", "MinValue", "MaxStdDev", "MinStdDev", "StdKilled", "VarKilled", "StdDevAvg", "VarAvg");
			printf("%10s %16s %10s %7s %7s %10s %10s %10s %10s %10s %10s %10s %10s %10s %10s\n",
				"----------", "---------------", "----------", "-------", "-------", "----------", "----------", "----------",
				"----------", "----------", "----------", "----------", "----------", "----------",
				"----------");
			foreach($rra as $rra_key => $dses) {
				if (sizeof($dses)) {
				foreach($dses as $dskey => $ds) {
					printf("%10s %16s %10s %7s %7s " .
						($ds["average"] < 1E6 ? "%10s ":"%10.4e ") .
						($ds["standard_deviation"] < 1E6 ? "%10s ":"%10.4e ") .
						(isset($ds["max_value"]) ? ($ds["max_value"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") .
						(isset($ds["min_value"]) ? ($ds["min_value"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") .
						(isset($ds["max_cutoff"]) ? ($ds["max_cutoff"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") .
						(isset($ds["min_cutoff"]) ? ($ds["min_cutoff"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") .
						"%10s %10s " .
						(isset($ds["avgnksampled"]) ? ($ds["avgnksamples"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") .
						(isset($ds["variance_avg"]) ? ($ds["variance_avg"] < 1E6 ? "%10s ":"%10.4e ") : "%10s ") . "\n",
						displayTime($rra_pdp[$rra_key]),
						$ds_name[$dskey],
						$rra_cf[$rra_key],
						$ds["totalsamples"],
						(isset($ds["numsamples"]) ? $ds["numsamples"] : "0"),
						($ds["average"] != "N/A" ? round($ds["average"],2) : $ds["average"]),
						($ds["standard_deviation"] != "N/A" ? round($ds["standard_deviation"],2) : $ds["standard_deviation"]),
						(isset($ds["max_value"]) ? round($ds["max_value"],2) : "N/A"),
						(isset($ds["min_value"]) ? round($ds["min_value"],2) : "N/A"),
						($ds["max_cutoff"] != "N/A" ? round($ds["max_cutoff"],2) : $ds["max_cutoff"]),
						($ds["min_cutoff"] != "N/A" ? round($ds["min_cutoff"],2) : $ds["min_cutoff"]),
						$ds["stddev_killed"],
						$ds["variance_killed"],
						($ds["avgnksamples"] != "N/A" ? round($ds["avgnksamples"],2) : $ds["avgnksamples"]),
						(isset($ds["variance_avg"]) ? round($ds["variance_avg"],2) : "N/A"));
				}
				}
			}

			echo "\n";
		}else{
			printf("<tr><th style='width:10%%;'>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>\n",
				"Size", "DataSource", "CF", "Samples", "NonNan", "Avg", "StdDev",
				"MaxValue", "MinValue", "MaxStdDev", "MinStdDev", "StdKilled", "VarKilled", "StdDevAvg", "VarAvg");
			foreach($rra as $rra_key => $dses) {
				if (sizeof($dses)) {
				foreach($dses as $dskey => $ds) {
					printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>" .
						($ds["average"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") .
						($ds["standard_deviation"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") .
						(isset($ds["max_value"]) ? ($ds["max_value"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") : "%s</td><td>") .
						(isset($ds["min_value"]) ? ($ds["min_value"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") : "%s</td><td>") .
						(isset($ds["max_cutoff"]) ? ($ds["max_cutoff"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") : "%s</td><td>") .
						(isset($ds["min_cutoff"]) ? ($ds["min_cutoff"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") : "%s</td><td>") .
						"%s</td><td>%s</td><td>" .
						(isset($ds["avgnksampled"]) ? ($ds["avgnksamples"] < 1E6 ? "%s</td><td>":"%.4e</td><td>") : "%s</td><td>") .
						(isset($ds["variance_avg"]) ? ($ds["variance_avg"] < 1E6 ? "%s</td></tr>\n":"%.4e</td></tr>\n") : "%s</td></tr>\n") . "\n",
						displayTime($rra_pdp[$rra_key]),
						$ds_name[$dskey],
						$rra_cf[$rra_key],
						$ds["totalsamples"],
						(isset($ds["numsamples"]) ? $ds["numsamples"] : "0"),
						($ds["average"] != "N/A" ? round($ds["average"],2) : $ds["average"]),
						($ds["standard_deviation"] != "N/A" ? round($ds["standard_deviation"],2) : $ds["standard_deviation"]),
						(isset($ds["max_value"]) ? round($ds["max_value"],2) : "N/A"),
						(isset($ds["min_value"]) ? round($ds["min_value"],2) : "N/A"),
						($ds["max_cutoff"] != "N/A" ? round($ds["max_cutoff"],2) : $ds["max_cutoff"]),
						($ds["min_cutoff"] != "N/A" ? round($ds["min_cutoff"],2) : $ds["min_cutoff"]),
						$ds["stddev_killed"],
						$ds["variance_killed"],
						($ds["avgnksamples"] != "N/A" ? round($ds["avgnksamples"],2) : $ds["avgnksamples"]),
						(isset($ds["variance_avg"]) ? round($ds["variance_avg"],2) : "N/A"));
				}
				}
			}
		}
	}
}

function updateXML(&$output, &$rra) {
	global $numspike, $percent, $avgnan, $method, $total_kills;

	/* variance subroutine */
	$rra_num = 0;
	$ds_num  = 0;
	$kills   = 0;

	if (sizeof($output)) {
	foreach($output as $line) {
		if (substr_count($line, "<v>")) {
			$linearray = explode("<v>", $line);
			/* discard the row */
			array_shift($linearray);

			/* initialize variables */
			$ds_num  = 0;
			$out_row = "<row>";
			foreach($linearray as $dsvalue) {
				/* peel off garbage */
				$dsvalue = trim(str_replace("</row>", "", str_replace("</v>", "", $dsvalue)));
				if (strtolower($dsvalue) == "nan") {
					/* do nothing, it's a NaN */
				}else{
					if ($method == 2) {
						if ($dsvalue > (1+$percent)*$rra[$rra_num][$ds_num]["variance_avg"]) {
							if ($kills < $numspike) {
								if ($avgnan == "avg") {
									$dsvalue = $rra[$rra_num][$ds_num]["variance_avg"];
								}else{
									$dsvalue = "NaN";
								}
								$kills++;
								$total_kills++;
							}
						}
					}else{
						if (($dsvalue > $rra[$rra_num][$ds_num]["max_cutoff"]) ||
							($dsvalue < $rra[$rra_num][$ds_num]["min_cutoff"])) {
							if ($kills < $numspike) {
								if ($avgnan == "avg") {
									$dsvalue = $rra[$rra_num][$ds_num]["average"];
								}else{
									$dsvalue = "NaN";
								}
								$kills++;
								$total_kills++;
							}
						}
					}
				}

				$out_row .= "<v> " . $dsvalue . "</v>";
				$ds_num++;
			}

			$out_row .= "</row>";

			$new_array[] = $out_row;
		}else{
			if (substr_count($line, "</rra>")) {
				$ds_minmax = array();
				$rra_num++;
				$kills = 0;
			}else if (substr_count($line, "</database>")) {
				$ds_num++;
				$kills = 0;
			}

			$new_array[] = $line;
		}
	}
	}

	return $new_array;
}

function removeComments(&$output) {
	if (sizeof($output)) {
		foreach($output as $line) {
			$line = trim($line);
			if ($line == "") {
				continue;
			}else{
				/* is there a comment, remove it */
				$comment_start = strpos($line, "<!--");
				if ($comment_start === false) {
					/* do nothing no line */
				}else{
					$comment_end = strpos($line, "-->");
					if ($comment_start == 0) {
						$line = trim(substr($line, $comment_end+3));
					}else{
						$line = trim(substr($line,0,$comment_start-1) . substr($line,$comment_end+3));
					}
				}

				if ($line != "") {
					$new_array[] = $line;
				}
			}
		}
		/* transfer the new array back to the original array */
		return $new_array;
	}
}

function displayTime($pdp) {
	global $step;

	$total_time = $pdp * $step; // seconds

	if ($total_time < 60) {
		return $total_time . " secs";
	}else{
		$total_time = $total_time / 60;

		if ($total_time < 60) {
			return $total_time . " mins";
		}else{
			$total_time = $total_time / 60;

			if ($total_time < 24) {
				return $total_time . " hours";
			}else{
				$total_time = $total_time / 24;

				return $total_time . " days";
			}
		}
	}
}

function debug($string) {
	global $debug;

	if ($debug) {
		echo "DEBUG: " . $string . "\n";
	}
}

function standard_deviation($samples) {
	$sample_count = count($samples);

	for ($current_sample = 0; $sample_count > $current_sample; ++$current_sample) {
		$sample_square[$current_sample] = pow($samples[$current_sample], 2);
	}

	return sqrt(array_sum($sample_square) / $sample_count - pow((array_sum($samples) / $sample_count), 2));
}

/* display_help - displays the usage of the function */
function display_help () {
	global $using_cacti;

	if ($using_cacti) {
		$version = spikekill_version();
	}else{
		$version = "v1.1";
	}

	echo "Cacti Spike Remover " . ($using_cacti ? "v" . $version["version"] : $version) . ", Copyright 2009, The Cacti Group, Inc.\n\n";
	echo "Usage:\n";
	echo "removespikes.php -R|--rrdfile=rrdfile [-M|--method=stddev] [-A|--avgnan] [-S|--stddev=N]\n";
	echo "                 [-P|--percent=N] [-N|--number=N] [-D|--dryrun] [-d|--debug]\n";
	echo "                 [--html] [-h|--help|-v|-V|--version]\n\n";

	echo "The RRDfile input parameter is mandatory.  If no other input parameters are specified the defaults\n";
	echo "are taken from the Spikekill Plugin settings.\n\n";

	echo "-M|--method      - The spike removal method to use.  Options are 'stddev'|'variance'\n";
	echo "-A|--avgnan      - The spike replacement method to use.  Options are 'avg'|'nan'\n";
	echo "-S|--stddev      - The number of standard deviations +/- allowed\n";
	echo "-P|--percent     - The sample to sample percentage variation allowed\n";
	echo "-N|--number      - The maximum number of spikes to remove from the RRDfile\n";
	echo "-D|--dryrun      - If specified, the RRDfile will not be changed.  Instead a summary of\n";
	echo "                   changes that would have been performed will be issued.\n";
	echo "--backup         - Backup the original RRDfile to preserve prior values.\n\n";

	echo "The remainder of arguments are informational\n";
	echo "--html           - Format the output for a web browser\n";
	echo "-d|--debug       - Display verbose output during execution\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n";
}

?>
