<?php

function mysql_table_exists($dbLink, $database, $tableName)
{
   $tables = array();
   $tablesResult = mysql_query("SHOW TABLES FROM $database;", $dbLink);
   while ($row = mysql_fetch_row($tablesResult)) $tables[] = $row[0];
   if (!$result) {
   }
   return(in_array($tableName, $tables));
}

require("config.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $chan_name; ?></title>
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
<div style="text-align:center;"><h1>Trevorchan Installation</h1></div>

<?php
echo '<h2>Checking configuration file...</h2>';
if (file_exists("config.php")) {
	if (file_exists("inc/encryption.php")) {
		require("config.php");
		if ($chan_randomseed!="ENTER RANDOM LETTERS/NUMBERS HERE"&&$chan_randomseed!="") {
			echo 'Configuration appears correct.';
			echo '<h2>Checking database...</h2>';
			$reqiredtables = array("banlist","boards","config","iplist","loginattempts","modlog","news","posts","staff","wordfilter");
			foreach ($reqiredtables as $tablename) {
				if (!mysql_table_exists($dblink,$dbconnection_database,$tablename)) {
					die("Couldn't find the table <b>".$tablename."</b> in the database.  Please (re)execute the included SQL file.");
				}
			}
			echo 'Database appears correct.';
			echo '<h2>Inserting default administrator account...</h2>';
			$result = mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( 'admin' , '".md5("admin")."' , '1' , '".time()."' )",$dblink);
			if ($result) {
				echo 'Account inserted.';
				echo '<h2>Inserting default configuration values into database...</h2>';
				$result = mysql_query("INSERT INTO `config` ( `key` , `value` ) VALUES ( 'imagesinnewwindow' , '1' )",$dblink);
				if ($result) {
					$result = mysql_query("INSERT INTO `config` ( `key` , `value` ) VALUES ( 'postboxnotice' , '<ul><li>Supported file types are: GIF, JPG, PNG</li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than 200x200 pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.</li></ul>' )",$dblink);
					if ($result) {
						$result = mysql_query("INSERT INTO `config` ( `key` , `value` ) VALUES ( 'modlogmaxdays' , '7' )",$dblink);
						if ($result) {
							$result = mysql_query("INSERT INTO `config` ( `key` , `value` ) VALUES ( 'maxthumbwidth' , '200' )",$dblink);
							if ($result) {
								$result = mysql_query("INSERT INTO `config` ( `key` , `value` ) VALUES ( 'maxthumbheight' , '200' )",$dblink);
								if ($result) {
									echo 'Default configs inserted.';
									echo '<h2>Done!</h2>Installation has finished!  The default administrator account is <b>admin</b> with the password of <b>admin</b>.<br /><br />Delete this file from the server, then <a href="manage.php">add some boards</a>!';
									echo '<br /><br /><br /><h1><font color="red">DELETE THIS FILE RIGHT NOW!</font></h1>';
								} else {
									echo 'Error: '.mysql_error($dblink);
								}
							} else {
								echo 'Error: '.mysql_error($dblink);
							}
						} else {
							echo 'Error: '.mysql_error($dblink);
						}
					} else {
						echo 'Error: '.mysql_error($dblink);
					}
				} else {
					echo 'Error: '.mysql_error($dblink);
				}
			} else {
				echo 'Error: '.mysql_error($dblink);
			}
		} else {
			echo 'Please enter a random string into the <b>$chan_randomseed</b> value.';
		}
	} else {
		echo 'Unable to locate inc/encryption.php';
	}
} else {
	echo 'Unable to locate config.php';
}
?>

</body>
</html>