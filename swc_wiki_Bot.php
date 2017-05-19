<?php
/*
SWC Wiki Bot

Purpose:
	Write/Update Wiki Entries of game items:
		Units (Troops, Vehicles, Spaceships)
		Buildings
		
	Bot will try to import existing page sources from either the input-dir (text-files) or
	directly from the wiki. It will overwrite all template parameters it knows but it'll try to
	retain all unknown parameters and other edits. Also pages with unknown template names might be
	left unchanged.
	
	For auto-edit please put follow these formatting conventions for page content:
	
	
	Any content (will be left unchanged)
	...
	{{ TemplateName |
	  | parameter1 =
	  ...
	  | parametern =
	  {{ NestedTemplate |  (parameters in nested templates will also be filled in, if any exist)
		| parameter n+1
		...
		| parameter n+m
	  }}
	}}
	Any content
	...

Install:
	Edit values in: config/swc_wiki_config.php
	json-dir: see below
	input-dir: the script can read page sources from individual text files 
		(but usually directly from wiki)
	output-dir: the script will write page sources to the output dir
		and optionally to the wiki. So you can take the text files and manually edit the wiki
		or let it do it the script.
	
	Memory Limit (PHP.INI):
	This script reads the json-files into memory. So it may need the memory limit to be lifted.
	64MB should be adequate. (tested with 128MB)
	
Needs:
	JSON Game Data in the $cfg['dir_json'] directory:
		base.json
		strings_en-US.json

	See this forum post for instructions how to obtain the JSON-Data:
		http://www.swcommander.com/viewtopic.php?f=31&t=18402

*/

//date_default_timezone_set(date_default_timezone_get());

ini_set('user_agent', 'PHPSWCTroopBot/1.0 (http://www.swcommander.info/~swc/PHPSWCTroopBot/; RY_PHPSWCTroopBot@example.org) BasedOnCoffee/1.0');

ini_set('display_errors', 1);
//ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once('extlib/mediawikibot.class.php');

require_once('config/swc_wiki_config.php');

require_once('lib/swc_json_data.php');
require_once('lib/TroopTmpl.php');
require_once('lib/TrapTmpl.php');
require_once('lib/MercenaryTmpl.php');
require_once('lib/StarshipTmpl.php');
require_once('lib/TurretTmpl.php');
require_once('lib/BuildingTmpl.php');
require_once('lib/DroidekaTmpl.php');
require_once('lib/EquipmentTmpl.php');



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

//iterate dataTable and pass all levels of a unit to a new template object
function processData($tmplClass,$jdb,$dataTable) {
	global $cfg;
	global $wiki;

	$lvls = array();
	
	foreach($dataTable as $k => $v) {
		$nextrec=next($dataTable);
		
		//filter records here
		if ($tmplClass::validRec($v)) {
			
			//echo $v->{'uid'}."\n";
			
			//split uid and appended lvl
			$splitId=$tmplClass::splitUidLevel($v);
			if ($splitId !== FALSE) {
				$lvls[]=$v; //add record
				
				if ($nextrec !== FALSE) {
					$splitNext=$tmplClass::splitUidLevel($nextrec);
					$nextId=($splitNext!==FALSE) ? $splitNext['id'] : "";
				}
				else $nextId='';

				//echo "uid:".$splitId['id']."\n";
				
				//next uid is new unit id (or this is the last record), or next record is filtered
				if (($splitId['id'] != $nextId) ||
					!$tmplClass::validRec($nextrec)) { 
					if (count($lvls)>0) {
						
						$trpWiki = new $tmplClass($lvls,$jdb);
						echo "uid:".$splitId['id']."/title:".$trpWiki->getTitle()."/records:".count($lvls)."\n";

						if ($cfg['read_wiki'])
							$trpWiki->readFromWiki($wiki);
						else
							$trpWiki->readFromFile($cfg['dir_input'].$trpWiki->getFileName());
					
						$trpWiki->process();
						if ($cfg['write_file'])
							$trpWiki->writeToFile($cfg['dir_output'].$trpWiki->getFileName());
						if ($cfg['write_wiki'])
							$trpWiki->writeToWiki($wiki);
					}
					$lvls = array();
				}
			}
		}
	}
}

$jdb = array(); //array holding json databases;


try {
	if ($cfg['read_wiki'] || $cfg['write_wiki'])
		$wiki = wiki_login();
	
	$jdb['version'] = new JsonVersionData($cfg['dir_json']."version.json");
	$jdb['base'] = new JsonBaseData($cfg['dir_json']."base.json");
	$jdb['strings'] = new JsonStringData($cfg['dir_json']."strings_en-US.json");

	if ($cfg['process_traps'])
		processData('TrapTmpl',$jdb,$jdb['base']->getBuildingData());
	
	if ($cfg['process_troops'])
		processData('TroopTmpl',$jdb,$jdb['base']->getTroopData());
	
	if ($cfg['process_ships'])
		processData('StarshipTmpl',$jdb,$jdb['base']->getSpecialAttackData());

	if ($cfg['process_turrets'])
		processData('TurretTmpl',$jdb,$jdb['base']->getBuildingData());

	if ($cfg['process_mercenaries'])
		processData('MercenaryTmpl',$jdb,$jdb['base']->getTroopData());

	if ($cfg['process_buildings'])
		processData('BuildingTmpl',$jdb,$jdb['base']->getBuildingData());

	if ($cfg['process_droidekas'])
		processData('DroidekaTmpl',$jdb,$jdb['base']->getTroopData());
	
	if ($cfg['process_equipment'])
		processData('EquipmentTmpl',$jdb,$jdb['base']->getEquipmentData());
	
}
//catch exception
catch(Exception $e) {
  echo 'Exception: ' .$e->getMessage();
}
?> 