<?php
/*

Simple image upload bot.


*/

//date_default_timezone_set(date_default_timezone_get());

ini_set('user_agent', 'PHPSWCTroopBot/1.0 (http://www.swcommander.info/~swc/PHPSWCTroopBot/; RY_PHPSWCTroopBot@example.org) BasedOnCoffee/1.0');

ini_set('display_errors', 1);
//ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

const FILESPEC = "..\\png_upload\\*.png";

require_once('..\\extlib\\mediawikibot.class.php');

require_once('..\\config\\swc_wiki_config.php');



function wiki_login() {
	
	global $cfg;
	
	
	$wiki = new MediaWikiBot($cfg['wiki_api'], $cfg['wiki_user'], $cfg['wiki_pw']);
	//$wiki->setDebugMode(true); // enable this to see what's happening

	$loginerror = $wiki->login();
	if (isset($loginerror)) {
		echo "login returned an error: ". print_r($loginerror, true) ."\n";
		exit(1); 
	}

	// if you do not want to edit anything you don't need this
	$edittoken = $wiki->getEditToken();
	if ($edittoken == null) {
		echo "Unable to aquire an edit token\n";
		exit(2);
	}
	return $wiki;
}

function wiki_upload_png($wiki,$path,$filenameonly,$descr) {
	$cfile = curl_file_create($path,'image/png',$filenameonly); // try adding

	$uploaddata = array(
			'filename' => $filenameonly,
			'text'     => $descr, //." (Version 2: cropped)",
			'ignorewarnings' => 1, //must be set to upload new versions of images
			'file'     => $cfile,
			//'file'     => "@". $path .";type=image/png",
			'token'    => $wiki->getEditToken(),
	);
	$r = $wiki->upload($uploaddata);
	echo "  Upload result:".$r['upload']['result']."\n";
	if (preg_match('/Success/i',$r['upload']['result']))
		return TRUE;
	else {
		var_dump($r['upload']['warnings']);
		return FALSE;
	}

	// You may want to check if $r['upload']['result'] contains 'Success'
}

try {
	$wiki = wiki_login();

	foreach (glob(FILESPEC) as $filename) {
		if (preg_match('/([^\\\\\\/]*)\.([^.]+)$/',$filename,$matches)) {
			echo $filename . "<>". $matches[1] . "<>" . $matches[2] . "\n";
			wiki_upload_png($wiki,$filename,$matches[1].".".$matches[2],$matches[1]);
		}
//		break;
	}	
}
//catch exception
catch(Exception $e) {
  echo 'Exception: ' .$e->getMessage();
}
?> 