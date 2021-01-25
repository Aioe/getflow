<?php
function clear_file($rawfile)
{
        exec("cat /dev/null >$rawfile");
}

function read_getflow_groupfile($file, $hosts)
{
        if (!file_exists($file)) touch($file);
        $lines = file($file);

        foreach($lines as $line)
        {
                $elem = explode("\t", $line);
                $group = $elem[1];
                $arts = $elem[2];
                $size = trim($elem[3]);
                if (preg_match("/^pg/", $line)) $hosts = set_spool($hosts, "groups", 		$group, $arts, $size);
                if (preg_match("/^pl/", $line)) $hosts = set_spool($hosts, "localgroups", 	$group, $arts, $size);
		if (preg_match("/^pz/", $line)) $hosts = set_spool($hosts, "artgroups", 	$group, $arts, $size);
		if (preg_match("/^pc/", $line)) $hosts = set_spool($hosts, "localartgroups",    $group, $arts, $size);
        }

        return $hosts;
}

function read_getflow_spoolfile($spoolfile, $hosts)
{
        if (!file_exists($spoolfile)) touch($spoolfile);
        $lines = file($spoolfile);

        foreach($lines as $line)
        {
                $elem = explode("\t", $line);
                $site = $elem[1];
                $arts = $elem[2];
                $size = trim($elem[3]);

                if (preg_match("/^po/", $line)) $hosts = set_spool($hosts, "origins", 	$site, $arts, $size);
                if (preg_match("/^pf/", $line)) $hosts = set_spool($hosts, "feeds", 	$site, $arts, $size);
                if (preg_match("/^ph/", $line)) $hosts = set_spool($hosts, "hiers", 	$site, $arts, $size);
		if (preg_match("/^pa/", $line)) $hosts = set_spool($hosts, "arthiers",  $site, $arts, $size);
        }

        return $hosts;

}

function write_spool_files($spoolfile, $groupfile, $hosts)
{
        $fh = fopen($spoolfile, "w");
        foreach ($hosts["origins"] as $path => $articles)
        {
                fwrite($fh, "po\t$path\t" . $hosts["origins"][$path]["arts"] . "\t" . $hosts["origins"][$path]["size"] . "\n");
        }
        foreach ($hosts["feeds"] as $path => $articles)
        {
                fwrite($fh, "pf\t$path\t" . $hosts["feeds"][$path]["arts"] . "\t" . $hosts["feeds"][$path]["size"] . "\n");
        }

        foreach ($hosts["hiers"] as $hierarchy => $articles)
        {
                fwrite($fh, "ph\t$hierarchy\t" . $hosts["hiers"][$hierarchy]["arts"] . "\t" . $hosts["hiers"][$hierarchy]["size"] . "\n");
        }

        foreach ($hosts["arthiers"] as $hierarchy => $articles)
        {
                fwrite($fh, "pa\t$hierarchy\t" . $hosts["arthiers"][$hierarchy]["arts"] . "\t" . $hosts["arthiers"][$hierarchy]["size"] . "\n");
        }

        fclose($fh);

        $fg = fopen($groupfile, "w");

        foreach ($hosts["groups"] as $path => $articles)
        {
                fwrite($fg, "pg\t$path\t" . $hosts["groups"][$path]["arts"] . "\t" . $hosts["groups"][$path]["size"] . "\n");
        }

        foreach ($hosts["localgroups"] as $path => $articles)
        {
                fwrite($fg, "pl\t$path\t" . $hosts["localgroups"][$path]["arts"] . "\t" . $hosts["localgroups"][$path]["size"] . "\n");
        }

        foreach ($hosts["artgroups"] as $grp => $articles)
        {
                fwrite($fg, "pz\t$grp\t" . $hosts["artgroups"][$grp]["arts"] . "\t" . $hosts["artgroups"][$grp]["size"] . "\n");
        }

        foreach ($hosts["localartgroups"] as $grp => $articles)
        {
                fwrite($fg, "pc\t$grp\t" . $hosts["localartgroups"][$grp]["arts"] . "\t" . $hosts["localartgroups"][$grp]["size"] . "\n");
        }


        fclose($fg);
}

function read_inn2_spoolfile($rawfile)
{
        if (!file_exists($rawfile)) touch($rawfile);
        $lines = file($rawfile);
        $total_articles = count($lines);
        $hosts = array();
        foreach ($lines as $line)
        {
                $items = split(" ", $line );
                $size = $items[0];
                $pathline = $items[1];
                $elem = split("!", $pathline );
                $path = array_reverse($elem);
                $hops = count($path);
                $feed = trim($items[2]);
                $newsgroups = trim($items[3]);

                if (preg_match("/\,/", $newsgroups))
                {
                        $crosspost =  explode(",", $newsgroups   );
			$subhier =    explode(".", $crosspost[0] );
			$hosts = set_spool($hosts, "artgroups", $crosspost[0], 1, $size );
			$hosts = set_spool($hosts, "arthiers",  $subhier[0],   1, $size );

			$nomorelocal = 0;
	                foreach ($crosspost as $group)
                	{
                        	$subhier = explode(".", $group );
                        	$current_hierarchy = $subhier[0];

                        	$hosts = set_spool($hosts, "groups", $group,             1, $size);
                       	 	$hosts = set_spool($hosts, "hiers",  $current_hierarchy, 1, $size);

                        	if ($feed == "localhost")
                        	{
                                	$hosts = set_spool($hosts, "localgroups", $group, 1, $size);
					if ($nomorelocal == 0)
					{
						$hosts = set_spool($hosts, "localartgroups", $group, 1, $size);
						$nomorelocal++;
					} 
                        	}
                	}
                } else {
                        $subhier = explode(".", $newsgroups );
                        $current_hierarchy = $subhier[0];
                        $hosts = set_spool($hosts, "artgroups", $newsgroups, 	      1, $size );
                        $hosts = set_spool($hosts, "arthiers",  $current_hierarchy,   1, $size );

                        $hosts = set_spool($hosts, "groups", $newsgroups,        1, $size);
			$hosts = set_spool($hosts, "hiers",  $current_hierarchy, 1, $size);

			if ($feed == "localhost")
                        {
                                        $hosts = set_spool($hosts, "localgroups",    $newsgroups, 1, $size);
					$hosts = set_spool($hosts, "localartgroups", $newsgroups, 1, $size);  
                        }
		}
                $items = explode(".", $feed );
                if (count($items) > 1)
                {
                        $items = array_reverse($items);
                        $feed = "$items[1].$items[0]";
                }

                for ($ii = 0; $ii < $hops; $ii++)
                {
                        if (preg_match("/not-for-mail/", $path[$ii])) continue;
                        if (preg_match("/\.POSTED|\.MISMATCH/", $path[$ii])) continue;
                        if (!preg_match("/\...|%/", $path[$ii])) continue;
                        if (preg_match("/\.iad|\.invalid/", $path[$ii])) continue;
                        if (preg_match("/\..+[0-9]/", $path[$ii])) continue;
                        if (preg_match("/%/", $path[$ii])) continue;
                        $origin = $path[$ii];
                        break;
      		}

                $subdomains = explode(".", $origin);
                $pathstamp = "";

                if (count($subdomains) == 1) $pathstamp = $origin;
                if (count($subdomains) > 1)
                {
                        $reverse = array_reverse($subdomains);
                        $pathstamp = trim($reverse[1]) . "." . trim($reverse[0]);
                        if (($pathstamp == "co.uk") or ($pathstamp == "org.uk") or ($pathstamp == "mi.it")) $pathstamp = "$reverse[2].$pathstamp";
                }

                $hosts = set_spool($hosts, "origins", $pathstamp, 1, $size);
                $hosts = set_spool($hosts, "feeds",   $feed,      1, $size); 
        }
        return $hosts;
}

function set_spool($hosts, $tag, $entry, $arts, $size )
{
        if (isset($hosts[$tag][$entry])) 
        {
                $hosts[$tag][$entry]["size"] += $size;
                $hosts[$tag][$entry]["arts"] += $arts;
        } else {
                $hosts[$tag][$entry]["size"] = $size;
                $hosts[$tag][$entry]["arts"] = $arts;
        }

        return $hosts;
}

function change_day($date, $shift) // shift is negative
{
	if ($shift == 0) return $date;

	if (strlen($date) == 4) return $date - $shift;

        $year   = $date[0] . $date[1] . $date[2] . $date[3];
        $month  = $date[4] . $date[5];

        $days_months = array( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

	$year = $year + 0;
	if (checkdate(2, 29, $year)) $days_months[2] = 29;

        if (strlen($date) == 8) 
        {
                $day = $date[6] . $date[7];
                $newday = $day + $shift;

		if ($newday == 0) {
                        $month--;
                        if ($month == 0)
                        {
                                $month = 12;
                                $year--;
                                if (checkdate(2, 29, $year)) $days_months[2] = 29;
                        }
                        $day = $days_months[$month];
			$month = sprintf("%02d", $month );
		}
                else if ($newday < 0) 
                {
                        $scartamento = ($newday * -1);
			if ($scartamento > $days_months[$month])
			{
                        	for ($scartamento; $scartamento > $days_months[$month]; $scartamento = $scartamento - $days_months[$month])
                        	{	
					$month--;
                                	if ($month == 0) 
                                	{
                                        	$month = 12;
                                        	$year--;
						if (checkdate(2, 29, $year)) $days_months[2] = 29;
                                	}
				}
                        } else {
				for ($scartamento; $scartamento > 0; $scartamento--)
				{
					$day--;
					if ($day == 0)

					{
						$month--;
						if ($month == 0) 
						{
                                                	$month = 12;
                                                	$year--;
                                                	if (checkdate(2, 29, $year)) $days_months[2] = 29;
							$day = 31;
						}	
						$day = $days_months[$month];
					}
				}
			}
			$day 	= sprintf("%02d", $day);
			$month  = sprintf("%02d", $month);
                } else {
                	$day    = sprintf("%02d", $newday);
                	$month  = sprintf("%02d", $month);
		}

                $output = $year . $month . $day;

        } elseif (strlen($date) == 6) {
                $newmonth = $month + $shift; // $shift is negative
                if ($newmonth < 1)
                {
                        $scartamento = $newmonth * -1;
                        for ($scartamento; $scartamento >= 0; $scartamento--)
                        {
                                $month--;
                                if ($month == 0)
                                {
                                        $month = 12;
                                        $year--;
                                }
                        }
                } elseif ($newmonth == 0) {
                        $year--;
                        $month = 12;
                } else {
			$month = $newmonth;
		}
                $month  = sprintf("%02d", $month );
                $output = $year . $month;
        } else 	quit( 1090, "Invalid strlen in change_day(): ". strlen($date) . " $date");

        return $output;
}

function plot_table_head($table, $add_rank)
{
        $item = array ("groups"         => "Group",
                       "hiers"          => "Hierarchy",
                       "localgroups"    => "Group",
                       "feeds"          => "USENET peer",
                       "origins"        => "USENET site",
                       "arthiers"       => "Hierarchy",
                       "artgroups"      => "Group",
                       "localartgroups" => "Group" );

        $descriptions = array ("groups" => "Articles per group",
                       "hiers"          => "Articles per hierarchy",
                       "localgroups"    => "Local articles per group",
                       "feeds"          => "Articles per USENET peer",
                       "origins"        => "Articles per USENET posting site",
                       "arthiers"       => "Articles per hierarchy (no crosspost)",
                       "artgroups"      => "Articles per group (no crosspost)",
                       "localartgroups" => "Local articles per group (no crosspost)" );

        if ($add_rank == 1) $fields = 7;
        else $fields = 6;

        $output = "
<table class=\"$table\">
<tr class=\"head\"><th colspan=\"$fields\">" . $descriptions[$table] . "</th></tr>
<tr class=\"head\">";

        if ($add_rank == 1) $output .= "<th>Ranking</th>";
        $output .= "
<th>$item[$table]</th>
<th>Number</th>
<th>Perc.</th>
<th>Size</th>
<th>Perc.</th>
<th>Avrg size</th></tr>\n";

        return $output;
}

function print_html_heads($file)
{
        include($file);
}

function add_text_line($field, $lenght, $unit)
{
        $text = "";
        if (($unit == " ") and ($field == 0)) $field = "0.00";
        $lun = strlen($field);
        $toadd = $lenght - $lun;
        for ($toadd; $toadd > 0; $toadd--) 
        {
                $text .= " ";
        }
        $text .= " $field $unit";
        return $text;
}

function create_txt_line($rank, $item, $arts, $perc_arts, $size, $perc_size, $artsize, $color )
{
	$text = $color;
        if ($rank == 0) $text .= "$item     ";
        else {
                $lung = strlen($rank);
                $text .= $rank;
                $toadd = 5 - $lung;
                for ($toadd; $toadd > 0; $toadd--) $text .= " ";
                $text .= $item;
        }

        for ($n = strlen($item); $n < 40; $n++) $text .= " ";

        $text .= add_text_line($arts,           9,   "");       
        $text .= add_text_line($perc_arts,      9,  "%");
        $text .= add_text_line($size,           9, "MB");
        $text .= add_text_line($perc_size,      9,  "%");
        $text .= add_text_line($artsize,        9,  " ");

	if (strlen($color) > 0) $text .= "\e[0m\n";
	else $text .= "\n";

        return $text;
}

function create_daily_table($spoolfile, $groupfile, $table, $format, $limit, $search, $add_ranking, $colorize)
{
	$output = "";
	if ($search == "") $search = ".+";
	$hosts = array();
	if (($table == "hiers") or ($table == "feeds") or ($table == "origins") or ($table == "arthiers")) $hosts = read_getflow_spoolfile($spoolfile, $hosts);
	if (($table == "groups") or ($table == "localgroups") or ($table == "artgroups") or ($table == "localartgroups")) $hosts = read_getflow_groupfile($groupfile, $hosts);

	$totalarts = 0;
	$totalsize = 0;
	$format    = strtolower($format);

	foreach ($hosts[$table] as $site => $value)
	{
		$artspersite[$site] = $hosts[$table][$site]["arts"];
		$volupersite[$site] = $hosts[$table][$site]["size"];
		$totalarts += $artspersite[$site];
		$totalsize += $volupersite[$site];
	}

	arsort($artspersite);
	$total_volume  		= sprintf("%02.02f", $totalsize / (1024*1024));
	$total_artsize 		= sprintf("%02.02f", $totalsize / $totalarts);

	if ($format == "txt")
	{
		if ($colorize) $output .= create_txt_line(0, "Total", $totalarts, "100.00", $total_volume, "100.00", $total_artsize, "\e[38;5;178m");
		else $output .= create_txt_line(0, "Total", $totalarts, "100.00", $total_volume, "100.00", $total_artsize, "");
	} elseif ($format == "csv") {
		$output .= "0;Total;$totalarts;100.00;$total_volume;100.00;$total_artsize\n";
	} elseif ($format == "html") {
		$output .= plot_table_head($table, $add_ranking);
		$output .= create_html_line(0, "Total", $totalarts, "100.00", $total_volume, "100.00", $total_artsize, "TOTALS", $add_ranking);
	}

	$shownarts = 0;
	$shownsize = 0;

	$pari = 0;
	$results = 0;
	foreach ($artspersite as $site => $arts)
	{
		$perc_articles  = sprintf("%02.02f", ($arts/$totalarts)*100);
		$perc_size      = sprintf("%02.02f", ($volupersite[$site]/$totalsize)*100);
		$volume		= sprintf("%02.02f", $volupersite[$site] / (1024*1024)); 
		$artsize	= sprintf("%02.02f", $volupersite[$site] / $arts);
		if (
			(preg_match("/$search/", $site )) and
			($results < $limit )
		) 
		{
			$color = "";
                	if ($pari == 1)
                	{
				$style = "PARI";
                        	$pari = 0;
				if ($colorize) $color = "\e[38;5;119m";
                	} else {
                        	$style = "DISPARI";
                        	$pari = 1;
				if ($colorize) $color = "\e[38;5;143m";
                	}

			$results++;
			if ($format == "txt")
        		{
				if ($add_ranking == 1) 
				{
					$rankstring = $results;
					if ($results < 100) $rankstring = " $results";
					if ($results < 10 ) $rankstring = "  $results";
					$output .= create_txt_line($rankstring, $site, $arts, $perc_articles, $volume, $perc_size, $artsize, $color);
				}
				else $output .= create_txt_line(0, $site, $arts, $perc_articles, $volume, $perc_size, $artsize, $color);
			} elseif ($format == "csv") {
				if ($add_ranking == 1) $output .= "$results;";
				$output .= "$site;$arts;$perc_articles;$volume;$perc_size;$artsize\n";
        		} elseif ($format == "html") {
				if ($add_ranking == 0) $output .= create_html_line(0, $site, $arts, $perc_articles, $volume, $perc_size, $artsize, $style, $add_ranking);
				else $output .= create_html_line($results, $site, $arts, $perc_articles, $volume, $perc_size, $artsize, $style, $add_ranking);
			}
			$shownarts += $arts;
			$shownsize += $volupersite[$site];
		}
	}

	$hidearts 		= $totalarts - $shownarts;
	$hidesize 		= ($totalsize - $shownsize);
	$hidesize_short         = sprintf("%02.02f", $hidesize/(1024*1024));
	$perc_hide_articles  	= sprintf("%02.02f", ($hidearts/$totalarts)*100);
	$perc_hide_size      	= sprintf("%02.02f", ($hidesize/$totalsize)*100);
	if ($hidesize > 0) 
	{
		$hideartsize = sprintf("%02.02f", ($hidesize/$hidearts));
	} else {
		$hideartsize = 0;
	}

	if ($format == "txt")
        {
		if ($colorize) $output .= create_txt_line(0, "Other sites", $hidearts, $perc_hide_articles, $hidesize_short, $perc_hide_size, $hideartsize, "\e[38;5;178m");
		else create_txt_line(0, "Other sites", $hidearts, $perc_hide_articles, $hidesize_short, $perc_hide_size, $hideartsize, "");
	} elseif ($format == "csv") {
		$results++;
		$output .= "$results;Other sites;$hidearts;$perc_hide_articles;$hidesize_short;$perc_hide_size;$hideartsize";
	}  elseif ($format == "html") {
		$output .= create_html_line(0, "Other sites", $hidearts, $perc_hide_articles, $hidesize_short, $perc_hide_size, $hideartsize, "TOTALS", $add_ranking);
	}

	if ($format == "html") $output .= "</table>\n";

	return $output;
}

function create_html_line($rank, $item, $arts, $perc_arts, $size, $perc_size, $artsize, $line, $add_ranking)
{

	$output = "<tr class=\"$line\">\n";

	if ($rank != 0) $output .= "<th>$rank</th>\n";
	
	$output .= "<th";

	if (($rank == 0) and ($add_ranking == 1)) $output .= " colspan=\"2\"";

	$output .= ">$item</th>";
	$output .= "
	<td>$arts</td>
	<td>$perc_arts %</td>
	<td>$size MB</td>
	<td>$perc_size %</td>
	<td>$artsize</td>
</tr>\n";

	return $output;
}

function quit($error, $string)
{
	echo "\e[38;5;202m";
	echo "Error $error: $string ";
	echo "\e[0m";
	echo "Use -h for help\n";
	exit(5);

}

function get_usenet_flow($results, $file, $table, $datastring, $search)
{
        $marks = array(
                        "hiers"          => "ph",
                        "feeds"          => "pf",
                        "origins"        => "po",
                        "arthiers"       => "pa",
                        "groups"         => "pg",
                        "localgroups"    => "pl",
                        "artgroups"      => "pz",
                        "localartgroups" => "pc" );
                         
        $lines = file($file);
	$search_regexp =  "/^" . $marks[$table] . "/";

        foreach($lines as $line)
        {
                if (!preg_match($search_regexp, $line)) continue;
                $elems = explode("\t", $line);
                $item = $elems[1];

                if (preg_match("/$search/i", $item))
                {
                        $arts = $elems[2];
                        $size = trim($elems[3]);

                        if (isset($results[$datastring]))
                        {
                                $results[$datastring]["arts"] += $arts;
                                $results[$datastring]["size"] += $size;
                        } else {
                                $results[$datastring]["arts"] = $arts;
                                $results[$datastring]["size"] = $size;
                        }
                }
        }
        return $results;
}

function prepare_flow($table, $search, $spooldir, $daydir, $items, $want_current)
{
        $current = $daydir;
	$dirs_to_scan = array();
        for ($num = 0; $num < $items; $num++ )
        {
                if ($num > 0) 
		{
			$current = change_day($current, -1);
		}
                else {
                        if ($want_current) $current = change_day($current, -1);   // include/exclude current day/month from report
                }
                $path = $spooldir . "/" . $current . "/";
                if (file_exists($path)) $dirs_to_scan[] = $path;        
        }
        $results = array();

        if (count($dirs_to_scan) == 0) quit(8, "no database matches the required time interval");

        foreach($dirs_to_scan as $database)
        {
                if (($table == "groups") or ($table == "artgroups") or ($table == "localgroups") or ($table == "localartgroups")) $filetoscan = $database . "/groups.dat";
                else if (($table == "feeds") or ($table == "origins") or ($table == "arthiers") or ($table == "hiers")) $filetoscan = $database . "inflow.dat";

                $elem = explode("/", $database);
                $elnum = count($elem) -2;       
                $datastring = $elem[$elnum];
                $datastring = $elem[$elnum];
                if (!file_exists($filetoscan)) quit(9, "missing database file $filetoscan");
                $results = get_usenet_flow($results, $filetoscan, $table, $datastring, $search);
        }
        return $results;
}

function plot_flow($results, $format, $add_ranking, $colorize)
{
	$prog = 0;
	$totalarts = 0;
	$totalsize = 0;
	$text = "";

	foreach($results as $dataset => $array)
	{
		$totalarts += $results[$dataset]["arts"];
		$totalsize += $results[$dataset]["size"];
	}

	if ($totalsize != 0)
	{
		$total_human_size   = sprintf("%02.02f", ($totalsize / (1024*1024)));
        	$total_artsize = sprintf("%02.02f", $totalsize/$totalarts);
	} else {
		$total_human_size = "0.00";
		$total_artsize  = "0.00";
	}

	if ($format == "text")
	{
		if ($colorize) $text .= create_txt_line(0, "Total", $totalarts, "100.00", $total_human_size, "100.00", $total_artsize, "\e[38;5;178m");
		else $text .= create_txt_line(0, "Total", $totalarts, "100.00", $total_human_size, "100.00", $total_artsize, "");
	} elseif ($format == "html")
	{
		$text .=  create_html_line(0, "Total", $totalarts, "100.00", $total_human_size, "100.00", $total_artsize, "TOTALS", $add_ranking);
	}

	$pari = 0;

	foreach($results as $dataset => $array)
	{
		$prog++;
		if ($add_ranking) $rank = $prog;
		else $rank = 0;
		$item = $dataset;
		$arts = $results[$dataset]["arts"];
		$size = $results[$dataset]["size"];
		$perc_arts    = sprintf("%02.02f", ($arts/$totalarts)*100);
		$perc_size    = sprintf("%02.02f", ($size/$totalsize)*100);
		$human_size   = sprintf("%02.02f", ($size / (1024*1024)));
		$artsize = sprintf("%02.02f", $size/$arts);

		$months = array('', 'January','February','March','April','May','June','July','August','September','October','November','December');
		$year = substr($dataset, 0, 4);
		$month = substr($dataset, 4, 2);
		$month = $month + 0;

                if (strlen($dataset) == 6) $month_string = $months[$month] . " " . $year;
		else {
			$day = substr($dataset, 6, 2);
			$month_string = "$day $months[$month] $year";
		}

		$color = "";

	        if ($pari == 1)
                {
        	        $style = "PARI";
                        $pari = 0;
                        if ($colorize) $color = "\e[38;5;119m";
                } else {
                	$style = "DISPARI";
                        $pari = 1;
                        if ($colorize) $color = "\e[38;5;143m";
                }

		if ($format == "txt") $text .= create_txt_line($rank, $month_string, $arts, $perc_arts, $human_size, $perc_size, $artsize, $color);
		elseif ($format == "html") $text .= create_html_line($rank, $month_string, $arts, $perc_arts, $human_size, $perc_size, $artsize, $style, $add_ranking);
		elseif ($format == "csv") 
		{
			if ($add_ranking) $text .= "$rank;";
			$text .= "$item;$arts;$perc_arts;$size;$perc_size;$artsize\n";
		}
	}

	return $text;
}
?>
