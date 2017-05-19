<?php

require_once('WikiTmpl.php');
require_once('swc_json_data.php');

/* -------------------------------------- */

/*
  WikiLeveledTmpl:
	Base class for WikiTemplate filling with leveled data (i.e. unit/building levels)
*/

class WikiLeveledTmpl extends WikiTmpl {
	protected $minLvl;
	protected $maxLvl;

	const MATCH_TYPE_DURATIONMS = '/Delay|chargeTime|cooldownTime|^reload$/';
	const MATCH_TYPE_DURATION = '/Time/i';
	const MATCH_TYPE_NUMBER = '/^(credits|material|contraband|dps|health|upgradeCredits|upgradeMaterial|upgradeContraband|crossMaterial|xp)/i';


	
	const LVL_ATTR = 'lvl';
	
	function __construct($recs,$jdb) {
		parent::__construct($recs,$jdb);
		
		$this->reindexRecsByLvl();
	}
	
	public static function isLeveled() {
		return TRUE;
	}
	
	public static function splitUidLevel($rec) {
		if (array_key_exists('uid',$rec) && array_key_exists('lvl',$rec) && is_numeric($rec->{'lvl'})) {
			//expect level number at end of uid ie: shipHWK2901 is uid:shipHWK290 and level 1
			if (preg_match('/^(.*)('.$rec->{'lvl'}.')$/',$rec->{'uid'},$splituid)==1) {
				return array("id" => $splituid[1],"level" => $splituid[2]);
			}
		}
		return FALSE;
	}
	
	//reindex recs array by lvl
	protected function reindexRecsByLvl() {		
		$this->minLvl=9999;
		$this->maxLvl=0;
		
		$newrecs = array();
		foreach($this->recs as $k => $v) {
			$lv = intval($v->{self::LVL_ATTR});
			$newrecs[$lv]=$v;
			if ($lv<$this->minLvl)
				$this->minLvl=$lv;
			if ($lv>$this->maxLvl)
				$this->maxLvl=$lv;
			//echo "lv:$lv ".$this->maxLvl."\n";
		}
		$this->recs=$newrecs;
		
		/*Sort indexes 0-relative, but we need array by level index
			uasort($this->recs,
			function ($a, $b) {
				if (intval($a->{self::LVL_ATTR}) == intval($b->{self::LVL_ATTR}))
					return 0;
				return (intval($a->{self::LVL_ATTR}) < intval($b->{self::LVL_ATTR})) ? -1 : 1;
			}
		);
		
		//var_dump($this->recs);
		$this->minLvl=intval(reset($this->recs)->{self::LVL_ATTR});
		$this->maxLvl=intval(end($this->recs)->{self::LVL_ATTR});
		*/
	}
	
	protected function deriveTypeFromId($id) {
		if (preg_match(self::MATCH_TYPE_DURATIONMS,$id))
			return 'durationms';
		if (preg_match(self::MATCH_TYPE_DURATION,$id))
			return 'duration';
		elseif (preg_match(self::MATCH_TYPE_NUMBER,$id))
			return 'number';
		else
			return 'capital';
	}
	
	//checks ID for syntax "tablename.id" and returns linked table in case
	//i.e. projectile or buff table
	protected function valueForID($id,$lvl,$fmt = TRUE) {
		$rec = $this->recs[$lvl];
		
		if (!$fmt && !property_exists($rec,$id))
			return "";
		
		return $fmt?$this->fmt($rec,$id):$rec->{'val'};
	}
	
	//checks if param has different values across levels and try to merge these into 1 value
	protected function paramNTo1($id) {
		$res='';
		$prevval='';
		$prevstart=$this->minLvl;
		$count=0;

		//echo '+++';
		//var_dump($subarr);
	
		//loop to lvl+1 to have at least one value change at end
		for($i = $this->minLvl; $i<=$this->maxLvl+1; $i++) {
			if ($i<=$this->maxLvl) {
				$val=$this->valueForID($id,$i);
			} else
				$val='<eol>'; //just special value for end of list handling
		
			if (($i != $this->minLvl) && ($val != $prevval)) {
				if ($count>0)
					$res .= ', '; //separator (space allows line wrap)
			
				if (($i<$this->maxLvl) || ($count>0)) {
					if ($prevstart!=$i-1)
						$res.='L'.$prevstart."-".($i-1).':';
					else
						$res.='L'.$prevstart.':';
				}
				$res.=$prevval;
				$count++;
				$prevstart=$i;
			}
			$prevval=$val;
		}
	
		return $res;
	}
}
?>