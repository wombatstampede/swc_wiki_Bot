<?php

//require_once('..\\config\\swc_wiki_config.php');

require_once('TroopTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  TroopTmpl:
	Class for WikiTemplate filling with Troop unit data
*/


class MercenaryTmpl extends TroopTmpl{
	CONST DEFAULT_TEMPLATE='TroopMercenary';

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_VALID_TYPE    = '/mercenary/';
	//CONST FILTER_INVALID_UNIT  = '//';

	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		//$this->template_params.="";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		//echo $rec->{'unitID'};
		return ((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) && 
				(preg_match(self::FILTER_VALID_TYPE,$rec->{'type'}) === 1) &&
//				(preg_match(self::FILTER_INVALID_UNIT,$rec->{'unitID'}) !==1) &&
				(preg_match($cfg['match_mercenaries_re'],$rec->{'unitID'}) === 1)) ;
	}

	
	//fill in parameter values into template
	public function process() {
		//fill in template
		
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('upgradecurrency',$this->currencyUpgrade(),TRUE);
		$this->setTmplParam('trainingcurrency',$this->currencyCost(),TRUE);
		
		//temporary for initializing
		//$this->setTmplParam('image',$this->getStr($this->getTitleStrKey()).".png",TRUE);
		
		//echo ($this->minLvl."-".$this->maxLvl."\n");
		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			if ($this->hasFinalStrike())
				$this->setTmplParam('deathDamage'.$i,$this->fmt($rec,'deathProjectileDamage'));
			
			$rq1=$this->valueForID('requirement1.buildingID',$i,FALSE);
			if ($rq1 != "")
				$this->setTmplParam('require'.$i,$this->getStr('bld_title_'.lcfirst($rq1)).' ('.$this->valueForID('requirement1.lvl',$i).')');
			
		}
		//$this->processDamageModifiers();
		if ($this->hasFinalStrike() &&
			array_key_exists($rec->{'deathProjectile'},$this->projectileData))
			$this->processDamageModifiers('dt_dmod_','deathProjectile.');
		
		return $this->chgCount;
	}
	
}
?>