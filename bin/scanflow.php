#!/usr/bin/php
<?php
include("/usr/system/news/getflow/lib/base.php");

$rawfile   = "/var/spool/news/outgoing/incomingstats.dat";
$spooldir  = "/usr/system/news/getflow/spool/data/";

//////////////////////////////////////////////////////////

$options = getopt("DMYh");

if (isset($options["D"]))
{
	$daydir = date("Ymd");
	$path =  "$spooldir/daily/$daydir/";
	if (!file_exists($path)) mkdir($path, 0664, true);
	$spoolfile = "$path/inflow.dat";
	$groupfile = "$path/groups.dat";

	$hosts = read_inn2_spoolfile($rawfile);
	$hosts = read_getflow_spoolfile($spoolfile, $hosts);
	$hosts = read_getflow_groupfile($groupfile, $hosts);
	write_spool_files($spoolfile, $groupfile, $hosts);
	clear_file($rawfile);
}

/////////////////////////////////////////////////////////


if (isset($options["M"]))
{
	$current_month = date("Ym");
	$monthlypath = "$spooldir/monthly/$current_month";

	if (!file_exists($monthlypath)) mkdir($monthlypath, 0664, true);
        $monthly_spoolfile = "$monthlypath/inflow.dat";
        $monthly_groupfile = "$monthlypath/groups.dat";

	$monthly_hosts = array();

	for ($day = 1; $day <= 31; $day++)
	{
		$current_day = sprintf("%02d", $day );
		$dailypath = "$spooldir/daily/$current_month"  . $current_day . "/";
		if (!file_exists($dailypath)) continue;
		$dailyspool = "$dailypath/inflow.dat";
		$dailygroup = "$dailypath/groups.dat";
		$monthly_hosts = read_getflow_spoolfile($dailyspool, $monthly_hosts);
        	$monthly_hosts = read_getflow_groupfile($dailygroup, $monthly_hosts);
	}

	write_spool_files($monthly_spoolfile, $monthly_groupfile, $monthly_hosts);
}

//////////////////////////////////////////////////////////

if (isset($options["Y"]))
{
        $current_year = date("Y");
        $yearlypath = "$spooldir/yearly/$current_year";

        if (!file_exists($yearlypath)) mkdir($yearlypath, 0664, true);
        $yearly_spoolfile = "$yearlypath/inflow.dat";
        $yearly_groupfile = "$yearlypath/groups.dat";

        $yearly_hosts = array();

        for ($month = 1; $month <= 12; $month++)
        {
                $current_month = sprintf("%02d", $month );
                $monthlypath = "$spooldir/monthly/$current_year$current_month/";
                if (!file_exists($monthlypath)) continue;
                $monthlyspool = "$monthlypath/inflow.dat";
                $monthlygroup = "$monthlypath/groups.dat";
                $yearly_hosts = read_getflow_spoolfile($monthlyspool, $yearly_hosts);
                $yearly_hosts = read_getflow_groupfile($monthlygroup, $yearly_hosts);
        }

        write_spool_files($yearly_spoolfile, $yearly_groupfile, $yearly_hosts);
}




if (isset($options["h"]))
{
	echo "scanflow v. 0.0.1 Copyright 2018 by Paolo Amoroso <usenet@aioe.org>\n";
	echo "USAGE: scanflow.php -D -M\n";
	echo "      -D  Fetch data from inn spool file\n";
	echo "      -M  Build per month indexes\n";
	echo "      -Y  Build per year indexes\n";
	exit(0);
}


?>
