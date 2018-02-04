# getflow
PHP inflow replacement
    Introduction
    Download
    How getflow works


1. Introduction

Around 1994, Fabien Tassin wrote inflow, a perl script to monitor the flow of articles posted on USENET. Inflow has been used for decades as a tool for managing news servers but in recent times it has become obsolete and hard to find. Getflow is an inflow replacement that offers more search functions and options to customize the output but that is not able to generate charts.
At the moment, getflow only requires that any modern version of InternetNews and PHP5 are installed on the system but it has no other dependencies. It is licensed under the BSD license as well as InternetNetNews.
Getflow works in the same way as inflow: at regular intervals, a script with this specific function (scanflow) reads a file generated by INND for this purpose, populates a file based database with the aggregate data and builds some indexes. The newsmaster queries the database generated by scanflow using a command line script called getflow that provides the required data in the format chosen by the administrator.


2. Download

Getflow is currently in beta, some bugs may be present in the program. its current version is 0.1 and it can be downloaded using this link.


3. How getflow works

Getflow provides and manages eight datasets:

    feeds  - Number of articles received from each incoming NNTP feed.
    origins  - Number of articles per posting server.
    hiers - Number of articles per hierarchy.
    groups - Number of articles per group.
    localgroups - Number of local articles sent to each group
    arthiers - Number of articles per hierarchy
    artgroups - Number of articles per groups
    localartgroups - Number of local articles per group


The datasets 'artgroups', 'arthiers' and 'localartgroups' differ from 'groups', 'hiers' and 'localgroups' because they count the articles regardless of the number of groups in which each post was sent. So, for example, if a message is crossposted to five groups, in the 'groups', 'hiers' and 'localgroups' datasets it is counted five times, in the 'artgroups', 'arthiers' and 'localartgroups' datasets only once. Since all the news servers save each message only once without regard to the number of groups in which it was posted, the datasets 'groups', 'hiers' and 'localgroups' describe the server spool size; 'artgroups', 'arthiers' and 'localartgroups' the total number of messages that should be downloaded to clone the server.

Getflow provides three time based indices: daily, monthly and yearly. Users can request data about a single day, month or year or the total amounts ​​related to an arbitrarily chosen range of days, months or years. Getflow can also print data in text format, csv or html on screen. It can be configured to limit the number of results shown on the screen or to filter the data using a regular expression. Even if it's able to print nice html pages, getflow can't be used as cgi script.
NAME
       getflow - Show data about flow of USENET articles

SYNOPSIS
       getflow -T -d dataset \[ -r epoch -f format -s search -t days -m months -l number -x file -v -i ]
       getflow -F -d dataset -l number \[ -k -r epoch -f format -s search -t days -m months -x file -v -i ]
       getflow -h

DESCRIPTION
       getflow reads databases generated by scanflow than prints data in various formats.


