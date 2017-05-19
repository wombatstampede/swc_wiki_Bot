<?php

require_once('UnitTmpl.php');
require_once('TroopTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  TrapTmpl:
	Class for WikiTemplate filling with Trap unit data
	
  !Important!
  Expects records from BuildingData Table
  
  
*/

class TrapTmpl extends UnitTmpl{
	CONST DEFAULT_TEMPLATE='Trap';
	CONST TEMPLATE_MATCH = '/{{\s*Trap/'; //default matches to Starship*

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_VALID_ARMOR = '/^trap$/';
	//CONST FILTER_INVALID_UID = '/^empire_ab_|^rebel_ab_/';
	
	protected $trapData;
	protected $buildingData;
	protected $specialAttackData;
	protected $troopData;
	protected $projectileData;

	protected $linkedTroop;       //a single record of linked Troop (minLvl)
	protected $linkedTroopTmpl;   //template object for minLvl troop record	

	
	//baseTable in recs -> BuildingData
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		$this->trapData = $jdb['base']->getTrapData();
		$this->buildingData = $jdb['base']->getBuildingData();
		$this->specialAttackData = $jdb['base']->getSpecialAttackData();
		$this->troopData = $jdb['base']->getTroopData();
		$this->projectileData = $jdb['base']->getProjectileData();
		
		$this->linkedTroop = $this->valueForID('troop.@',$this->minLvl,FALSE);
		if (isset($this->linkedTroop))
			$this->linkedTroopTmpl = new TroopTmpl(array($this->linkedTroop),$jdb);
		
		$this->template_params.="
  | type = 
  | armortype = 
  | triggerrange = 
  | buildcurrency = alloy
  | rearmcurrency = alloy
  | dropunit =
  | splash = 
  | require1 =
  | require2 =
  | require3 =
  | require4 =
  | require5 =
  | require6 =
  | require7 =
  | require8 =
  | require9 =
  | require10 =
  | image =
  | xp1 =
  | xp2 =
  | xp3 =
  | xp4 =
  | xp5 =
  | xp6 =
  | xp7 =
  | xp8 =
  | xp9 =
  | xp10 =
  | damage1 = 
  | damage2 = 
  | damage3 = 
  | damage4 = 
  | damage5 = 
  | damage6 = 
  | damage7 = 
  | damage8 = 
  | damage9 = 
  | damage10 = 
  | health1 = 
  | health2 = 
  | health3 = 
  | health4 = 
  | health5 = 
  | health6 = 
  | health7 = 
  | health8 = 
  | health9 = 
  | health10 = 
  | dropunits1 = 
  | dropunits2 = 
  | dropunits3 = 
  | dropunits4 = 
  | dropunits5 = 
  | dropunits6 = 
  | dropunits7 = 
  | dropunits8 = 
  | dropunits9 = 
  | dropunits10 = 
  | cost1 = 
  | cost2 = 
  | cost3 = 
  | cost4 = 
  | cost5 = 
  | cost6 = 
  | cost7 = 
  | cost8 = 
  | cost9 = 
  | cost10 = 
  | rearmcost1 = 
  | rearmcost2 = 
  | rearmcost3 = 
  | rearmcost4 = 
  | rearmcost5 = 
  | rearmcost6 = 
  | rearmcost7 = 
  | rearmcost8 = 
  | rearmcost9 = 
  | rearmcost10 = 
  | time1 = 
  | time2 = 
  | time3 = 
  | time4 = 
  | time5 = 
  | time6 = 
  | time7 = 
  | time8 = 
  | time9 = 
  | time10 = ";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		return ((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) &&
				(preg_match(self::FILTER_VALID_ARMOR,$rec->{'armorType'}) === 1) &&
//				(preg_match(self::FILTER_INVALID_UID,$rec->{'uid'}) !== 1) &&
				(preg_match($cfg['match_trap_re'],$rec->{'uid'}) === 1));
	}
	
	protected function getID() {
		return $this->defaultRec()->{'buildingID'};
	}
	
	protected function getTitleStrKey() {
		return 'bld_title_'.$this->getID();
	}

	protected function getDescriptionStrKey() {
		return 'bld_desc_'.$this->getID();
	}
	
	protected function checkAmbiguousTitle($key,&$title) {
//		$title.=" (".ucfirst($this->defaultRec()->{'faction'}).")";
	}
	
	protected function currencyBuild() {
		return "alloy";
	}

	protected function currencyRearm() {
		return "alloy";
//Maybe check	TrapData.rearmMaterialsCost/rearmCreditsCost
	}
	
	
/*
  BuildingData.trapID (armorType: trap)
	-> TrapData.uid
	TrapData.eventData
		-> SpecialAttackData.uid
		SpecialAttackData.projectileType
			-> ProjectileData.uid
		SpecialAttackData.linkedUnit  (Dropship)
			-> TroopData.uid
*/
	
	//checks ID for syntax "projectile.id" and returns linked table value in case
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$bldRec=$this->recs[$lvl];
		$rec=null;
		
		if (preg_match('/^(projectile|trap|specialAttack|troop)\.(.*)$/',$id,$fldID)) {
			if (array_key_exists($bldRec->{'trapID'},$this->trapData)) {
				$trapRec=$this->trapData[$bldRec->{'trapID'}];
				$id = $fldID[2];
				
				if ($fldID[1] == "trap") {
					$rec = $trapRec;
					
				} elseif(($fldID[1] == "specialAttack") || ($fldID[1] == "projectile") || ($fldID[1] == "troop")) {
					
					if (array_key_exists($trapRec->{'eventData'},$this->specialAttackData)) {
						$saRec=$this->specialAttackData[$trapRec->{'eventData'}];
						
						if ($fldID[1] == "specialAttack") {
							$rec = $saRec;
						} elseif (($fldID[1] == "projectile") &&
									array_key_exists($saRec->{'projectileType'},$this->projectileData)) {
							$rec = $this->projectileData[$saRec->{'projectileType'}];
						} elseif (($fldID[1] == "troop") && property_exists($saRec,'linkedUnit') &&
									array_key_exists($saRec->{'linkedUnit'},$this->troopData)) {
							$rec = $this->troopData[$saRec->{'linkedUnit'}];
						}
					}
				}
			}
		}
		elseif (preg_match('/^requirement([1-2])\.(.*)$/',$id,$reID)) {//requirement1-> requirements[0], requirement2 -> requirements2
				$id = $reID[2];
				if ((property_exists($bldRec,'requirements') && ($reID[1]==1) &&
					 array_key_exists(0,$bldRec->{'requirements'}))) {
					if (array_key_exists($bldRec->{'requirements'}[0],$this->buildingData)) {
						$rec = $this->buildingData[$bldRec->{'requirements'}[0]];
					}
				} elseif (property_exists($bldRec,'requirements2') && ($reID[1]==2)) {
					if (array_key_exists($bldRec->{'requirements2'},$this->buildingData)) {
						$rec = $this->buildingData[$bldRec->{'requirements2'}];
					}
				}
		}
		else
			$rec = $bldRec;
		
		if ($rec !== null)
			if ($id == "@")
				return $rec;
			else
				if (!$fmt && !property_exists($rec,$id))
					return null;
				else
					return $fmt?$this->fmt($rec,$id):$rec->{$id};
		else
			return $fmt?"":null;
	}
	
	protected function armorType() {
		$key = $this->defaultRec()->{'armorType'};
		
		return ucfirst($key);
	}
	
	//fill in parameter values into template
	public function process() {
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('unitID',$this->getID());
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));

/*
	SpecialAttackData.unitCount
	TrapData.rearmMaterialsCost
	TrapData.rearmCreditsCost
	TrapData.triggerConditions ("Radius(2) & ArmorNot(flierInfantry)")

		*/
		
		//maybe paramNTo1 value?
		$this->setTmplParam('type',$this->fmt($rec,'type'));
		$this->setTmplParam('armortype',$this->armorType());

		$this->setTmplParam('buildcurrency',$this->currencyBuild(),TRUE);
		$this->setTmplParam('rearmcurrency',$this->currencyRearm(),TRUE);

		$trig = $this->valueForID('trap.triggerConditions',$this->maxLvl);
		if (preg_match('/ArmorNot\(([^\)]+)\)/',$trig,$rangeMatch) == 1)
			$this->setTmplParam('triggerexcludearmor',$this->formatArmorType($rangeMatch[1]));
		else
			$this->setTmplParam('triggerexcludearmor','',TRUE);
		if (preg_match('/Radius\(([0-9]+)\)/',$trig,$rangeMatch) == 1)
			$this->setTmplParam('triggerrange',$rangeMatch[1]);
		else
			$this->setTmplParam('triggerrange','',TRUE);

		if (isset($this->linkedTroop)) {
			$this->setTmplParam('dropunit',$this->linkedTroopTmpl->getTitle(),TRUE);
		} else {
			$this->setTmplParam('dropunit','',TRUE);
		}

		

#		$this->setTmplParam('rangemin',$this->valueForID('turret.minAttackRange',$this->maxLvl));
#		$this->setTmplParam('rangemax',$this->valueForID('turret.maxAttackRange',$this->maxLvl));

/*
  | triggerrange = 
  | buildcurrency = alloy
  | rearmcurrency = alloy
  | dropunit =
  | splash = 
  | require1 =
  | image =
  | xp1 =
  | damage1 = 
  | health1 = 
  | dropunits1 = 
  | cost1 = 
  | rearmcost1 = 
  | time1 = 
*/


		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			//get image level-id from count at end of assetName (not every building level has own image)
			if (preg_match('/([0-9]+)$/',$rec->{'assetName'},$lvlMatch) == 1)
				$this->setTmplParam('image'.$i,$this->getTitle()." l".$lvlMatch[1].".png");
			else
				$this->setTmplParam('image'.$i,$this->getTitle().".png");
			
//			if (!preg_match('/sonic/i',$rec->{'uid'}))
//				$this->setTmplParam('damage'.$i,$this->valueForID('turret.dps',$i));
//			else
				$this->setTmplParam('damage'.$i,$this->valueForID('specialAttack.damage',$i));
			$this->setTmplParam('time'.$i,$this->fmt($rec,'time'));
			$this->setTmplParam('cost'.$i,$this->fmt($rec,'materials'));
			//todo check rearmCreditsCost
			$this->setTmplParam('rearmcost'.$i,$this->valueForID('trap.rearmMaterialsCost',$i));
			$this->setTmplParam('dropunits'.$i,$this->valueForID('specialAttack.unitCount',$i));
			$this->setTmplParam('health'.$i,$this->fmt($rec,'health'));
			$this->setTmplParam('xp'.$i,$this->fmt($rec,'xp'));
			
			$rq1=$this->valueForID('requirement1.buildingID',$i,FALSE);
			$rq2=$this->valueForID('requirement2.buildingID',$i,FALSE);
			if ($rq1 != "") {
				$rq1=$this->getStr('bld_title_'.lcfirst($rq1)).' ('.$this->valueForID('requirement1.lvl',$i).')';
				if ($rq2 != "")
					$rq1.=", ".$this->getStr('bld_title_'.lcfirst($rq2)).' ('.$this->valueForID('requirement2.lvl',$i).')';
			}
			$this->setTmplParam('require'.$i,preg_replace('/Headquarters/',"HQ",$rq1),FALSE);
		}
		$this->processDamageModifiers();
		
		return $this->chgCount;
	}
	
}
?>