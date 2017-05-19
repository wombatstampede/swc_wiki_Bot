<?php
/*
 Wiki Template handling classes
 Purpose: create/modify a Wiki Page Source for Star Wars Commander Wiki units and buildings
 derived from game data
 
 Hierarchy:
 
 WikiTmpl (Basic handling)

	WikiLeveledTmpl (with unit levels)
	
		UnitTmpl ()
		
			TroopTmpl (Units based on TroopData)
				MercenaryTmpl 
				DroidekaTmpl
		
			ShipTmpl (Units based on SpecialAttackData)
						
			TurretTmpl
			
		BuildingTmpl (Units based on BuildingData)

	Classes for unleveled/different leveled items: Chests etc.

*/

require_once('extlib/mediawikibot.class.php');

/*
	Basic Wiki Template class
	Has to be overriden to be functional
*/

class WikiTmpl {
	CONST DEFAULT_TEMPLATE='Troop';
	CONST TEMPLATE_MATCH = '/{{\s*Troop/'; //default matches to Troop*
	
	protected $template_params;
	
	//version data
	protected $version;
	
	//string data
	protected $localizedStrings;
	
	//basic data records (i.e. 1 for each unit level)
	protected $recs;
	
	//string-array containing page-src
	protected $tmplArr;
	
	//changecount when editing page source
	protected $chgCount = 0;
	
	function __construct($recs,$jdb) {
		$this->localizedStrings = $jdb['strings']->getLocalizedStrings();
		$this->version=$jdb['version']->getVersion();
		$this->recs=$recs;
//  | image = (not as default parameter anymore)
		$this->template_params = "  | unitID = 
  | gametext = 
  | dataversion =
  | faction = ";
	}

	public static function isLeveled() {
		return FALSE;
	}

	//filter records
	public static function validRec($rec) {
		return TRUE;
	}

	//todo: public function initForSubpage()
	
	public function hasFinalStrike() {
		return FALSE;
	}
	
	public function hasAbility() {
		return FALSE;
	}

	
	//get one record of recs (for general field values)
	protected function defaultRec() {
		return reset($this->recs); //1st record in this case
	}

	//unique identifier of first/default record
	protected function getFirstUID() {
		return defaultRec()->{'uid'};
	}
	
	//ID which usually represents the group of records (may not be unique: unitID, specialAttackID, buildingID ...)
	//this is a dummy value, please overload in child classes
	protected function getID() {
		return $this->getFirstUID();
	}
	
	//please overload to provide correct key into string table
	protected function getTitleStrKey() {
		return $this->getFirstUID();
	}
	
	//please overload to provide correct key into string table
	protected function getDescriptionStrKey() {
		return $this->getFirstUID();
	}
	
	//init Page Source with default Template-Info
	public function init() {
		$this->chgCount=0;
		$rec=$this->defaultRec();
		$this->tmplArr = array();

//		commented out: unit description as page content (same description is in the parameters as well)
//		$this->tmplArr[]=$this->fmtWikiParam($this->getTitle());
//		$this->tmplArr[]="";
		$c = get_called_class();
		$this->tmplArr=array_merge($this->tmplArr,explode("\n",
						"{{ ".$c::DEFAULT_TEMPLATE.
						" | \n".$this->template_params."\n}}"));
	}
	
	//read Page Source from File
	//	returns TRUE on success, FALSE on failure
	public function readFromFile($fname) {
		if (file_exists($fname)) { //prob. don't use this when switching to http get
			$tmplStr = file_get_contents($fname);
			if ($tmplStr !== FALSE) {
				$this->tmplArr = explode("\n",$tmplStr);
				$this->chgCount=0;
				return TRUE;
			}
		}
		$this->init(); //init with empty template on failure
		return FALSE;
	}
	
	public function readFromWiki($wiki) {
		global $cfg;
		$retrycnt = $cfg['read_wiki_retries'];
		
		do {
			// to edit a page you have to know its page-id
			$querydata = array(
				'prop'       => 'revisions',
				'rvprop'     => 'content',
				'titles'     => $this->getTitle()
			);
			$r = $wiki->query($querydata);
			
	/*
			Page not found result example:
				array(2) {
					["batchcomplete"] => string(0) ""
					["query"] => array(1) {
						["pages"]=> array(1) {
							[-1]=> array(3) {
								["ns"]=>int(0)
								["title"]=> string(18) "A-wing Starfighter"
								["missing"]=> string(0) ""
							}
						}
					}
				}
			
			Page found result example:
				array(2) {
					["batchcomplete"] => string(0) ""
					["query"] => array(1) {
						["pages"] => array(1) {
							[105] => array(4) {
								["pageid"] => int(105)
								["ns"] => int(0)
								["title"] => string(18) "A-wing Starfighter"
								["revisions"]=> array(1) {
									[0] => array(3) {
										["contentformat"] => string(11) "text/x-wiki"
										["contentmodel"]=> string(8) "wikitext"
										["*"]=> string(1810) "{{ Starship | ..... }}"
									}
								}
							}
						}
					}
				}
	*/

			if (($r!==null) && ($r['query']!==null) && array_key_exists('pages',$r['query']) 
				&& (count($r['query']['pages'])>0)) {
				$page = array_pop($r['query']['pages']);
					
				if (array_key_exists('revisions',$page)) {
					$rev = array_pop($page['revisions']);
					
					if (array_key_exists('*',$rev)) {//most recent revision is '*'
						echo '  Read from wiki: '.$this->getTitle()."\n";
						$this->tmplArr = explode("\n",$rev['*']);
						$this->chgCount=0;
						return TRUE;
					}
				}
				echo '  Not found in wiki: '.$this->getTitle()."\n";
				$this->init(); //init with empty template on failure
			} 
			else {
				$msg="Invalid response reading wiki entry for ".$this->getTitle()."\n".
				ob_start();
				echo "Querydata:\n";
				var_dump($querydata);
				echo "Response:\n";
				var_dump($r);
				$msg.=ob_get_contents();
				ob_end_clean();
				if ($retrycnt<1)
					throw new Exception($msg);
				else
					echo /*$msg.*/"Retries: ".$retrycnt."\n";
			}
			if ($retrycnt<1)
				return FALSE;
			$retrycnt--;
		} while ($retrycnt>=0);
	}
		
	public function writeToFile($fname,$force = FALSE) {
		if ($force || $this->chgCount>0) { //only write if changed
			file_put_contents($fname,implode("\n",$this->tmplArr));
		}
	}
	
	public function writeToWiki($wiki,$force = FALSE) {
		global $cfg;
		$retrycnt = $cfg['write_wiki_retries'];
		do {
			if ($force || $this->chgCount>0) { //only write if changed
				
				$src = implode("\n",$this->tmplArr);
				
				$edittoken = $wiki->getEditToken();
					
				$querydata = array(//retrieve page-id
					'prop'       => 'info',
					'titles'     => $this->getTitle()
				);
				$r = $wiki->query($querydata);
				
				if (array_key_exists('pages',$r['query']) && (count($r['query']['pages'])>0)) {
					$pages = $r['query']['pages'];
					$page = array_pop($pages);

					$editdata = array(
						'text'       => $src,
						'bot'        => true,
						'md5'        => md5($src),
						'token'      => $edittoken
					);
					if (array_key_exists('pageid',$page)) {
						$editdata['pageid']=$page['pageid'];
						echo "  Update existing wiki page.";
					} else {
						$editdata['title']=$this->getTitle();
						echo "  Create new wiki page.";
					}
					$r = $wiki->edit($editdata);
					
					echo "Wiki Edit result:".$r['edit']['result']."\n";
					if (preg_match('/Success/i',$r['edit']['result']))
						return TRUE;
					else
						return FALSE;//retry??
				}
				else {
					echo "Error: Wiki Page request unsuccessful.\n";
					if ($retrycnt<1)
						return FALSE;
					else
						echo "Retries: ".$retrycnt."\n";
				}
			} else return TRUE; //no changes -> no write -> "success"
			$retrycnt--;
		} while ($retrycnt>=0);
	}
	
	//edit params from records into page source
	public function process() {
	
		//fill in template
		
		$saveChgCount = $this->chgCount;// don't change chgCount, version change alone shouldn't trigger an update
		$this->setTmplParam('dataversion',$this->version);
		$this->chgCount = $saveChgCount;
		
		return $this->chgCount;
	}

	public function getTitle() {
		$key = $this->getTitleStrKey();
		$title = $this->getStr($key);
		$this->checkAmbiguousTitle($key,$title);
		
		return $title;
	}

	public function getFileName() {
		return $this->getTitle().".txt";
	}
	
	public function getDescription() {
		$key = $this->getDescriptionStrKey();
		return $this->getStr($key,"No description available.");
	}
	
	//rename template via regexp (maybe useful for bulk renaming)
	public function renameTemplateRegExp($matchexp,$replacestr) {
		foreach($this->tmplArr as $k => &$v) {
			if (strpos($v,'{{') !== FALSE) {
				if (preg_match($matchexp,$v)) {
					$newval=preg_replace($matchexp,$replacestr,$v);
					if ($newval!=$v) {
						$this->chgCount++;
						$v=$newval;
					}
				}
			}
		}
	}
	
	//merge template param value into wiki page source
	protected function setTmplParam($param,$value,$removeIfEmpty = FALSE) {
		if (is_null($value))
			$value='';
	
		$inTmpl=0;
		$tmplLv=9999; //nest level of named template
		//parsing relies on specific format:
		// only 1 template per line
		// only 1 param per line
		
		foreach($this->tmplArr as $k => $v) {
			if (strpos($v,'{{') !== FALSE) {
				//template opens
				$inTmpl++;
				//check if template name is valid for parameter replacing
				$c = get_called_class();
				if (preg_match($c::TEMPLATE_MATCH,$v))
					$tmplLv=$inTmpl;
			}
			if (strpos($v,'}}') !== FALSE) { 
				if (($inTmpl==$tmplLv) && (!$removeIfEmpty || ($value!=""))) { 
					//template closes but parameter not found => insert into array
					array_splice($this->tmplArr,$k,0,'  | '.$param.' = '.$this->fmtWikiParam($value));
					$this->chgCount++;
				}
				$inTmpl--;
			}
			//param found and in template or nested template
			if (($inTmpl>=$tmplLv) && (preg_match('/^\s*\|\s*'.$param.'\s*=\s*(.*)$/',$v,$matches))) {
				if (!$removeIfEmpty || ($value!="")) {
					if ($value != $matches[1]) {//value changed -> update
						$this->tmplArr[$k]='  | '.$param.' = '.$this->fmtWikiParam($value);
						$this->chgCount++;
					}
				} elseif ($removeIfEmpty && ($value=="")) {
					//remove parameter if found, removeIfEmpty is true and new value is empty:
					array_splice($this->tmplArr,$k,1);
					
					//important: this foreach ends after this remove (break), else foreach might be out of sync
					$this->chgCount++;
				}
				break;
			}
		}
	}
	
	
	protected function getStr($key,$defaultVal = null) {
		if (array_key_exists($key,$this->localizedStrings)) {
			return $this->localizedStrings[$key]->{'text'};
		}
		else
			if ($defaultVal === null)
				return '_'.$key.'_';
			else
				return $defaultVal;
	}

	protected function checkAmbiguousTitle($key,&$title) {
	}


	//try to format a template parameter value so that it doesn't contain color codes or invalid wiki identifiers
	protected function fmtWikiParam($str) {
		return preg_replace('/\[[^\]]*\]|{[^}]*}/','',str_replace("\n"," ",str_replace("\r"," ",str_replace("|","_",$str))));
	}

	protected function deriveTypeFromId($id) {
	}

	//overload if different record or field value
	protected function armorType() { //derive armor type name from armorType field, stringtable and more...
		//there's no standard localization for armor type
		//example for difference type<->armor type: Lugga, type=Mercenary, armor type: Healer Infantry
		$this->formatArmorType($this->defaultRec()->{'armorType'});
	}

	protected function formatArmorType($key) {
		//look up in target pref localization strings
		//not covered: champion, healerInfantry, flierInfantry, HQ only translates as HQ (better: Headquarters)
		$trgpref = $this->getStr('target_pref_'.preg_replace('/^vehicle$/',"vehicles",$key),'');
		if ($trgpref != "")
			return $trgpref;
		
		if ($key == "champion")
			return "Droideka";
		
		//match two word id. example: flierInfantry -> (flier)(Infantry)
		if (preg_match('/([a-z]+)([A-Z].*)/',$key,$matches)) 
			return ucfirst($matches[1])." ".$matches[2];
		else //single word id
			return ucfirst($key);
	}
	
	
	//currently, units are not appended to numbers (currencyCost,currencyUpgrade are used instead)
	protected function deriveNumberUnitFromId($id) {
		//echo("derive $id\n");
		if (preg_match('/credit/i',$id))
			return '';
		elseif (preg_match('/alloy/i',$id))
			return '';
		elseif (preg_match('/contraband/i',$id))
			return '';
		else
			return '';
	}
	
	//format field, type is derived from fieldid
	protected function fmt($rec,$id) {
		if (property_exists($rec,$id)) {
			$val=$rec->{$id};
			switch ($this->deriveTypeFromId($id)) {
				case 'durationms':
					return $this->fmtDurationMS($val);
				case 'duration':
					return $this->fmtDuration($val);
				case 'number':
					return $this->fmtNumber($val,$id);
				case 'capital':
					return $this->fmtCapital($val);
			}
			return $val;
		}
		else return '';
	}

	//capital letter
	protected function fmtCapital($val) {
		if (preg_match('/^[a-z]/',$val))
			return ucfirst($val);
		else
			return $val;
	}

	protected function fmtDuration($val) {
		if (is_numeric($val)) {
			$res='';
			$we = floor($val/(3600*24*7));
			$val-=($we*3600*24*7);
			if ($we>0)
				$res.=$we.'w';
			$da = floor($val/(3600*24));
			$val-=($da*3600*24);
			if ($da>0)
				$res.=$da.'d';
			$ho = floor($val/3600);
			$val-=($ho*3600);
			if ($ho>0)
				$res.=$ho.'h';
			$mi = floor($val/60);
			$val-=($mi*60);
			if ($mi>0)
				$res.=$mi.'m';
			if ($val>0)
				$res.=$val.'s';
			return $res;
		} else
			return $val;
	}

	protected function fmtDurationMS($val) {
		if (is_numeric($val)) {
			return sprintf("%F",($val/1000));
		} else
			return $val;
	}
	
	//format number and append unit if id matches
	protected function fmtNumber($val,$id="") {
		if (is_numeric($val)) {
			return sprintf("%d%s",$val,$this->deriveNumberUnitFromId($id));
			//currently, numbers with thousands separators are formatted on template level, not here
			//return number_format($val,0,'.',',').$this->deriveNumberUnitFromId($id);
		} else
			return $val;
	}
}


?> 