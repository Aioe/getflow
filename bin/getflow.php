#!/usr/bin/php 
<?php

$spooldir  	= "/usr/system/news/getflow/spool/data/";
$htmlheadfile   = "/usr/system/news/getflow/lib/htmlhead.php";

//////////////////////////////////////////////////////////////

include("/usr/system/news/getflow/lib/base.php"); // /usr/share/php/getflow/lib/base.php

/////////////////////////////////////////////////////////////

$options = getopt("m:t:TFd:l:hf:s:r:n:vux:ke:w");

if (isset($options["h"]))
{
	echo "Getflow v. 0.1.1 Copyright 2018 by Paolo Amoroso <usenet@aioe.org>, released under BSD license

Usage: 
	getflow -T -d <dataset> [ -r <epoch> -f <format> -s <search> -t <days> -m <months> -l <number> -x <file> -v -i ]
	getflow -F -d <dataset> -l <number> [ -k -r <epoch> -f <format> -s <search> -t <days> -m <months> -x <file> -v -i ]
	getflow -h

Options:

  	-T	       Print a dataset as a table of data

	-F	       Print flow of an item

	-d <dataset>   Select dataset to print (MANDATORY)
		       <dataset> must be one among:
		       groups         -> articles per group     (crosspost)
		       artgroups      -> articles per group     (no crosspost)
		       hiers          -> articles per hierarchy (crosspost)
		       arthiers       -> articles per hierarchy (no crosspost)
		       origins        -> articles per posting server
		       feeds          -> articles per incoming feed
		       localgroups    -> local articles per group (crosspost)
		       localartgroups -> local articles per group (no crosspost)
		       all	      -> all datasets (only if -T is set)

	-r <epoch>     Select daily or monthly report. <epoch> must be
		       daily     -> daily reports
		       monthly   -> monthly reports (per day)
		       yearly	 -> yearly reports (per month)
	               Default: 'daily'	
			

       -f <format>     Select format of output data
		       <format> must be one among:
		       text        -> print data in text format
		       csv         -> print data in comma separated values (CSV) format
		       html        -> print data in html data
		       Default: 'text'

      -s <search>      Print only items that match /<search>/ (REGEXP)

      -l <number>      if -T is set, print only first <number> items
		       if -F is set, scan only <number> months/days after first date

      -t <day>         Fetch data about <day>
                       <day> can be:
		       * a negative number that represents days ago if -r is set to
			 'daily' or months ago if -r is set to 'monthly'
		       * a positive number that represents the day of the month if
		         -r is set to 'daily' or number of month if -r is set to
                        'monthly' ('1' is January)

      -m <month>       Set month. <month> can be:
		       * a negative number that represents months ago
		       * a positive number that sets number of month ('1' is January)

      -x <file>        Use <file> content as html header if -f html and -u are set 
		       Default: $htmlheadfile

      -v 	       Show ranking	

      -u               if -f is set to 'html', print html page headers

      -k	       if -F is set, this flags excludes current month/day from reports

      -w 	       Do not colorize lines

      -h               This help  
";
	exit(0);	   
}

if (!isset($options["T"]) and !isset($options["F"])) quit(1, "one flag between -T and -F is mandatory");

$daydir = date("Ymd");

if (!isset($options["d"])) quit(2, "tag -d is mandatory");

if (isset($options["d"])) $table = strtolower($options["d"]);
else $table = "all";

if (isset($options["r"]))
{
	$request = $options["r"];
	if ($request == "daily") $spooldir .= "/daily/";
	elseif ($request == "monthly") $spooldir .= "/monthly/";
	elseif ($request == "yearly") $spooldir .= "/yearly/";
	else quit(3, "invalid argument $request for flag -r [daily|monthly|yearly]");
} else $spooldir .= "/daily/";

if (isset($options["m"]))
{
	$current_day = $daydir[6] . $daydir[7];
	$current_month = $daydir[4] . $daydir[5];
	$current_year = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3];

	$requested_month = $options["m"];
	if ($requested_month > 12) quit(4, "-m <month> must be a number between 1 and 12 but is $requested_month");
	else if ($requested_month > 0)
	{
		$current_month = sprintf("%02d", $requested_month); 
	}  else if ($requested_month < 0) 
	{
		$temp_month = $current_month + $requested_month; //requested_month is negative here
		if ($temp_month == 0)
		{
			$current_year--;
			$current_month = 12;
		} else if ($temp_month > 0)
		{
			$current_month = sprintf("%02d", $temp_month);
		} else { 						// if month is in the past
			while($requested_month < 0)
			{
				$current_month--;
				if ($current_month == 0)
				{
					$current_year--;
					$current_month = 12;
				}
				$requested_month++;
			}
				$current_month = sprintf("%02d", $current_month);
		}
	}

	$daydir = $current_year . $current_month . $current_day;
}

if (isset($options["t"]) and ($options["t"] == 0)) quit(1092, "invalid -t argument: " . $options["t"]);

if (isset($options["t"]) and isset($options["r"]))
{
	$reqday = $options["t"];

	if ($options["r"] == "daily")
	{
		if ($reqday < 0) $daydir = change_day($daydir, $reqday); // $reqday is *negative*

		if ($reqday > 0)
		{
			$monthyear = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3] . $daydir[4] . $daydir[5];
			$daydue = sprintf("%02d", $reqday );
			$daydir = "$monthyear$daydue";
		}
		$path = "$spooldir/$daydir";
	} else if ($options["r"] == "monthly") {
		$monthdir = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3] . $daydir[4] . $daydir[5];
		if ($reqday < 0) $daydir = change_day($monthdir, $reqday); // $reqday is *negative*
		if ($reqday > 0)
		{
			$yeardir = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3];
			$monthtwo = sprintf("%02d", $reqday );
			$daydir = "$yeardir$monthtwo";
		}
		$path = "$spooldir/$daydir";
	} elseif ($request == "yearly")  {
		$daydir = date("Y");
		$path = "$spooldir/$daydir";	
	} else quit(1091, "invalid -r argument");
} elseif (isset($options["t"]) and !isset($options["r"]))
{
		$reqday = $options["t"];
                if ($reqday < 0) $daydir = change_day($daydir, $reqday); // $reqday is *negative*
                if ($reqday > 0)
                {
                        $monthyear = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3] . $daydir[4] . $daydir[5];
                        $daydue = sprintf("%02d", $reqday );
                        $daydir = "$monthyear$daydue";
                }
		$path = "$spooldir/$daydir";
} else {
	if ((!isset($options["r"]) or ($options["r"] == "daily"))) 
	{
		$path = "$spooldir/$daydir/";
	}
	elseif ($options["r"] == "monthly")
	{
		$monthdir = $daydir[0] . $daydir[1] . $daydir[2] . $daydir[3] . $daydir[4] . $daydir[5];
		$path = "$spooldir/$monthdir/";
		$daydir = $monthdir;
	}  elseif ($options["r"] == "yearly") {
		$daydir = date("Y");
		$path = "$spooldir/$daydir/";
	}

}

//////////////////////////////////////////////

if (!file_exists($path))  quit(5, "dataset does not exist: $path");

//////////////////////////////////////////////

$spoolfile = "$path/inflow.dat";
$groupfile = "$path/groups.dat";


if (!isset($options["f"]))
{
	$format = "txt";
} else {
	$format = $options["f"];
	if (($format != "txt") and
	    ($format != "csv") and
	    ($format != "html")) quit(12, "-f invalid argument: $format");
        else if ($format == "") quit(13, "-f requires html|csv|txt as argument");
}


if (!isset($options["s"]))
{
        $search = "";
} else {
        $search = $options["s"];
}

if (isset($options["l"]))
{
	$limit = $options["l"];
	if ($limit == 0) $limit = 999999999;
} else {
	$limit = 9999999;
}

if (
	($table != "origins") and
	($table != "feeds")   and
	($table != "hiers")   and
	($table != "groups")  and
	($table != "artgroups") and
	($table != "arthiers") and
	($table != "localgroups") and
	($table != "localartgroups") and
	($table != "all")) quit(6, "invalid dataset: $table");
else if ($table == "") quit(11, "-d requires a dataset as argument");


if (isset($options["v"])) $add_ranking = 1;
else $add_ranking = 0;

if (isset($options["x"])) 
{
	$htmlheadfile = $options["x"];
	if (!file_exists($htmlheadfile)) quit(14, "$htmlheadfile doesn't exists");
}

if (isset($options["w"])) $colorize = 0;
else $colorize = 1;


///////////////////////////////////////////////////////////////////////////
//
// plot_table($spoolfile, $groupfile, $table, $format, $limit, $search, $add_ranking)
// $spoolfile 	-> full path of inflow.dat
// groupfile  	-> full path of groups.dat
// $table     	-> origins|feeds|hiers|groups|localgroups|artgroups|arthiers|localartposts
// $format    	-> txt|csv|html
// $limit     	-> show only first $limit items
// $search    	-> show only data about items that match /$search/
// $add_ranking -> whether to add ranking (0|1)
// $colorize    -> Print rows with colors
//
///////////////////////////////////////////////////////////////////////////


if (isset($options["T"]))
{
	$output = "";

	if ($table != "all") $output = create_daily_table($spoolfile, $groupfile, $table, $format, $limit, $search, $add_ranking, $colorize);
	else {
		$datasets = array("origins", "feeds", "hiers", "arthiers", "groups", "artgroups", "localgroups", "localartgroups" );
		foreach($datasets as $alltable)
		{
			$output .= create_daily_table($spoolfile, $groupfile, $alltable, $format, $limit, $search, $add_ranking, $colorize);
			if (($format == "html") and (isset($options["u"]))) $output .= "<hr />\n";
			else $output .= "\n";
		}
	}

	if (($format == "html") and (isset($options["u"]))) 
	{
		print_html_heads($htmlheadfile);
		echo "$output";
		echo "</body></html>\n";
	} else echo $output;
}


//////////////////////////////////////////////////////////////////////////////


if (isset($options["F"]))
{
	$dirs_to_scan = array();

	if ($table == "all") quit(15, "-d all requires -T");

	if (!isset($options["l"])) quit(12, "with -F option -l <num> is mandatory");
	if (!isset($options["s"])) $search = ".+";
	$items = $options["l"];

	if (
		(!isset($options["t"]) or $options["t"] == -1) and
		(!isset($options["m"]) or $options["m"] == -1) and
		(isset($options["k"]))) $want = 1;
	else $want = 0;
	$results = prepare_flow($table, $search, $spooldir, $daydir, $items, $want);
	$output = plot_flow($results, $format, $add_ranking, $colorize);

        if (($format == "html") and (isset($options["u"]))) 
        {
                if (isset($options["u"])) print_html_heads($htmlheadfile);
		echo plot_table_head($table, $add_ranking);
                echo "$output";
                echo "</table></body></html>\n";
        } else echo $output;
}

?>
