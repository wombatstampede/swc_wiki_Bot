<?php

//todo: implement skinOverrrideData 

//require_once('..\\config\\swc_wiki_config.php');

require_once('WikiLeveledTmpl.php');
require_once('swc_json_data.php');

require_once('TroopTmpl.php');
require_once('BuildingTmpl.php');
require_once('TurretTmpl.php');


/* -------------------------------------- */
/*
  EquipmentTmpl:
	Class for WikiTemplate filling with Armory Equipment data
*/


class EquipmentTmpl extends WikiLeveledTmpl {
	CONST DEFAULT_TEMPLATE='Equipment';
	CONST TEMPLATE_MATCH = '/{{\s*Equipment/'; 
	
	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	//CONST FILTER_INVALID_UNIT = '//';
	
	protected $skinOverrideData;
	protected $equipmentData;
	protected $equipmentEffectData;
	protected $buffData;
	protected $planetData;
	protected $buildingData;
	protected $turretData;
	protected $linkedTroop;       //a single record of linked Troop (minLvl)
	protected $linkedTroopTmpl;   //template object for minLvl troop record	
	protected $linkedBuilding;    //a single record of linked Building (minLvl)
	protected $linkedTurret;      //a single record of linked Turret (minLvl)
	protected $linkedBuildingTmpl;//either turret or other building
	protected $buildingDataByUid;
	
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->skinOverrideData = $jdb['base']->getSkinOverrideData();
		$this->equipmentData = $jdb['base']->getEquipmentData();
		$this->equipmentEffectData = $jdb['base']->getEquipmentEffectData();
		$this->buffData = $jdb['base']->getBuffData();
		$this->planetData = $jdb['base']->getPlanetData();
		$this->troopData = $jdb['base']->getTroopDataByIDLvl();
		$this->buildingData = $jdb['base']->getBuildingDataByIDLvl();
		$this->turretData = $jdb['base']->getTurretData();
		
		$this->linkedTroop = $this->valueForID('troop.@',$this->minLvl,FALSE);
		if (isset($this->linkedTroop))
			$this->linkedTroopTmpl = new TroopTmpl(array($this->linkedTroop),$jdb);
		$this->linkedBuilding = $this->valueForID('building.@',$this->minLvl,FALSE);
		$this->linkedTurret = $this->valueForID('turret.@',$this->minLvl,FALSE);
		if (isset($this->linkedBuilding))
			if (isset($this->linkedTurret)) { //turrettmpl constructs with buildingData records (not turretData records)
				$this->linkedBuildingTmpl = new TurretTmpl(array($this->linkedBuilding),$jdb);
			} else
				$this->linkedBuildingTmpl = new BuildingTmpl(array($this->linkedBuilding),$jdb);
		
		
		$this->buildingDataByUid = $jdb['base']->getBuildingData();
/*
  | planet1..n = 
  | fortroop =
  | forbuilding =
  | upcost1..n
  | uptime1..n
  | buff1 =
  | value1_1 =  
*/		
		
		$this->template_params.="
  | capacity =
  | upgradecurrency = data fragments";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		return ((preg_match($cfg['match_equipment_re'],$rec->{'uid'}) === 1) &&
				(preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1));
	}

	protected function getID() {
		return $this->splitUidLevel($this->defaultRec())['id'];
	}
	
	// building-> building title + equiment name --> "Burst Turret (Empire)"+" "+"Enhanced Heat Sink"
	// troop -> just name
	
	public function getTitle() {
		$key = $this->getTitleStrKey();
		$title = $this->getStr($key);
		if (isset($this->linkedTurret) && isset($this->linkedBuildingTmpl))//add Turret Name before Title (only for turrets)
			$title=$this->linkedBuildingTmpl->getTitle()." ".$title;
		else
			$this->checkAmbiguousTitle($key,$title);
		return $title;
	}
	
	protected function getTitleStrKey() {
		return $this->defaultRec()->{'equipmentName'};
	}

	protected function getDescriptionStrKey() {
		return $this->defaultRec()->{'equipmentDescription'};
	}
	
	protected function checkAmbiguousTitle($key,&$title) {
		if (isset($this->linkedBuilding) && !isset($this->linkedTurret))
			$title.=" (".ucfirst($this->defaultRec()->{'faction'}).")";
	}
	
	/*protected function currencyCost() {
		return 'data fragments';
	}*/
	
	protected function currencyUpgrade() {
		return "data fragments";
	}
	
	//checks ID for syntax "table.id" and other linked tables and returns linked table value in case
	//behaves a bit like a sql join
	//here, id "@" is a special case and returns the record itself
	//equipment -> effect
	//equipment -> effect -> troop
	//equipment -> effect -> building
	//equipment -> effect -> building -> turret
	//equipment -> effect -> buff1-n
	//Todo!!
	//      skinOverrideData
	//equipment -> skins[0]-> maxAttackRange/minAttackRange, projectileType, role, gunSequence etc, priorities
	//equipment -> planet1-n
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$eqrec = $this->recs[$lvl];
		$rec = null;
		
		if (preg_match('/^(effect|troop|building|turret|buff[0-9])\.(.*)$/',$id,$eqEffID)) {
			//take 1st effectuid
			if (property_exists($eqrec,'effectUids') && (count($eqrec->{'effectUids'})>0) &&
				array_key_exists($eqrec->{'effectUids'}[0],$this->equipmentEffectData)) {
				$erec = $this->equipmentEffectData[$eqrec->{'effectUids'}[0]];
				
				//echo "effect $id,".$erec->{'affectedTroopIds'}[0].$lvl."\n";
				$id = $eqEffID[2];
				//table equipmentEffectTable
				if ($eqEffID[1]=="effect")
					$rec = $erec;
				//first linked troop record (by unitID+level)
				elseif (($eqEffID[1]=="troop") && property_exists($erec,'affectedTroopIds') && 
						(count($erec->{'affectedTroopIds'})>0) &&
						array_key_exists($erec->{'affectedTroopIds'}[0].$lvl,$this->troopData)) {
					//echo "troop:".$eqEffID[1];
					$rec = $this->troopData[$erec->{'affectedTroopIds'}[0].$lvl];
				//first linked building record (by unitID+level)
				}
				elseif (preg_match("/^(building|turret)$/",$eqEffID[1]) && property_exists($erec,'affectedBuildingIds') && 
						(count($erec->{'affectedBuildingIds'})>0) &&
						array_key_exists($erec->{'affectedBuildingIds'}[0].$lvl,$this->buildingData)) {
					$brec=$this->buildingData[$erec->{'affectedBuildingIds'}[0].$lvl];
					//building
					if ($eqEffID[1]=="building")
						$rec = $brec;
					//or continue to turret
					elseif (property_exists($brec,'turretId') && 
							array_key_exists($brec->{'turretId'},$this->turretData))
						$rec=$this->turretData[$brec->{'turretId'}];
					//echo "brec ".$eqEffID[1]." tid: ".$brec->{'turretId'}."/set:".isset($rec)."\n";
				}
				elseif (preg_match('/^buff([0-9]+)$/',$eqEffID[1],$bID) && //buff1,buff2
						array_key_exists($bID[1]-1,$erec->{'buffUids'}) &&
						array_key_exists($erec->{'buffUids'}[$bID[1]-1],$this->buffData))
					$rec = $this->buffData[$erec->{'buffUids'}[$bID[1]-1]];
			}
		}
		elseif (preg_match('/^planet([0-9]+)\.(.*)$/',$id,$plID)) {//planet1-planet9
			if (property_exists($eqrec,'planetIDs') &&
				array_key_exists($plID[1]-1,$eqrec->{'planetIDs'})) {
				if (array_key_exists($eqrec->{'planetIDs'}[$plID[1]-1],$this->planetData)) {
					$rec = $this->planetData[$eqrec->{'planetIDs'}[$plID[1]-1]];
					$id = $plID[2];
				}
			}
		}
		elseif (preg_match('/^requirement([0-9])\.(.*)$/',$id,$reID)) {//requirement1-requirement9
			if (property_exists($eqrec,'requirements') &&
				array_key_exists($reID[1]-1,$eqrec->{'requirements'})) {
				if (array_key_exists($eqrec->{'requirements'}[$reID[1]-1],$this->buildingDataByUid)) {
					$rec = $this->buildingDataByUid[$eqrec->{'requirements'}[$reID[1]-1]];
					$id = $reID[2];
				}
			}
			else $rec = null;
		}
		else $rec=$eqrec;
		
		if ($rec !== null)
			if ($id == "@")
				return $rec;
			else
				if (!$fmt && !property_exists($rec,$id))
					return null;
				else
					return $fmt?$this->fmt($rec,$id):$rec->{$id};
		else
			if ($fmt)
				return "";
			else
				return null;
	}
	
	//fill in parameter values into template
	public function process() {
		//fill in template
		
		parent::process();
		$rec = $this->defaultRec();
		$this->setTmplParam('unitID',$this->fmt($rec,'equipmentID'));
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));

		//!! temporary?
		$this->setTmplParam('image',$this->getTitle().".png",TRUE);//reset image parameter/remove 
		//!!
		
		$this->setTmplParam('type',$this->fmt($rec,'quality'));
		
		$this->setTmplParam('capacity',$this->fmt($rec,'size'));

		if (property_exists($rec,'planetIDs'))
			foreach($rec->{'planetIDs'} as $k => $v) {
				$planetIx=$k+1;
				$pname=$this->getStr('planet_name_'.$v);
				if(!isset($pname)) //use column value in case there's no string
					$pname=$this->valueForID('planet'.$planetIx.'.planetBIName',$this->minLvl);
				$this->setTmplParam('planet'.$planetIx,$pname,TRUE);
			}
			
		//mark equipment either for hero/troop or structure
		if (isset($this->linkedTroop)) {
			$this->setTmplParam('forunit',$this->linkedTroopTmpl->getTitle(),TRUE);
			if (preg_match("/Hero/",$this->valueForID('effect.affectedTroopIds',$this->minLvl,FALSE)[0])) {
				$this->setTmplParam('hero','hero',TRUE);
				$this->setTmplParam('troop','',TRUE);
			} else {
				$this->setTmplParam('hero','',TRUE);
				$this->setTmplParam('troop','troop',TRUE);
			}
		} else {
			$this->setTmplParam('hero','',TRUE);
			$this->setTmplParam('troop','',TRUE);
		}
		if (isset($this->linkedBuilding)) {
			$this->setTmplParam('forunit',$this->linkedBuildingTmpl->getTitle(),TRUE);
			$this->setTmplParam('structure','structure',TRUE);
		} else {
			$this->setTmplParam('structure','',TRUE);
		}

		$this->setTmplParam('upgradecurrency',$this->currencyUpgrade(),TRUE);
		//$this->setTmplParam('trainingcurrency',$this->currencyCost(),TRUE);
		
		//echo ($this->minLvl."-".$this->maxLvl."\n");
		
		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			$this->setTmplParam('upcost'.$i,$this->valueForID('upgradeShards',$i),TRUE);
			$this->setTmplParam('uptime'.$i,$this->valueForID('upgradeTime',$i),TRUE);
			$rq1=$this->valueForID('requirement1.buildingID',$i,FALSE);
			if ($rq1 != "")
				$this->setTmplParam('require'.$i,$this->getStr('bld_title_'.lcfirst($rq1)).' ('.$this->valueForID('requirement1.lvl',$i).')',TRUE);
			else
				$this->setTmplParam('require'.$i,'',TRUE);
		}
		
		$buffs=$this->valueForID('effect.buffUids',$this->minLvl,FALSE);
		
		foreach ($buffs as $bk => $bv) {
			$buffIx=intval($bk)+1;
			$this->setTmplParam('bufftype'.$buffIx,preg_replace("/maxHealth/i","health",$this->valueForID('buff'.$buffIx.'.modifier',$this->minLvl,TRUE)),TRUE);
			//Try to make up "human readable" id of the buffID string
			//#1: Remove prefix
			$buffid=preg_replace("/buffEqp|buff_eqp_/i","",$this->valueForID('buff'.$buffIx.'.buffID',$this->minLvl));
			//#2: HelloWorld -> Hello World
			$buffid=preg_replace("/([a-z])([A-Z])/","$1 $2",$buffid); 
			//#3: hello_world -> Hello World
			//echo "buffid: $buffid,";
			$buffid = preg_replace_callback('(_[a-z]|^[a-z])',
						function ($m) {
							return (strlen($m[0])>1)?(" ".strtoupper(substr($m[0],1))):strtoupper($m[0]);
						},
						$buffid
					  );
			$this->setTmplParam('buffid'.$buffIx,$buffid,TRUE);
			$this->setTmplParam('buffapply'.$buffIx,$this->valueForID('buff'.$buffIx.'.applyValueAs',$this->minLvl),TRUE);
			
			for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
				$this->setTmplParam('value'.$buffIx."_".$i,$this->valueForID('buff'.$buffIx.'.value',$i),TRUE);
			}
		}
		
		return $this->chgCount;
	}
	
}
?>