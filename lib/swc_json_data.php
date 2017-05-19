<?php
/*
	JSON Data Handling for SWC Wiki Bot
*/

class JsonData {
	protected $data;
	
	function __construct($jsonUrl) {
		$sbase = file_get_contents($jsonUrl);
		$sbase = utf8_encode($sbase);
		$this->data = json_decode($sbase);
	}
	
	protected function getTabFromData($tabName) {
		return $this->data->{'content'}->{'objects'}->{$tabName};
	}
	
	protected function buildTabArrByAttr($tabName,$keyattr,$checkForEmptyKeys = FALSE) {
		$tabArr = array(); //build data array
		$dataTab = $this->getTabFromData($tabName);
		if (!is_array($keyattr))
			foreach($dataTab as $k => $v) {
				if (!$checkForEmptyKeys || (property_exists($v,$keyattr) && ($keyattr!="")))
					$tabArr[$v->{$keyattr}] = $v;
			}
		else { //build data array with combined key
			foreach($dataTab as $k => $v) {
				$keyval="";
				foreach($keyattr as $kak => $kav) 
					if (!$checkForEmptyKeys || property_exists($v,$kav))
						$keyval.= $v->{$kav};
				//echo "$keyval,";
				if ($keyval != "")
					$tabArr[$keyval] = $v;
			}
		}
		return $tabArr;
			//todo: filter relevant records: (trp_desc_ trp_title_)
			//filter func: if (substr($v->{'uid'},0,4) == 'trp_')
	}
	
	protected function sortTabArrByAttr(&$tab,$attr) {
		uasort($tab,
			function ($a, $b) use ($attr) {
				if ($a->{$attr} == $b->{$attr})
					return 0;
				return ($a->{$attr} < $b->{$attr}) ? -1 : 1;
			}
		);
	}
	
	//sort table by uid+level (uid alphanum, level numeric)
	protected function sortTabArrByUidLvl(&$tab,$attrUid,$attrLvl) {
		uasort($tab,
			function ($a, $b) use ($attrUid,$attrLvl) {
				if ($a->{$attrUid} == $b->{$attrUid})
					return 0;
				//not optimized for speed ;-)
				if (array_key_exists($attrLvl,$a) && array_key_exists($attrLvl,$b) &&
					(preg_match('/^(.*)('.$a->{$attrLvl}.')$/',$a->{$attrUid},$splituidA)==1) &&
					(preg_match('/^(.*)('.$b->{$attrLvl}.')$/',$b->{$attrUid},$splituidB)==1)) {
					//echo 'a:'.$attrUid.":".$a->{$attrUid}."/".$attrLvl.'/'.$a->{$attrLvl}."/split:".$splituidA[1]."\n";
					//echo intval($a->{$attrLvl})."/".intval($b->{$attrLvl})."->".(intval($a->{$attrLvl}) < intval($b->{$attrLvl}))."\n";
					if ($splituidA[1] == $splituidB[1])//same basic uid -> compare levels						
						return (intval($a->{$attrLvl}) < intval($b->{$attrLvl})) ? -1 : 1;
					else
						return ($a->{$attrUid} < $b->{$attrUid}) ? -1 : 1;
				} else
					return ($a->{$attrUid} < $b->{$attrUid}) ? -1 : 1;
			}
		);
	}
	
}

/* -------------------------------------- */

class JsonVersionData extends JsonData {
	protected $version;
	
	function getVersion() {
		return $this->data->{'version'};
	}
}


/* -------------------------------------- */

class JsonStringData extends JsonData {
	protected $localizedStrings;
	
/*	function __construct($jsonUrl) {
		parent::__construct($jsonUrl);
		
	}*/
	
	function getLocalizedStrings() {
		if (!isset($this->localizedStrings))
			$this->localizedStrings = $this->buildTabArrByAttr('LocalizedStrings','uid');
		return $this->localizedStrings;
	}
}

/* -------------------------------------- */

class JsonBaseData extends JsonData{
	protected $gameConstants;
	protected $trapData;
	protected $troopData;
	protected $troopDataByIDLvl;
	protected $projectileData;
	protected $specialAttackData;
	protected $heroAbilities;
	protected $buildingData;
	protected $buildingDataByLinkedUnit;
	protected $buildingDataByIDLvl;
	protected $turretData;
	protected $equipmentData;
	protected $equipmentEffectData;
	protected $skinOverrideData;
	protected $buffData;
	protected $planetData;
	
	function getTrapData() {
		if (!isset($this->trapData)) {
			$this->trapData = $this->buildTabArrByAttr('TrapData','uid');
			//no sort needed, trapdata is not iterated (iteration is done via buildingData)
		}
		return $this->trapData;
	}
	
	function getTroopData() {
		if (!isset($this->troopData)) {
			$this->troopData = $this->buildTabArrByAttr('TroopData','uid');
			//sort troopData by uid, required for iteration & group change
			$this->sortTabArrByAttr($this->troopData,'uid');
		}
		return $this->troopData;
	}
	
	//combined index unitID+Lvl
	function getTroopDataByIDLvl() {
		if (!isset($this->troopDataByIDLvl)) {
			$this->troopDataByIDLvl = $this->buildTabArrByAttr('TroopData',['unitID','lvl'],TRUE);
		}
		return $this->troopDataByIDLvl;
	}

	function getSpecialAttackData() {
		if (!isset($this->specialAttackData)) {
			$this->specialAttackData = $this->buildTabArrByAttr('SpecialAttackData','uid');
			//sort troopData by uid, required for iteration & group change
			$this->sortTabArrByAttr($this->specialAttackData,'uid');
		}
		return $this->specialAttackData;
	}

	function getHeroAbilities() {
		if (!isset($this->heroAbilities)) {
			$this->heroAbilities = $this->buildTabArrByAttr('HeroAbilities','uid');
			$this->sortTabArrByAttr($this->heroAbilities,'uid');
		}
		return $this->heroAbilities;
	}
	
	function getBuildingData() {
		if (!isset($this->buildingData)) {
			$this->buildingData = $this->buildTabArrByAttr('BuildingData','uid');
			$this->sortTabArrByUidLvl($this->buildingData,'uid','lvl');
			//$this->sortTabArrByAttr($this->buildingData,'uid');
		}
		return $this->buildingData;
	}

	function getBuildingDataByLinkedUnit() {
		if (!isset($this->buildingDataByLinkedUnit)) {
			$this->buildingDataByLinkedUnit = $this->buildTabArrByAttr('BuildingData','linkedUnit',TRUE);
		}
		return $this->buildingDataByLinkedUnit;
	}
	
	//combined index buildingID+Lvl
	function getBuildingDataByIDLvl() {
		if (!isset($this->buildingDataByIDLvl)) {
			$this->buildingDataByIDLvl = $this->buildTabArrByAttr('BuildingData',['buildingID','lvl'],TRUE);
		}
		return $this->buildingDataByIDLvl;
	}

	function getTurretData() {
		if (!isset($this->turretData)) {
			$this->turretData = $this->buildTabArrByAttr('TurretData','uid');
			$this->sortTabArrByAttr($this->turretData,'uid');
		}
		return $this->turretData;
	}

	function getEquipmentData() {
		if (!isset($this->equipmentData)) {
			$this->equipmentData = $this->buildTabArrByAttr('EquipmentData','uid');
			$this->sortTabArrByAttr($this->equipmentData,'uid');
		}
		return $this->equipmentData;
	}

	function getEquipmentEffectData() {
		if (!isset($this->equipmentEffectData)) {
			$this->equipmentEffectData = $this->buildTabArrByAttr('EquipmentEffectData','uid');
			$this->sortTabArrByAttr($this->equipmentEffectData,'uid');
		}
		return $this->equipmentEffectData;
	}
	
	function getSkinOverrideData() {
		if (!isset($this->skinOverrideData)) {
			$this->skinOverrideData = $this->buildTabArrByAttr('SkinOverrideData','uid');
			$this->sortTabArrByAttr($this->skinOverrideData,'uid');
		}
		return $this->skinOverrideData;
	}

	
	function getBuffData() {
		if (!isset($this->buffData)) {
			$this->buffData = $this->buildTabArrByAttr('BuffData','uid');
			$this->sortTabArrByAttr($this->buffData,'uid');
		}
		return $this->buffData;
	}
	
	function getPlanetData() {
		if (!isset($this->planetData)) {
			$this->planetData = $this->buildTabArrByAttr('PlanetData','uid');
			$this->sortTabArrByAttr($this->planetData,'uid');
		}
		return $this->planetData;
	}
	
	function getProjectileData() {
		if (!isset($this->projectileData))
			$this->projectileData = $this->buildTabArrByAttr('ProjectileData','uid');
		return $this->projectileData;
	}
	
	function getGameConstants() {
		if (!isset($this->gameConstants))
			$this->gameConstants = $this->buildTabArrByAttr('GameConstants','uid');
		return $this->gameConstants;
	}

}

?> 