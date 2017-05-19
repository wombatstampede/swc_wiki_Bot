<?php

require_once('UnitTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  TurretTmpl:
	Class for WikiTemplate filling with Turret unit data
	
  !Important!
  Expects records from BuildingData Table
*/

class TurretTmpl extends UnitTmpl{
	CONST DEFAULT_TEMPLATE='Turret';
	CONST TEMPLATE_MATCH = '/{{\s*Turret/'; //default matches to Starship*

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_VALID_ARMOR = '/^turret$/';
	CONST FILTER_INVALID_UID = '/^empire_ab_|^rebel_ab_/';
	
	protected $turretData;

	
	//baseTable in recs -> BuildingData
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		$this->turretData = $jdb['base']->getTurretData();
		$this->template_params.="
  | type = 
  | armortype = 
  | rangemin = 
  | rangemax = 
  | favorite = 
  | buildcurrency = alloy
  | switchcurrency = alloy
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
  | image1 =
  | image2 =
  | image3 =
  | image4 =
  | image5 =
  | image6 =
  | image7 =
  | image8 =
  | image9 =
  | image10 =
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
  | switch1 = 
  | switch2 = 
  | switch3 = 
  | switch4 = 
  | switch5 = 
  | switch6 = 
  | switch7 = 
  | switch8 = 
  | switch9 = 
  | switch10 = 
  | switchtime1 = 
  | switchtime2 = 
  | switchtime3 = 
  | switchtime4 = 
  | switchtime5 = 
  | switchtime6 = 
  | switchtime7 = 
  | switchtime8 = 
  | switchtime9 = 
  | switchtime10 = 
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
				(preg_match(self::FILTER_INVALID_UID,$rec->{'uid'}) !== 1) &&
				(preg_match($cfg['match_turret_re'],$rec->{'uid'}) === 1));
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
		$title.=" (".ucfirst($this->defaultRec()->{'faction'}).")";
	}
	
	protected function currencyBuild() {
		return "alloy";
	}

	protected function currencySwitch() {
		return "alloy";
	}
	

	//checks ID for syntax "projectile.id" and returns linked table value in case
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		if (preg_match('/^(projectile|turret)\.(.*)$/',$id,$fldID)) {
			$rec = null;
			if (array_key_exists($this->recs[$lvl]->{'turretId'},$this->turretData)) {
				$tuRec=$this->turretData[$this->recs[$lvl]->{'turretId'}];
				
				if ($fldID[1] == "turret") {
					$rec = $tuRec;
					$id = $fldID[2];
				}
				
				if (($fldID[1] == "projectile") &&
					array_key_exists($tuRec->{'projectileType'},$this->projectileData)) {
					$rec = $this->projectileData[$tuRec->{'projectileType'}];
					$id = $fldID[2];
				}
			}
		}
		else
			$rec = $this->recs[$lvl];
		
		if ($rec !== null)
			if (!$fmt && !property_exists($rec,$id))
				return "";
			else
				return $fmt?$this->fmt($rec,$id):$rec->{$id};
		else
			return "";//todo: maybe formatting for empty values?
	}
	
	
	//fill in parameter values into template
	public function process() {
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('unitID',$this->getID());
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));
		
		//maybe paramNTo1 value?
		$this->setTmplParam('favorite',$this->getStr('target_pref_'.
							lcfirst($this->valueForID('turret.favoriteTargetType',$this->maxLvl))));
		
		$this->setTmplParam('type',$this->fmt($rec,'type'));
		$this->setTmplParam('armortype',$this->armorType());

		$this->setTmplParam('buildcurrency',$this->currencyBuild(),TRUE);
		$this->setTmplParam('switchcurrency',$this->currencySwitch(),TRUE);

		$this->setTmplParam('rangemin',$this->valueForID('turret.minAttackRange',$this->maxLvl));
		$this->setTmplParam('rangemax',$this->valueForID('turret.maxAttackRange',$this->maxLvl));


		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			//get image level-id from count at end of assetName (not every building level has own image)
			if (preg_match('/([0-9]+)$/',$rec->{'assetName'},$lvlMatch) == 1)
				$this->setTmplParam('image'.$i,$this->getTitle()." l".$lvlMatch[1].".png");
			else
				$this->setTmplParam('image'.$i,$this->getTitle().".png");
			
			if (!preg_match('/sonic/i',$rec->{'uid'}))
				$this->setTmplParam('damage'.$i,$this->valueForID('turret.dps',$i));
			else
				$this->setTmplParam('damage'.$i,$this->valueForID('projectile.beamDamage',$i));
			$this->setTmplParam('time'.$i,$this->fmt($rec,'time'));
			$this->setTmplParam('switchtime'.$i,$this->fmt($rec,'crossTime'));
			$this->setTmplParam('cost'.$i,$this->fmt($rec,'materials'));
			$this->setTmplParam('switch'.$i,$this->fmt($rec,'crossMaterials'));
			$this->setTmplParam('health'.$i,$this->fmt($rec,'health'));
			$this->setTmplParam('xp'.$i,$this->fmt($rec,'xp'));
		}
		$this->processDamageModifiers();
		
		return $this->chgCount;
	}
	
}
?>