<?php

require_once('UnitTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  StarshipTmpl:
	Class for WikiTemplate filling with Starship unit data
*/

class StarshipTmpl extends UnitTmpl{
	CONST DEFAULT_TEMPLATE='Starship';
	CONST TEMPLATE_MATCH = '/{{\s*Starship/'; //default matches to Starship*

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_INVALID_UNIT = '/Trap|RedDot|Stolen|Seized|Corellian|Gozanti/';

	//baseTable in recs -> SpecialAttackData
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		$this->template_params.="
  | type = 
  | required = 
  | class = 
  | damtype = 
  | favorite = 
  | splash = 
  | capacity = 
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
  | training1 = 
  | training2 = 
  | training3 = 
  | training4 = 
  | training5 = 
  | training6 = 
  | training7 = 
  | training8 = 
  | training9 = 
  | training10 = 
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
  | upcost2 = 
  | upcost3 = 
  | upcost4 = 
  | upcost5 = 
  | upcost6 = 
  | upcost7 = 
  | upcost8 = 
  | upcost9 = 
  | upcost10 = 
  | uptime2 = 
  | uptime3 = 
  | uptime4 = 
  | uptime5 = 
  | uptime6 = 
  | uptime7 = 
  | uptime8 = 
  | uptime9 = 
  | uptime10 = ";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		return ((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) && 
				(preg_match(self::FILTER_INVALID_UNIT,$rec->{'specialAttackID'}) !== 1) &&
				(preg_match($cfg['match_ship_re'],$rec->{'specialAttackID'}) === 1));
	}
	
	protected function getID() {
		return $this->defaultRec()->{'specialAttackID'};
	}
	
	protected function getTitleStrKey() {
		return 'shp_title_'.$this->getID();
	}

	protected function getDescriptionStrKey() {
		return 'shp_desc_'.$this->getID();
	}
	
	protected function checkAmbiguousTitle($key,&$title) {
		if (preg_match('/Freighter/',$key)) {
			if (strpos($key,'Rebel'))
				$title.=" (Rebel)";
			elseif (strpos($key,'Empire'))
				$title.=" (Empire)";
			else 
				$title.="";
		}
		if (preg_match('/Sample/',$key)) //not yet
			$title.=" (Sample)";
	}
	
	//event/promo unit?
	public function isEvent() {
		if (preg_match('/^(0[a-z]*|)$/i',$this->fmt($this->defaultRec(),'credits')) &&
			preg_match('/^(0[a-z]*|)$/i',$this->fmt($this->defaultRec(),'contraband')))
			return TRUE;
		else
			return FALSE;
	}
	
	public function isUnlockable() {
		if (($this->fmt($this->defaultRec(),'unlockPlanet')!="") ||
			($this->fmt($this->defaultRec(),'upgradeShards')!=""))
			return TRUE;
		else
			return FALSE;
	}
	
	protected function currencyCost() {
		if ($this->isEvent() || preg_match('/^Freighter/i',$this->getID()))
			return '';
		else {
			return "credits";
		}
	}

	protected function currencyUpgrade() {
		if ($this->isEvent() || $this->isUnlockable() || preg_match('/^Freighter/i',$this->getID())) {
			if ($this->fmt($this->defaultRec(),'upgradeShards')!="")
				return "data fragments";
			else
				return '';
		} else {
			return "credits";
		}
	}
	
	
	
	//checks ID for syntax "projectile.id" and returns linked table value in case
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$rec = $this->recs[$lvl];
		
		if (preg_match('/^projectile\.(.*)$/',$id,$pjID)) {
			if (array_key_exists($rec->{'projectileType'},$this->projectileData)) {
				$rec = $this->projectileData[$rec->{'projectileType'}];
				$id = $pjID[1];
			}
			else
				$rec = null;
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
		//fill in template
		
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('unitID',$this->getID());
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));
		$this->setTmplParam('favorite',$this->fmt($rec,'favoriteTargetType'));
		
		//$this->setTmplParam('type','');
		//$this->setTmplParam('class','');
	
		$this->setTmplParam('capacity',$this->fmt($rec,'size'));
		//$this->setTmplParam('move',$this->paramNTo1('maxSpeed'));
		
		if ($this->fmt($rec,'unlockPlanet') != "")
			$this->setTmplParam('required',$this->getStr($rec->{'unlockPlanet'}));

		if (property_exists($rec,'infoUIType') && preg_match('/healer/i',$rec->{'infoUIType'}))
			$this->setTmplParam('healer','healer');
		else
			$this->setTmplParam('healer','');

		//no event aircraft, yet
/*		if ($this->isEvent())
			$this->setTmplParam('event','event');
		else
			$this->setTmplParam('event','',TRUE);*/
		
		if ($this->isUnlockable())
			$this->setTmplParam('unlockable','unlockable');
		else
			$this->setTmplParam('unlockable','',TRUE);

		$this->setTmplParam('upgradecurrency',$this->currencyUpgrade(),TRUE);
		$this->setTmplParam('trainingcurrency',$this->currencyCost(),TRUE);
		//echo ($this->minLvl."-".$this->maxLvl."\n");
		
		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];

			if (property_exists($rec,'unitCount') && preg_match('/[0-9]/',$rec->{'unitCount'})) {
				//unitCount -> dropship, no damage
				$this->setTmplParam('damage'.$i,'',TRUE);
				$this->setTmplParam('unitcount'.$i,$this->fmt($rec,'unitCount'),TRUE);
			}
			else {
				$this->setTmplParam('damage'.$i,$this->fmt($rec,'dps'));
			}
			if ($this->currencyCost() != "") {
				$this->setTmplParam('training'.$i,$this->fmt($rec,'trainingTime'));
				$this->setTmplParam('cost'.$i,$this->fmt($rec,'credits'));
			} else {
				$this->setTmplParam('training'.$i,'');
				$this->setTmplParam('cost'.$i,'');
			}
//			if (!preg_match('/AWing|TieAdvanced|HWK290|VT49/i',$this->getID())) {
			if ($this->currencyUpgrade() == "credits") {
				$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeCredits'));
				$this->setTmplParam('uptime'.$i,$this->fmt($rec,'upgradeTime'));
			} elseif ($this->currencyUpgrade() == "data fragments") {
				$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeShards'));
				$this->setTmplParam('uptime'.$i,$this->fmt($rec,'upgradeTime'));
			} else {
				$this->setTmplParam('upcost'.$i,'');
				$this->setTmplParam('uptime'.$i,'');
			}
		}
		$this->processDamageModifiers();
		
		return $this->chgCount;
	}
	
}
?>