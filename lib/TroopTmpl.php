<?php

//require_once('..\\config\\swc_wiki_config.php');

require_once('UnitTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */
/*
  TroopTmpl:
	Class for WikiTemplate filling with Troop unit data
*/


/*

(classFinalSrike)
(classAbility)

overload getTitle 

parent parameter <- orig Title
getFileName



*/


class TroopTmpl extends UnitTmpl{
	CONST FILTER_VALID_FACTION = '/empire|rebel/';
	CONST FILTER_INVALID_TYPE  = '/mercenary/';
	CONST FILTER_INCLUDE_UNIT = '/KraytDragon/';
	CONST FILTER_INVALID_UNIT = '/HeroSoldier|Champion|Seized|Stolen|Fake/';
	
	CONST IS_DROPSHIP = '/Dropship|Gunship/i';

	protected $heroAbilities;
	protected $buildingData;
	
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->buildingData = $jdb['base']->getBuildingData();
		$this->heroAbilities = $jdb['base']->getHeroAbilities();

		$this->template_params.="
  | type = 
  | required = 
  | class = 
  | damtype = 
  | favorite = 
  | move = 
  | range = 
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
";
	}

	//filter records
	public static function validRec($rec) {
		global $cfg;
		return ((preg_match($cfg['match_troop_re'],$rec->{'unitID'}) === 1) &&
				((preg_match(self::FILTER_INCLUDE_UNIT,$rec->{'unitID'}) === 1) ||
				((preg_match(self::FILTER_VALID_FACTION,$rec->{'faction'}) === 1) && 
				 (preg_match(self::FILTER_INVALID_TYPE,$rec->{'type'}) !== 1) &&
				 (preg_match(self::FILTER_INVALID_UNIT,$rec->{'unitID'}) !==1)))
				) ;
	}

	protected function getID() {
		return $this->defaultRec()->{'unitID'};
	}
	
	public function getTitle() {
		$key = $this->getTitleStrKey();
		$title = $this->getStr($key);
		if (preg_match('/(trp_title_.*)DropshipTrap/',$title,$strippedKey)) {
			//inconsistencies....
			$newKey = preg_replace("/HeavySoldier/","HeavyRebel",$strippedKey[1]);
			$title = $this->getStr($newKey).' (Dropship)';
		}
		$this->checkAmbiguousTitle($key,$title);
		
		return $title;
	}
	
	protected function getTitleStrKey() {
		return 'trp_title_'.$this->getID();
	}

	protected function getDescriptionStrKey() {
		return 'trp_desc_'.$this->getID();
	}
	
	protected function checkAmbiguousTitle($key,&$title) {
		if ((strpos($key,'Dropship') !== FALSE) && (strpos($title,'Drop')==FALSE))
			$title.=" (Dropship)";
		elseif (preg_match('/Pistoleer|Dowutin|Er.Kit Sniper/',$key)) {
			if (preg_match('/_r$/',$key))
				$title.=" (Rebel)";
			elseif (preg_match('/_e$/',$key))
				$title.=" (Empire)";
			else 
				$title.="";
		}
		elseif (preg_match('/Rancor|Johhar|Speeder|Brute|Droideka|Rider$|Gamorrean|Twilek|StormDeath|ScoutDeath|Tognath/',$key)) {
			if (strpos($key,'Rebel'))
				$title.=" (Rebel)";
			elseif (strpos($key,'Empire'))
				$title.=" (Empire)";
			else 
				$title.="";
		}
		if (preg_match('/Sample/',$key))
			$title.=" (Sample)";
		if (preg_match('/C3PO/',$key))
			$title="C3PO";
	}
	
	//event/promo unit? -> unit that goes into HQ (one-time use)
	//todo: find better indicator for event/promo units (=not purchaseable, not upgradeable)
	public function isEvent() {
		//credits/contra costs = 0 (or empty) & no droideka, or name match
		//if have no idea what exactly indicates if a troop is trainable or not  (player.unlockedlevels ???)
		if ((preg_match('/^(0[a-z]*|)$/i',$this->fmt($this->defaultRec(),'credits')) &&
			preg_match('/^(0[a-z]*|)$/i',$this->fmt($this->defaultRec(),'contraband')) &&
			!preg_match('/Champion/i',$this->getID())
			)
			|| preg_match('/Death|Rancor|Krayt|Sample/',$this->getID()) )
			return TRUE;
		else
			return FALSE;
	}
	
	//unit that is unlockable by conflicts/data fragments (maybe)
	// --> not upgradeable
	//example: "unlockPlanet": "FUTURE_EVENT_UNLOCK_HTH"
	//but: "unlockedByEvent" is useless because it is always 0
	public function isUnlockable() {
		if (($this->fmt($this->defaultRec(),'unlockPlanet')!="") ||
			($this->fmt($this->defaultRec(),'upgradeShards')!="")
			)
			return TRUE;
		else
			return FALSE;
	}

	//deathProjectile present?
	public function hasFinalStrike() {
		return property_exists($this->defaultRec(),'deathProjectile');
	}
	
	public function hasAbility() {
		return property_exists($this->defaultRec(),'ability');
	}
	
	protected function currencyCost() {
		if ($this->isEvent())
			return '';
		else {
			if (($this->defaultRec()->{'type'}=="mercenary") ||
				($this->defaultRec()->{'type'}=="champion")){
				return "contraband";
			} else {
				return "credits";
			}
		}
	}

	protected function currencyUpgrade() {
		if ($this->isEvent() || $this->isUnlockable()) {
			if (($this->fmt($this->defaultRec(),'upgradeShards')!=""))
				return "data fragments";
			else
				return '';
		} else {
			//check for conflict upgradable units
			if ($this->defaultRec()->{'type'}=="mercenary") {
				return "contraband";
			} else {
				return "credits";
			}
		}
	}
	
	//checks ID for syntax "projectile.id" and other linked tables and returns linked table value in case
	//behaves a bit like a sql join
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$rec = $this->recs[$lvl];
		
		if (preg_match('/^deathProjectile\.(.*)$/',$id,$dpjID)) {
			if (array_key_exists($rec->{'deathProjectile'},$this->projectileData)) {
				$rec = $this->projectileData[$rec->{'deathProjectile'}];
				$id = $dpjID[1];
			}
			else $rec = null;
		}
		elseif (preg_match('/^ability\.(.*)$/',$id,$abID)) {
			if (array_key_exists($rec->{'ability'},$this->heroAbilities)) {
				$rec = $this->heroAbilities[$rec->{'ability'}];
				$id = $abID[1];
			}
			else $rec = null;
		}
		elseif (preg_match('/^abilityProjectile\.(.*)$/',$id,$apID)) {
			if (array_key_exists($rec->{'ability'},$this->heroAbilities)) {
				$rec = $this->heroAbilities[$rec->{'ability'}];
				if (array_key_exists($rec->{'projectileType'},$this->projectileData)) {
					$rec = $this->projectileData[$rec->{'projectileType'}];
					$id = $apID[1];
				}
				else $rec = null;
			}
			else $rec = null;
		}
		elseif (preg_match('/^requirement([0-9])\.(.*)$/',$id,$reID)) {//requirement1-requirement9
			if (property_exists($rec,'requirements') &&
				array_key_exists($reID[1]-1,$rec->{'requirements'})) {
				if (array_key_exists($rec->{'requirements'}[$reID[1]-1],$this->buildingData)) {
					$rec = $this->buildingData[$rec->{'requirements'}[$reID[1]-1]];
					$id = $reID[2];
				}
				else $rec = null;
			}
			else $rec = null;
		}
		elseif (preg_match('/^projectile\.(.*)$/',$id,$pjID)) {
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

//todo: (optional?)
//TroopData -> clipRetargeting,supportFollowDistance (<>0,null), attackShieldBorder, overWalls (Runs/Shoots? over Walls), preventDonation?
//			shieldCooldown?
//Ability->coolDownTime, overWalls?? maxAttackRange/minAttackRange, clipRetargeting

	
	//fill in parameter values into template
	public function process() {
		//fill in template
		
		parent::process();
		$rec = $this->defaultRec();
		
		$this->setTmplParam('unitID',$this->getID());
		$this->setTmplParam('gametext',$this->getDescription());
		$this->setTmplParam('faction',$this->fmt($rec,'faction'));

		//!!
		//$this->setTmplParam('image','',TRUE);//reset image parameter/remove 
		//!!
		
		$this->setTmplParam('type',$this->fmt($rec,'type'));
		$this->setTmplParam('armortype',$this->armorType());
		$this->setTmplParam('class',$this->fmt($rec,'role'));
		$this->setTmplParam('favorite',$this->fmt($rec,'favoriteTargetType'));
		
		if ($this->fmt($rec,'unlockPlanet') != "")
			$this->setTmplParam('required',$this->getStr($rec->{'unlockPlanet'}));
		
		if ($rec->{'minAttackRange'} == '0')
			$this->setTmplParam('range',$this->fmt($rec,'maxAttackRange'));
		else
			$this->setTmplParam('range',$this->fmt($rec,'minAttackRange').'-'.$this->fmt($rec,'maxAttackRange'));
		
		$this->setTmplParam('capacity',$this->fmt($rec,'size'));
		$this->setTmplParam('move',$this->paramNTo1('maxSpeed'));

		if (preg_match('/bruiser/i',$rec->{'armorType'}))
			$this->setTmplParam('bruiser','bruiser');
		else
			$this->setTmplParam('bruiser','');
		
		if (preg_match('/healer/i',$rec->{'armorType'}))
			$this->setTmplParam('healer','healer');
		else
			$this->setTmplParam('healer','');

		if ($this->isEvent())
			$this->setTmplParam('event','event');
		else
			$this->setTmplParam('event','',TRUE);
		if ($this->isUnlockable())
			$this->setTmplParam('unlockable','unlockable');
		else
			$this->setTmplParam('unlockable','',TRUE);

		$this->setTmplParam('upgradecurrency',$this->currencyUpgrade(),TRUE);
		$this->setTmplParam('trainingcurrency',$this->currencyCost(),TRUE);
		//echo ($this->minLvl."-".$this->maxLvl."\n");
		for($i = $this->minLvl; $i <= $this->maxLvl; $i++) {
			$rec = $this->recs[$i];
			
			$this->setTmplParam('damage'.$i,$this->fmt($rec,'dps'));
			if ($this->hasAbility())
				$this->setTmplParam('abilityDamage'.$i,$this->valueForID('ability.damage',$i));
			$this->setTmplParam('health'.$i,$this->fmt($rec,'health'));
			
			if (!preg_match(self::IS_DROPSHIP,$rec->{'uid'})) {
				if (!$this->isEvent())
					$this->setTmplParam('training'.$i,$this->fmt($rec,'trainingTime'));
				else
					$this->setTmplParam('training'.$i,'');
				switch ($this->currencyCost()) {
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
				switch ($this->currencyUpgrade()) {
					case 'credits':
						$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeCredits'));
						break;
					case 'contraband':
						$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeContraband'));
						break;
					case 'alloy':
						$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeMaterial'));
						break;
					case 'data fragments':
						$this->setTmplParam('upcost'.$i,$this->fmt($rec,'upgradeShards'));
						break;
					default:
						$this->setTmplParam('upcost'.$i,'');
				}
				$this->setTmplParam('uptime'.$i,$this->fmt($rec,'upgradeTime'));
			} else {
				$this->setTmplParam('cost'.$i,'',TRUE);
				$this->setTmplParam('training'.$i,'',TRUE);
				$this->setTmplParam('upcost'.$i,'',TRUE);
				$this->setTmplParam('uptime'.$i,'',TRUE);
			}
		}
		$this->processDamageModifiers();
		
		if ($this->hasAbility() &&
			array_key_exists($rec->{'ability'},$this->heroAbilities)) {
			$this->setTmplParam('cooldowntime',$this->paramNTo1('ability.cooldownTime'));
			//Assumption(!):Range assumed the same for all levels
			if ($this->valueForID('ability.minAttackRange',$this->minLvl) == '0')
				$this->setTmplParam('range',$this->valueForID('ability.maxAttackRange',$this->minLvl));
			else
				$this->setTmplParam('range',$this->valueForID('ability.minAttackRange',$this->minLvl).
								'-'.$this->valueForID('ability.maxAttackRange',$this->minLvl));
			
			$this->processDamageModifiers('ab_dmod_','abilityProjectile.');
		}
		
		return $this->chgCount;
	}
	
}
?>