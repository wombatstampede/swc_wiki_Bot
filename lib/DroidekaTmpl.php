<?php

//require_once('..\\config\\swc_wiki_config.php');

require_once('TroopTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  DroidekaTmpl:
	Class for WikiTemplate filling with Droideka unit data
*/


class DroidekaTmpl extends TroopTmpl{
	CONST DEFAULT_TEMPLATE='TroopDroideka';

	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_VALID_ARMORTYPE    = '/champion/';
	//CONST FILTER_INVALID_UNIT  = '//';
	
	protected $linkedBuildingData; //buildingdata indexed via the linkedUnit attribute

	
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->linkedBuildingData = $jdb['base']->getBuildingDataByLinkedUnit();
		//$this->template_params.="";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		//echo $rec->{'unitID'};
		return ((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) && 
				(preg_match(self::FILTER_VALID_ARMORTYPE,$rec->{'armorType'}) === 1) &&
				//(preg_match(self::FILTER_INVALID_UNIT,$rec->{'unitID'}) !==1) &&
				(preg_match($cfg['match_droidekas_re'],$rec->{'unitID'}) === 1)) ;
	}

	protected function currencyUpgrade() {
		if ($this->valueForID('linkedBuilding.contraband',$this->minLvl,FALSE)>0)
			return "contraband";
		elseif ($this->valueForID('linkedBuilding.credits',$this->minLvl,FALSE)>0)
			return "credits";
		elseif ($this->valueForID('linkedBuilding.materials',$this->minLvl,FALSE)>0)
			return "alloy";
		
		return "";
	}

	//checks ID for syntax "projectile.id" and other linked tables and returns linked table value in case
	//behaves a bit like a sql join
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$rec = $this->recs[$lvl];
		
		if (preg_match('/^linkedBuilding\.(.*)$/',$id,$lbID)) {
			//echo "id: $id, lbID: ".$lbID[1]." exists: ".array_key_exists($rec->{'uid'},$this->linkedBuildingData)."\n";
			if (array_key_exists($rec->{'uid'},$this->linkedBuildingData)) {
				$rec = $this->linkedBuildingData[$rec->{'uid'}];
				$id = $lbID[1];
			}
			else $rec = null;
			
			if ($rec !== null)
				if (!$fmt && !property_exists($rec,$id))
					return "";
				else
					return $fmt?$this->fmt($rec,$id):$rec->{$id};
		}
		else
			return parent::valueForID($id,$lvl,$fmt);
	}
	
	
	//fill in parameter values into template
	public function process() {
		//fill in template
		
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('activationradius', $this->paramNTo1('linkedBuilding.activationRadius'),TRUE);
		$this->setTmplParam('moverun',$this->paramNTo1('runSpeed'));

		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			$this->setTmplParam('shieldhealth'.$i,$this->fmt($rec,'shieldHealth'));
			
			if (!$this->isEvent()) {
				$this->setTmplParam('uptime'.$i,$this->valueForID('linkedBuilding.time',$i),TRUE);
			} else {
				$this->setTmplParam('uptime'.$i,'',TRUE);
			}

			
			switch ($this->currencyUpgrade()) {
				case 'credits':
					$this->setTmplParam('upcost'.$i,$this->valueForID('linkedBuilding.credits',$i),TRUE);
					break;
				case 'contraband':
					$this->setTmplParam('upcost'.$i,$this->valueForID('linkedBuilding.contraband',$i),TRUE);
					break;
				case 'alloy':
					$this->setTmplParam('upcost'.$i,$this->valueForID('linkedBuilding.alloy',$i),TRUE);
					break;
				default:
					$this->setTmplParam('upcost'.$i,'',TRUE);
			}
			
			$rq1=$this->valueForID('requirement1.buildingID',$i,FALSE);
			if ($rq1 != "")
				$this->setTmplParam('require'.$i,preg_replace('/Headquarters/i','HQ',$this->getStr('bld_title_'.lcfirst($rq1))).' ('.$this->valueForID('requirement1.lvl',$i).')',TRUE);
			
		}
		
		return $this->chgCount;
	}
	
}
?>