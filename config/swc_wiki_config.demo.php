<?php

/*
  Wiki login:
  Use a special Bot account if possible.
*/

$cfg['wiki_user'] = '';
$cfg['wiki_pw'] = '';

/*
  Wiki API configuration
*/

$cfg['wiki_url_base'] ="http://www.swcommander.info/~swc/wiki/";
$cfg['wiki_api'] = $cfg['wiki_url_base'].'api.php';

/*
  Directories in local file system:
*/

$cfg['dir_json'] = "json".DIRECTORY_SEPARATOR;    //JSON Files containing required data (base.json, ...)

//Directories for file based operation (load/saves wiki page source from/to *.txt instead of Wiki)
$cfg['dir_input'] = "input".DIRECTORY_SEPARATOR;  //previous page source data for troops
$cfg['dir_output'] = "output".DIRECTORY_SEPARATOR;//created/edited page source data for troops (result)

/*
  Wiki behaviour:
  These variables can be edited to enabled/disable the use/update of the Wiki data. 
  I.e. for debugging.
*/

$cfg['read_wiki'] = FALSE; //TRUE -> read unit pages from wiki (if any)
						   //FALSE -> read from text files in input dir (if any)
						   //note: if you change the code to read neither from file nor wiki
						   //      then call the init-method of the template object
$cfg['read_wiki_retries'] = 3; //retries on error if server is unreliable (2 retries = 3 tries in all per request)
$cfg['write_wiki'] = FALSE;//TRUE -> write/update unit pages to wiki
$cfg['write_wiki_retries'] = 3; //retries on error if server is unreliable (2 retries = 3 tries in all per request)
$cfg['write_file'] = TRUE;//TRUE -> write unit page source to output dir (good for testing)

/*
  Config variables to control unit iteration in *Data tables.
*/

// TroopData
$cfg['process_troops'] = FALSE;
//regexp on unitID to match only specific troops. /.*/ will match all
$cfg['match_troop_re'] = "/.*/"; 
//$cfg['match_troop_re'] = "/Dropship/i"; 

// TroopData, Mercenaries
$cfg['process_mercenaries'] = FALSE;
//regexp on unitID to match only specific troops. /.*/ will match all
$cfg['match_mercenaries_re'] = "/.*/"; 
//$cfg['match_mercenaries_re'] = "/BigMouth|SecurityDroid/i"; 

// TroopData, Droidekas
$cfg['process_droidekas'] = FALSE;
//regexp on unitID to match only specific troops. /.*/ will match all
$cfg['match_droidekas_re'] = "/.*/"; 
//$cfg['match_droidekas_re'] = "/ChampionEmpireDroideka/"; 

// SpecialAttackData (Starships)
$cfg['process_ships'] = FALSE;
$cfg['match_ship_re'] = "/.*/";  //regexp on specialAttackID to match only specific ships. /.*/ will match all
//$cfg['match_ship_re'] = "/HWK|VT49|FangFighter|AtmosMig/i";  //regexp to match only specific ships.

// BuildingData, Turrets
$cfg['process_turrets'] = FALSE;
$cfg['match_turret_re'] = "/.*/";  //regexp on turretID to match only specific turrets. /.*/ will match all
//$cfg['match_turret_re'] = "/Sonic/i";  //regexp to match only specific turrets.

// BuildingData
$cfg['process_buildings'] = FALSE;
$cfg['match_building_re'] = "/cantina/i";  //regexp on uid to match only specific ships. /.*/ will match all
//$cfg['match_building_re'] = "/.*/i";  //regexp to match only specific buildings

// EquipmentData
$cfg['process_equipment'] = FALSE;
//$cfg['match_equipment_re'] = "/ATMP/i";  //regexp on uid to match only specific ships. /.*/ will match all
$cfg['match_equipment_re'] = "/.*/i";  //regexp to match only specific buildings

// TrapData
$cfg['process_traps'] = TRUE;
//regexp on unitID to match only specific traps. /.*/ will match all
$cfg['match_trap_re'] = "/.*/"; 



?> 