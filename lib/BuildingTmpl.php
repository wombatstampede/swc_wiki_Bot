<?php

require_once('WikiLeveledTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */

/*
  BuildingTmpl:
	Base class for WikiTemplate filling with building data (except turrets & traps)
*/


class BuildingTmpl extends WikiLeveledTmpl{
	CONST DEFAULT_TEMPLATE='Building';
	CONST TEMPLATE_MATCH = '/{{\s*Building/';

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_INVALID_BUILDINGID = '/Locked$/';
	CONST FILTER_FORCE_UID = '/^(smugglerDefenseGenerator5|smugglerMiningComplex5|smugglerMunitionsPlant5|smugglerOrganicsLab5|smugglerStarshipWeaponsDepot5|smugglerWeaponsFactory5)$/';
	CONST FILTER_INVALID_ARMOR = '/^turret$/';
	CONST FILTER_INVALID_UID = '/^forest|^story|^empire_ab_|^rebel_ab_|Turret|Trap|^blocker|^junk|^deco|^crystalRock|^empireIdle|ShuttleIdle|rebelph|^rebelRubble/';
	
	const APPEND_FO_HQ = " HQ";
	
	//map Factory Outpost titles to string entries (they have no "title", yet)
	protected $mapFOTitleStr = array (
		"empireOrganicsLab" => array("BUFF_BASE_NAME_HEALTH_REGENERATION","BUFF_DESC_HEALTH_REGENERATION"),
		"empireMunitionsPlant" => array("BUFF_BASE_NAME_INFANTRY_DAMAGE","BUFF_DESC_INFANTRY_DAMAGE"),
		"empireMiningComplex" => array("BUFF_BASE_NAME_MAX_HEALTH","BUFF_DESC_MAX_HEALTH"),
		"empireDefenseGenerator" => array("BUFF_BASE_NAME_SHIELD_REGENERATION","BUFF_DESC_SHIELD_REGENERATION"),
		"empireStarshipWeaponsDepot" => array("BUFF_BASE_NAME_SPECIAL_ATTACK_DAMAGE","BUFF_DESC_SPECIAL_ATTACK_DAMAGE"),
		"empireWeaponsFactory" => array("BUFF_BASE_NAME_VEHICLE_DAMAGE","BUFF_DESC_VEHICLE_DAMAGE"),
		"rebelOrganicsLab" => array("BUFF_BASE_NAME_HEALTH_REGENERATION","BUFF_DESC_HEALTH_REGENERATION"),
		"rebelMunitionsPlant" => array("BUFF_BASE_NAME_INFANTRY_DAMAGE","BUFF_DESC_INFANTRY_DAMAGE"),
		"rebelMiningComplex" => array("BUFF_BASE_NAME_MAX_HEALTH","BUFF_DESC_MAX_HEALTH"),
		"rebelDefenseGenerator" => array("BUFF_BASE_NAME_SHIELD_REGENERATION","BUFF_DESC_SHIELD_REGENERATION"),
		"rebelStarshipWeaponsDepot" => array("BUFF_BASE_NAME_SPECIAL_ATTACK_DAMAGE","BUFF_DESC_SPECIAL_ATTACK_DAMAGE"),
		"rebelWeaponsFactory" => array("BUFF_BASE_NAME_VEHICLE_DAMAGE","BUFF_DESC_VEHICLE_DAMAGE"),
		"smugglerOrganicsLab" => array("BUFF_BASE_NAME_HEALTH_REGENERATION","BUFF_DESC_HEALTH_REGENERATION"),
		"smugglerMunitionsPlant" => array("BUFF_BASE_NAME_INFANTRY_DAMAGE","BUFF_DESC_INFANTRY_DAMAGE"),
		"smugglerMiningComplex" => array("BUFF_BASE_NAME_MAX_HEALTH","BUFF_DESC_MAX_HEALTH"),
		"smugglerDefenseGenerator" => array("BUFF_BASE_NAME_SHIELD_REGENERATION","BUFF_DESC_SHIELD_REGENERATION"),
		"smugglerStarshipWeaponsDepot" => array("BUFF_BASE_NAME_SPECIAL_ATTACK_DAMAGE","BUFF_DESC_SPECIAL_ATTACK_DAMAGE"),
		"smugglerWeaponsFactory" => array("BUFF_BASE_NAME_VEHICLE_DAMAGE","BUFF_DESC_VEHICLE_DAMAGE")
		);
	
	protected $buildingData;
	protected $gameConstants;
	
	protected $cfgShieldHealth;
	protected $cfgShieldRange;
	

	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->buildingData = $jdb['base']->getBuildingData();
		$this->gameConstants = $jdb['base']->getGameConstants();
		
		$this->cfgShieldHealth = explode(" ",$this->gameConstants['shield_health_per_point']->{'value'});
		$this->cfgShieldRange = explode(" ",$this->gameConstants['shield_range_per_point']->{'value'});
		
		$this->template_params.="
  | type = 
  | armortype =
";
  //optional: shieldrange1-10, shieldhealth1-10
  //         | activationradius =
  //		produce1-10, storage1-10
	}
//subType: OutpostHQ (not all levels are used)

	//filter records
	public static function validRec($rec) {
		global $cfg;
		return  (preg_match($cfg['match_building_re'],$rec->{'uid'}) === 1) &&
				(preg_match(self::FILTER_INVALID_BUILDINGID,$rec->{'buildingID'}) !== 1) &&
				((preg_match(self::FILTER_FORCE_UID,$rec->{'uid'}) === 1) ||
				 ((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) &&
				  (preg_match(self::FILTER_INVALID_ARMOR,$rec->{'armorType'}) !== 1) &&
				  (preg_match(self::FILTER_INVALID_UID,$rec->{'uid'}) !== 1)
				 ));
	}
	
//gameconstants:
//shield_health_per_point	Rule	5000 10000 20000 30000 40000 50000 63300 73600 83900 94500 100000 110000 120000 130000 140000 150000 160000 170000 180000 190000 200000
//                          Pts     0    1 ...
//shield_range_per_point	Rule	2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22
//                          Pts     1 2 3 ...
	protected function getShieldHealth($rec) {
		if (property_exists($rec,'shieldHealthPoints') &&
			($rec->{'shieldHealthPoints'} !== ""))
			return $this->cfgShieldHealth[$rec->{'shieldHealthPoints'}];
		else
			return "";
	}
	
	protected function getShieldRange($rec) {
		if (property_exists($rec,'shieldRangePoints') &&
			($rec->{'shieldRangePoints'} !== ""))
			return $this->cfgShieldRange[$rec->{'shieldRangePoints'}];
		else
			return "";
	}


	protected function getID() {
		return $this->defaultRec()->{'buildingID'};
	}
	
	protected function getTitleStrKey() {
		return 'bld_title_'.$this->getID();
	}

	public function getTitle() {
		if (array_key_exists($this->getID(),$this->mapFOTitleStr)) {
			$title = $this->getStr($this->mapFOTitleStr[$this->getID()][0]).self::APPEND_FO_HQ;
			$this->checkAmbiguousTitle($this->getID(),$title);
			return $title;
		}
		//else
		return parent::getTitle();
	}
	
	public function getDescription() {
		if (array_key_exists($this->getID(),$this->mapFOTitleStr)) {
			$title = $this->getStr($this->mapFOTitleStr[$this->getID()][1]);
			return $title;
		}
		//else
		return parent::getDescription();
	}
	
	protected function getDescriptionStrKey() {
		return 'bld_desc_'.$this->getID();
	}
	
	protected function checkAmbiguousTitle($key,&$title) {
		$title.=" (".ucfirst($this->defaultRec()->{'faction'}).")";
	}
	
	//eventually handle event or unlockable buildings (with no costs)
	protected function currencyBuild() {
		$rec=$this->recs[$this->maxLvl];//highest lvl record
		if (property_exists($rec,'credits') &&
			($rec->{'credits'} > "0"))
			return "credits";
		elseif (property_exists($rec,'materials') &&
			($rec->{'materials'} > "0"))
			return "alloy";
		elseif (property_exists($rec,'contraband') &&
			($rec->{'contraband'} > "0"))
			return "contraband";
		else
			return "";
	}


	//checks ID for syntax "projectile.id" and other linked tables and returns linked table value in case
	//behaves a bit like a sql join
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$rec = $this->recs[$lvl];
		
		if (preg_match('/^requirement([1-2])\.(.*)$/',$id,$reID)) {//requirement1-requirement2
			if ((property_exists($rec,'requirements') && ($reID[1]==1) &&
				 array_key_exists(0,$rec->{'requirements'}))) {
				if (array_key_exists($rec->{'requirements'}[0],$this->buildingData)) {
					$rec = $this->buildingData[$rec->{'requirements'}[0]];
					$id = $reID[2];
				}
				else $rec = null;
			}
			elseif ((property_exists($rec,'requirements2') && ($reID[1]==2))) {
				if (array_key_exists($rec->{'requirements2'},$this->buildingData)) {
					$rec = $this->buildingData[$rec->{'requirements2'}];
					$id = $reID[2];
				}
				else $rec = null;
			}
			else $rec = null;
		}
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

		//special case, smuggler FO, show only lv 1
		if (($this->fmt($rec,'subType') == "OutpostHQ") &&
			($this->fmt($rec,'faction') == "smuggler")) {
			$this->maxLvl=1;
		}
		
		$this->setTmplParam('unitID',$this->getID());
		$this->setTmplParam('title',$this->getTitle());
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));
		
		//maybe paramNTo1 value?
		$this->setTmplParam('type',$this->fmt($rec,'type'));
		$this->setTmplParam('armortype',$this->armorType());

		$this->setTmplParam('buildcurrency',$this->currencyBuild(),TRUE);
		$this->setTmplParam('producecurrency',preg_replace('/Materials/',"Alloy",$this->fmt($rec,'currency')),TRUE);
		$this->setTmplParam('cycletime',$this->paramNTo1('cycleTime'),TRUE);//resource producing interval

		if (preg_match('/Trap|Squad|Platform/',$this->getID()) === 1)//currently write radius only for droideka platforms and squad centers
			$this->setTmplParam('activationradius', $this->paramNTo1('activationRadius'),TRUE);
		else
			$this->setTmplParam('activationradius','',TRUE);
		
		$this->setTmplParam('size',$this->fmt($rec,'sizex')." x ".$this->fmt($rec,'sizey'));

		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			//!! updating image is temporary !!
			//get image level-id from count at end of assetName (not every building level has own image)
			/*if (preg_match('/up([0-9]+)/',$rec->{'assetName'},$lvlMatch) === 1)
				$this->setTmplParam('image'.$i,$this->getTitle()." l".$lvlMatch[1].".png");
			else
				if (preg_match('/Cantina|Droid Hut/',$this->getTitle()) !== 1)
					$this->setTmplParam('image'.$i,$this->getTitle().".png");
				else
					$this->setTmplParam('image'.$i,$key = $this->getStr($this->getTitleStrKey()).".png");*/

			$this->setTmplParam('time'.$i,$this->fmt($rec,'time'));
			
			switch ($this->currencyBuild()) {
				case 'credits':
					$this->setTmplParam('cost'.$i,$this->fmt($rec,'credits'));
					break;
				case 'contraband':
					$this->setTmplParam('cost'.$i,$this->fmt($rec,'contraband'));
					break;
				case 'alloy':
					$this->setTmplParam('cost'.$i,$this->fmt($rec,'materials'));
					break;
				default:
					$this->setTmplParam('cost'.$i,'');
			}
			
			$this->setTmplParam('maxquantity'.$i, $this->fmt($rec,'maxQuantity'),TRUE);
			$this->setTmplParam('health'.$i,$this->fmt($rec,'health'));
			$this->setTmplParam('xp'.$i,$this->fmt($rec,'xp'));
			
			$this->setTmplParam('produce'.$i,($this->fmt($rec,'produce')!="0")?$this->fmt($rec,'produce'):"",TRUE);
			$this->setTmplParam('storage'.$i,($this->fmt($rec,'storage')!="0")?$this->fmt($rec,'storage'):"",FALSE);
			
			$rq1=$this->valueForID('requirement1.buildingID',$i,FALSE);
			$rq2=$this->valueForID('requirement2.buildingID',$i,FALSE);
			if ($rq1 != "") {
				$rq1=$this->getStr('bld_title_'.lcfirst($rq1)).' ('.$this->valueForID('requirement1.lvl',$i).')';
				if ($rq2 != "")
					$rq1.=", ".$this->getStr('bld_title_'.lcfirst($rq2)).' ('.$this->valueForID('requirement2.lvl',$i).')';
			}
			$this->setTmplParam('require'.$i,preg_replace('/Headquarters/',"HQ",$rq1),FALSE);
			
			$this->setTmplParam('shieldhealth'.$i,$this->getShieldHealth($rec),TRUE);
			$this->setTmplParam('shieldrange'.$i,$this->getShieldRange($rec),TRUE);
			
		}
		
		return $this->chgCount;
	}	
}

?>