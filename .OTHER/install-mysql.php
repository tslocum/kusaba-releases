<?php
################################################################################
# Trevorchan MySQL Importing Script v1.0 is © 2007 David Steven-Jennings (relixx@gmail.com)
#
# This work is licensed under the Creative Commons Attribution-ShareAlike 2.5 License. 
# To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/2.5/ 
# or send a letter to Creative Commons, 543 Howard Street, 5th Floor, San Francisco, 
# California, 94105, USA.
# 
# You can modify this script as you wish just as long as this box stays intact. If 
# you do modify this script please state that you have done so and give me credit
# as the author of the original script (with my email address intact).
################################################################################
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>MySQL Batch File Importing Script</title>
<style type="text/css">
body { font-family: sans-serif; font-size: 75%; background: #ffe }
a { text-decoration: none; color: #550 }
h1,h2 { margin: 0px; background: #fca }
h1 { font-size: 150% }
h2 { font-size: 100%; margin-top: 1em }
.hl { font-style: italic }
.plus { float: right; font-size: 8px; font-weight: normal; padding: 1px 4px 2px 4px; margin: 0px 0px; background: #eb9; color: #000; border: 1px solid #da8; cursor: hand; cursor: pointer }
.plus:hover { background: #da8; border: 1px solid #c97 }
ul { list-style: none; padding-left: 0px; margin: 0px }
li { margin: 0px }
li:hover { background: #fec; }
li a { display: block; width: 100%; }
</style>
<link rel="shortcut icon" href="/favicon.ico" />
</head>

<body>
<div style="text-align:center;"><h1>MySQL Batch File Importing Script</h1></div>
<?php

if (!isset($_POST["confirm"])) {
?>
<br><br>


<font color="#FF0000"><b>WARNING!</b></font><br><br>
The purpose of this script is to quickly and easily run the commands contained within the Trevorchan SQL file, and is
to be used if you are installing the script for the first time or want to recreate the tables, for whatever reason.<br>
Running this script will delete any related tables and their data (including the admin files). I offer this script as-is and cannot be held 
responsible for any damages caused or accidental loss of data incurred as a result of running this script.<br>
Before running this script, make sure that:<br>
<ul>
<li>-&gt; You have set up the database connection and that it is working</li>
<li>-&gt; You have created the database</li>
<li>-&gt; You have created the database user and set up the config file appropriately</li>
</ul>
<form action="install-mysql.php" method="post">
<br>

<input type="checkbox" name="confirm"> By clicking this check box I agree that the author of this script cannot be held responsible for my own stupidity if something goes wrong.<br><br><br>
<input type="submit" value="Import the MySQL batch file">
</form>

<?php
} else {
	require('config.php');
	$reqiredtables = array("banlist","boards","config","iplist","loginattempts","modlog","news","posts","reports","sections","staff","wordfilter");
        foreach ($reqiredtables as $tablename) {
                if (mysql_table_exists($tc_config['dblink'],$tc_config['dbdatabase'],$tc_config['dbprefix'].$tablename)) {
                        die("Table <b>".$tc_config['dbprefix'].$tablename."</b> already exists in the database!  Drop it, and re run this script.");
                }
        }
	// Lets open the file for reading! :)
	echo '<h2>SQL Batch File Processing</h2>';
	echo 'Locating \'trevorchan_freshinstall.sql\'... ';
	if (file_exists('trevorchan_freshinstall.sql') && (filesize('trevorchan_freshinstall.sql') > 0)) {
	echo 'found.<br>';
	$sqlfile = fopen('trevorchan_freshinstall.sql', 'r');
	echo 'File opened.<br>';
	$readdata = fread($sqlfile, filesize('trevorchan_freshinstall.sql'));
	fclose($sqlfile);
	echo 'Contents read.<br>';
	}else{
	echo '<font color=red>error.</font> ';
	die('An error occured. trevorchan_freshinstall.sql does not exist in this directory or it is 0 bytes big :( Barring that, do you have read permissions for the directory?');
	}
	
	// Explodes the array
	$sqlarray = explode("\n", $readdata);
	
	// Loops through the array and deletes the non-SQL bits in the file, which is basically the '--' lines and the lines with no content
	foreach ($sqlarray as $key => $sqldata) {
		if (strstr($sqldata, '--') || strlen($sqldata) == 0){
			unset($sqlarray[$key]);
		}
	}
	// Here we are imploding everything together again...
	$readdata = implode('',$sqlarray);
	
	// ...then exploding it again. At this point we will have an array where each key's value is a one of the CREATE statements 
	$sqlarray = explode(';',$readdata);
	echo 'File contents have been formatted for use with mysql_query.<br>';
	// Lets drop any existing tables in the database
	$listoftables = mysql_query("show tables from ".$tc_config['dbdatabase']."",$tc_config['dblink']);
	
	echo '<h2>Table Creation</h2>';
	// Lets now loop through the array and create each table 
	foreach ($sqlarray as $sqldata) {
	
		if (strlen($sqldata) !== 0) { // As the array was exploded on ';', the last ';' caused a blank element to be created as there was no data after it :p
			// The following three lines retrieve the table name of the table from the sql command. It's dynamic so it doesn't matter how many tables need to be created
			// As long as each CREATE TABLE statement stays in the format CREATE TABLE `table` then this part will work.
			$pos1 = strpos($sqldata, '`');
			$pos2 = strpos($sqldata, '`', $pos1 + 1);
			$tablename = substr($sqldata, $pos1+1, ($pos2-$pos1)-1);
			echo "Attempting to create table '$tablename'... ";
			if(mysql_query($sqldata,$tc_config['dblink'])) {
				echo "success.<br>";
			} else {
				echo "<font color='red'>failed</font>. Error is: ".mysql_error().'<br>';
				die ("Table creation failed. Please rerun this script again or attempt to fix the problem if you know how to solve it.");
			}
		}
	}
	// All done :)
	echo '<br>SQL commands have finished. If all is well, proceed to the <a href="install.php">installation file</a> but don\'t forget to delete this file!'; 
}

function mysql_table_exists($dbLink, $database, $tableName)
{
   $tables = array();
   $tablesResult = mysql_query("SHOW TABLES FROM $database;", $dbLink);
   while ($row = mysql_fetch_row($tablesResult)) $tables[] = $row[0];
   if (!$result) {
   }
   return(in_array($tableName, $tables));
}

?>
</body>
</html>