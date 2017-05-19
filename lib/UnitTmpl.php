<?php

require_once('WikiLeveledTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */

/*
  WikiUnitTmpl:
	Base class for WikiTemplate filling with unit data (i.e. troops/vehicles/starships)
*/


class UnitTmpl extends WikiLeveledTmpl{
	protected $projectileData;
	
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->projectileData = $jdb['base']->getProjectileData();
	}
	
	protected function processDamageModifiers($paramPrefix="dmod_",$prefix="projectile.") {
		if ($paramPrefix == "dmod_")
			$this->setTmplParam('splash',$this->paramNTo1($prefix.'splashDamagePercentages'));
		else
			$this->setTmplParam($paramPrefix.'splash',$this->paramNTo1($prefix.'splashDamagePercentages'));

		$this->setTmplParam($paramPrefix.'widthsegments',$this->paramNTo1($prefix.'widthSegments'),TRUE);
		$this->setTmplParam($paramPrefix.'projectilename',$this->paramNTo1($prefix.'name'));
		$this->setTmplParam($paramPrefix.'building',$this->paramNTo1($prefix.'building'));
		$this->setTmplParam($paramPrefix.'hq',$this->paramNTo1($prefix.'HQ'));
		$this->setTmplParam($paramPrefix.'bruiserinfantry',$this->paramNTo1($prefix.'bruiserInfantry'));
		$this->setTmplParam($paramPrefix.'bruiservehicle',$this->paramNTo1($prefix.'bruiserVehicle'));
		$this->setTmplParam($paramPrefix.'droideka',$this->paramNTo1($prefix.'champion'));
		$this->setTmplParam($paramPrefix.'flierinfantry',$this->paramNTo1($prefix.'flierInfantry'));
		$this->setTmplParam($paramPrefix.'healerinfantry',$this->paramNTo1($prefix.'healerInfantry'));
		$this->setTmplParam($paramPrefix.'herobruiserinfantry',$this->paramNTo1($prefix.'heroBruiserInfantry'));
		$this->setTmplParam($paramPrefix.'herobruiservehicle',$this->paramNTo1($prefix.'heroBruiserVehicle'));
		$this->setTmplParam($paramPrefix.'heroinfantry',$this->paramNTo1($prefix.'heroInfantry'));
		$this->setTmplParam($paramPrefix.'herovehicle',$this->paramNTo1($prefix.'heroVehicle'));
		$this->setTmplParam($paramPrefix.'infantry',$this->paramNTo1($prefix.'infantry'));
		$this->setTmplParam($paramPrefix.'resource',$this->paramNTo1($prefix.'resource'));
		$this->setTmplParam($paramPrefix.'shield',$this->paramNTo1($prefix.'shield'));
		$this->setTmplParam($paramPrefix.'shieldgenerator',$this->paramNTo1($prefix.'shieldGenerator'));
		$this->setTmplParam($paramPrefix.'storage',$this->paramNTo1($prefix.'storage'));
		$this->setTmplParam($paramPrefix.'trap',$this->paramNTo1($prefix.'trap'));
		$this->setTmplParam($paramPrefix.'turret',$this->paramNTo1($prefix.'turret'));
		$this->setTmplParam($paramPrefix.'vehicle',$this->paramNTo1($prefix.'vehicle'));
		$this->setTmplParam($paramPrefix.'wall',$this->paramNTo1($prefix.'wall'));
	}
}

?>