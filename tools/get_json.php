<?php
//date_default_timezone_set(date_default_timezone_get());

//define('MANIFEST_VERSION',"379");
//starting with version 1010 a login is necessary to get the manifest
define('MANIFEST_VERSION',"1025");

define('MANIFEST_URL',"https://d50ea5a0.content.disney.io/manifests/__manifest_starts_prod.0".MANIFEST_VERSION.".json");
//define('MANIFEST_URL',"https://d50ea5a0.content.disney.io/1490909177/manifests/__manifest_starts_prod.0".MANIFEST_VERSION.".json");
//define('MANIFEST_URL',"https://starts0.content.disney.io/cloud-cms/manifest/starts/prod/".MANIFEST_VERSION.".json");

define('DIR_MANIFEST',"manifest".DIRECTORY_SEPARATOR);
define('DIR_JSON',"..".DIRECTORY_SEPARATOR."json".DIRECTORY_SEPARATOR);

$json_re = 	'\/base\.json$'.
			'|\/strings_en-US\.json$';


//$json_re = 	'\/base\.json\.android\.assetbundle$';


/*

Warning: file_get_contents(https://d50ea5a0.content.disney.io/1492731972/patches/base.json.android.assetbundle): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found
 in N:\michael\nintendo\tablet\starwars\debug\scripts\Wiki\tools\get_json.php on line 54
Error: could not load base.json.android.assetbundle from:
https://d50ea5a0.content.disney.io/1492731972/patches/base.json.android.assetbundle

//sample url (new): https://d50ea5a0.content.disney.io/1492647377/patches/base.json.android.assetbundle


GET https://d50ea5a0.content.disney.io/manifests/__manifest_starts_prod.01010.json HTTP/1.1
X-Unity-Version: 5.1.4p1
User-Agent: Dalvik/1.6.0 (Linux; U; Android 4.1.1; RY Tablet Build/JRO03S)
Host: d50ea5a0.content.disney.io
Connection: Keep-Alive
Accept-Encoding: gzip


*/


//doesn't make a difference
$options = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"X-Unity-Version: 5.1.4p1\r\n" .
              "User-Agent: Dalvik/1.6.0 (Linux; U; Android 4.1.1; Tablet Build/JRO01S)\r\n"
  )
);

$context = stream_context_create($options);
//$file = file_get_contents($url, false, $context);



try {
	$from_file = FALSE;
	$contents=FALSE;
	
	if (file_exists(DIR_MANIFEST.MANIFEST_VERSION.".json"))
		$contents = file_get_contents(DIR_MANIFEST.MANIFEST_VERSION.".json");
	
	if ($contents !== FALSE)
		$from_file=TRUE;
	else
		$contents = file_get_contents(MANIFEST_URL);
	
	if ($contents!==FALSE) {

		if (!$from_file)
			file_put_contents(DIR_MANIFEST.MANIFEST_VERSION.".json",$contents);
		
		//generate small json file to indicate current version
		$verobj = (object) [
			'version' => MANIFEST_VERSION
			];
		file_put_contents(DIR_JSON.'version.json',json_encode($verobj));
		
		$contents = utf8_encode($contents);
		$json = json_decode($contents);
		
		
		if (isset($json->{'cdnRoot'})) { //new since version 1010
			$url_root=$json->{'cdnRoot'};
			foreach($json->{'paths'} as $k => $v) {
				if (preg_match('/'.$json_re.'/i',$k)) {
					if (preg_match('/\/(.*)$/',$k,$matches)) {
						$jurl=$url_root.$v->{'v'}.'/'.$k;
						$jstr = file_get_contents($jurl,false,$context);
						
						if ($jstr !== FALSE) {
							echo "get: ".$matches[1]."\n";
							file_put_contents(DIR_JSON.$matches[1],$jstr);
							
						}
						else
							echo "Error: could not load ".$matches[1]." from:\n".$jurl."\n";
						
					}
				}
			}
		} else {
			$url_root=$json->{'secure_cdn_roots'}[0].$json->{'productId'}.'/'.$json->{'environment'}.'/';
		
			foreach($json->{'hashes'} as $k => $v) {
				if (preg_match('/'.$json_re.'/i',$k)) {
					if (preg_match('/\/(.*)$/',$k,$matches)) {
						$jurl=$url_root.$k.'/'.$v.".".$matches[1];
						$jstr = file_get_contents($jurl);
						
						if ($jstr !== FALSE) {
							echo "get: ".$matches[1]."\n";
							file_put_contents(DIR_JSON.$matches[1],$jstr);
							
						}
						else
							echo "Error: could not load ".$matches[1]." from:\n".$jurl."\n";
						
					}
				}
			}
		}
	}
	else
		echo "Error: could not load manifest version: ".MANIFEST_VERSION."\n";
}

catch(Exception $e) {
  echo 'Exception: ' .$e->getMessage();
}
?> 