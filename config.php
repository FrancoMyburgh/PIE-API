<?php

error_reporting(E_ALL);

ob_start();
session_start();

date_default_timezone_set("Africa/Johannesburg");

// Connection to Live Server database.
try{
	$con = new PDO("mysql:dbname=brainygi_pieparking;host=localhost", "brainygi_pieparking", "6PyAoEzrDXuh");
	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
}
catch(PDOException $e){
	exit("Connection Failed: ". $e->getMessage());
}

// Connection to XAMPP database.
// try{
// 	$con = new PDO("mysql:dbname=degasity;host=localhost", "root", "");
// 	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
// }
// catch(PDOException $e){
// 	exit("Connection Failed: ". $e->getMessage());
// }

?>