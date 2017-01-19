<?php
	$local = false;   //http://localhost
	$debug = false;   //use test data
	$useproxy = false; //use http_proxy

	header('Content-Type:text/plain; charset=utf-8');

	#for convert zh-TW to zh-cn
	define("MEDIAWIKI_PATH", "mediawiki-1.26.2");
	require_once "mediawiki-zhconverter.inc.php";
/*
	Copyright (C) 2010  Felix Nyffenegger

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
	//-------------------------------------------------------------------------------------------
	// Handle Get Paramters
	//-------------------------------------------------------------------------------------------

	$time_start = microtime(true);
	if ($debug == true){
		$file = "data/os.txt";
	}
	if ($local == true) {
		$wiki = "zh.wikipedia.org";
		$topic = "%E5%8A%A8%E7%89%A9";  //动物
	}
	else {
		$wiki = $_GET['Wiki'];
		$topic = $_GET['Topic'];
	}

	$topic = urldecode($topic);
	$topic = str_replace(" ", "_", $topic);

	//Wiki specific Variables
	$index_path = "";
	$access_path = "";

	switch ($wiki) {
		case "www.self-qs.de":
			$index_path = "";
			$access_path = "/m3WDB";
			break;
		case "zh.wikipedia.org":
			$index_path = "/w";
			$access_path = "/zh-cn";
			break;
		default:
			$index_path = "/w";
			$access_path = "/wiki";
			break;
	}

	$url = 'https://'.$wiki.$index_path.'/index.php?title='.urlencode($topic).'&action=raw';
	//echo $url;
	//-------------------------------------------------------------------------------------------
	// Defaults for the Parser
	//-------------------------------------------------------------------------------------------

	$catStart     = '{';
	$catEnd 	  = '}';
	$chapStart    = '==';
	$chapEnd	  = '==';
	$subChapStart = '===';
	$subChapEnd   = '===';
	$linkStart 	  = '[[';
	$linkEnd 	  = ']]';
	$wwwLinkStart = '[http:';
	$wwwLinkEnd   = ']';

	//echo $topic;

	//-------------------------------------------------------------------------------------------
 	// Extract the main Topic from the Wikki
	// This code works only for mediawiki type of wikis later following changes are to be done:
	// replace $wiki by a wiki class, representing a wiki-tpye
	// ad an extract_topc(wikiclass) function to get the wiki page
	// Typical WikiMedia URL http://de.wikipedia.org/w/index.php?title=Automobil&action=edit
 	//-------------------------------------------------------------------------------------------



	$timeout = 5; // set to zero for no timeout
	if ($local==true){
		$useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36";
	}
	else{
		$useragent = $_SERVER['HTTP_USER_AGENT']; // get user agent
	}


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	if ($useproxy==true){
		//use proxy
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($ch, CURLOPT_PROXY, "192.168.144.128");
		curl_setopt($ch, CURLOPT_PROXYPORT, "8118");
	}

	if ($debug==true){
		//get test data from local
		$topic = $file;
	  	$contents = file_get_contents($file);
	}
	else
	{
		//get data from wikipedia
		$contents = curl_exec($ch);
		if ($contents == false and $debug == true) {
			printf("cUrl error (#%d): %s<br>\n", curl_errno($ch), htmlspecialchars(curl_error($ch)));
		}
		curl_close($ch);
	}

	// Decode from UTF-8
	//$contents = utf8_decode($contents);

	//convert zh-TW to zh-cn
	//$contents = MediaWikiZhConverter::convert($contents,"zh-cn","utf-8");

	//remove none-printable unicode charactor

	/*
	$contents = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', '', $contents);
	*/


	//check encoding
	//echo mb_detect_encoding($contents);

	$contents = removeComments($contents);
	$contents = removeClassInfo($contents);

	//echo "Content:" . $contents;

	//-------------------------------------------------------------------------------------------
	// Parse the Topicfile to find WikiLinks
	//-------------------------------------------------------------------------------------------

	$i=0;
	$link[0][0] = "";
	//echo chr(239).chr(187).chr(191);
	echo chr(0xef).chr(0xbb).chr(0xbf);
	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<map version=\"0.8.0\">\n";
	echo "<edge STYLE=\"bezier\"/>\n";
	$wikilink  = 'http://'.$wiki.'/wiki/'.urlencode($topic);
	$ttorg = mb_substr($contents,0,500);
	//echo 'TTROG: '.$ttorg;
	$tooltip = createToolTipText($ttorg, 300);
	//echo 'TT: '.$tooltip;
	//echo $topic;
	echo "<node STYLE=\"bubble\" TEXT=\"".$topic."\" WIKILINK = \"".$wikilink."\" TOOLTIPTEXT = \"".$tooltip."\">\n";
	//echo '<node STYLE="bubble" TEXT="Main">/n';
	echo "<edge STYLE=\"sharp_bezier\" WIDTH=\"2\"/>\n";

	$openChap = FALSE;
	$openSubChap = FALSE;
	$counter = 0;

	while (mb_strpos($contents,$linkStart) > -1 ||
	       mb_strpos($contents,$wwwLinkStart) > -1 ||
		   mb_strpos($contents,$chapStart) > -1 ||
		   mb_strpos($contents,$subChapStart) > -1)
	{
		$counter++;
		// is the next object to parse a section or a wikilink?

		$iChap = mb_strpos($contents, $chapStart);
		$iSubChap = mb_strpos($contents, $subChapStart);
		$iLink = mb_strpos($contents, $linkStart);
		$iWwwLink = mb_strpos($contents, $wwwLinkStart);

		//echo '<br>Chap: '.$iChap.' Link:'.$iLink.' WWW:'.$iWwwLink.'<br>';

		//-----------------------------------------
		// Create Chapter Nodes
		//-----------------------------------------
		if ($iChap > -1 && ($iChap < $iLink || !$iLink) && ($iChap < $iWwwLink || !$iWwwLink) && ($iChap < $iSubChap || !$iSubChap))
		{

			$contents = mb_strstr($contents, $chapStart);
			$contents = mb_substr($contents, mb_strlen($chapStart));
			$Chap = mb_substr($contents,0, mb_strpos($contents,$chapEnd));

			//echo $Chap.'<br>';
			if ($Chap !="")
			{
				if ($openSubChap == TRUE)
				{
					echo "</node>\n";
					$openSubChap = FALSE;
				}
				if ($openChap == TRUE)
				{
					echo "</node>\n";
					$openChap = FALSE;
				}

				// Filter all the Tag information
				$Chap  = str_replace($linkStart,"", $Chap);
				$Chap  = str_replace($chapEnd,"", $Chap);
				$Chap  = str_replace("=","", $Chap);


				// Create Topic
				$wChap = str_replace(" ","_", $Chap);
				$wChap = trim($wChap, "_");
				$ttorg = mb_substr($contents,mb_strpos($contents,$chapEnd)+2, 500);
				$tooltip = createToolTipText($ttorg, 150);

				$wikilink  = 'http://'.$wiki.$access_path.'/'.$topic.'#'.implode('.',explode('%',urlencode($wChap)));
				echo  "<node TEXT=\"".cleanText($Chap)."\" WIKILINK = \"".cleanWikiLink($wikilink)."\" TOOLTIPTEXT = \"".$tooltip."\" STYLE=\"bubble\">\n";
				//echo  '<node TEXT="'.cleanText($Chap).'" WIKILINK = "'.cleanWikiLink($wikilink).'"  STYLE="bubble">/n';
				//echo 'node TEXT="'.$Chap.'" STYLE="bubble"><br>';
				$openChap = TRUE;

			}

			$contents = mb_strstr($contents,$chapEnd);
			$contents = mb_substr($contents, mb_strlen($chapEnd));

		}

		//-----------------------------------------
		// Create SubChapter Nodes
		//-----------------------------------------
		if ($iSubChap > -1 && ($iSubChap < $iLink || !$iLink) && ($iSubChap < $iWwwLink || !$iWwwLink) && ($iSubChap <= $iChap || !$iChap))
		{
			//echo "SUbCHap";
			$contents = mb_strstr($contents, $subChapStart);
			$contents = mb_substr($contents, mb_strlen($subChapStart));
			$SubChap = mb_substr($contents,0, mb_strpos($contents,$subChapEnd));

			//echo $Chap.'<br>';
			if ($SubChap !="")
			{
				if ($openSubChap == TRUE)
				{
					echo "</node>\n";
					$openSubChap = FALSE;
				}

				// Filter all the Tag information
				$SubChap  = str_replace($linkStart,"", $SubChap);
				//$SubChap  = str_replace($chapEnd,"", $SubChap);
				$SubChap  = str_replace("=","", $SubChap);

				// Create Topic
				$wSubChap = str_replace(" ","_", $SubChap);
				$wSubChap = trim($wSubChap, "_");
				$ttorg = mb_substr($contents,mb_strpos($contents,$subChapEnd)+3, 500);
				//$tooltip = createToolTipText($ttorg, 150);

				$wikilink  = 'http://'.$wiki.$access_path.'/'.$topic.'#'.implode('.',explode('%',urlencode($wSubChap)));
				echo  "<node TEXT=\"".cleanText($SubChap)."\" WIKILINK = \"".cleanWikiLink($wikilink)."\" TOOLTIPTEXT = \"".$tooltip."\" STYLE=\"bubble\">\n";
				//echo  '<node TEXT="'.cleanText($Chap).'" WIKILINK = "'.cleanWikiLink($wikilink).'"  STYLE="bubble">/n';
				//echo 'node TEXT="'.$Chap.'" STYLE="bubble"><br>';
				$openSubChap = TRUE;
			}

			$contents = mb_strstr($contents,$subChapEnd);
			$contents = mb_substr($contents, mb_strlen($subChapEnd));
		}


		//-----------------------------------------
		// Create WWW Link Nodes
		//-----------------------------------------
		if ($iWwwLink > -1 && ($iWwwLink < $iLink || !$iLink) && ($iWwwLink < $iChap || !$iChap) && ($iWwwLink < $iSubChap || !$iSubChap))
		{

			$contents = mb_strstr($contents, $wwwLinkStart);
			$contents = mb_substr($contents, mb_strlen($wwwLinkStart));
			$wwwLink  = mb_substr($contents,0, mb_strpos($contents,$wwwLinkEnd));
			if ($wwwLink !="")
			{
				$wwwLinkURL = 'http:'.substr($wwwLink, 0, mb_strpos($wwwLink, " "));
				$wwwLinkName = mb_substr($wwwLink, mb_strpos($wwwLink, " "), mb_strlen($wwwLink));
				echo "<node TEXT=\"".cleanText($wwwLinkName)."\" WEBLINK=\"".$wwwLinkURL."\" STYLE=\"fork\">\n";
				echo "</node>\n";
			}
			$contents = mb_strstr($contents,$wwwLinkEnd);
			$contents = mb_substr($contents,mb_strlen($wwwLinkEnd));
		}

		//-----------------------------------------
		// Create WikiPage Nodes
		//-----------------------------------------
		if ($iLink > -1 && ($iLink < $iWwwLink || !$iWwwLink) && ($iLink < $iChap || !$iChap) && ($iLink < $iSubChap || !$iSubChap))
		{
			$contents = mb_strstr($contents,$linkStart);
			$tag = mb_substr($contents, mb_strlen($linkStart), mb_strpos($contents, $linkEnd) - mb_strlen($linkStart));

			//echo $tag;

			// Keine Bilder etc...
			if (mb_strpos($tag, ':') == FALSE)
			//$tag should be parsed seperately in future (applies for all nodes, end-node and chapter, to do the following things:
			// - if | exists: left part = wikilink, right part = name in text
			// - if : exists, decide, what to do... (add picture to mindmap?
			// - ...
			{
				//No dublicates

				if (in_array($tag, $link[0]) == FALSE)
				{
					if (mb_strpos($tag, '|') != FALSE)
					{
						$wTag = mb_substr($tag,0,mb_strpos($tag,'|'));
						$link[1][$i] = str_replace(" ","_", $wTag);
						$link[0][$i] = str_replace("|"," / ", $tag);
					}
					else
					{
						$link[1][$i] = str_replace(" ","_", $tag);
						$link[0][$i] = $tag;
					}
					$wikilink    = 'http://'.$wiki.$access_path.'/'.$link[1][$i];
					$mmlink	 = 'viewmap.php?wiki='.$wiki.'&topic='.$link[1][$i];

					echo "<node TEXT=\"".cleanText($link[0][$i])."\" WIKILINK=\"".cleanWikiLink($wikilink)."\" MMLINK=\"".$mmlink."\" STYLE=\"fork\">\n";
					//echo '<node TEXT="T"  STYLE="fork">/n';
					echo "</node>\n";
					$i++;
				}
			}
			$contents = mb_strstr($contents,$linkEnd);
			$contents = mb_substr($contents,mb_strlen($linkEnd));
		}
	}

	if ($openSubChap == TRUE)
	{
		echo "</node>\n";
	}
	if ($openChap == TRUE)
	{
		echo "</node>\n";
	}

	echo "</node>\n";
	echo "</map>\n";

	$time_end = microtime(true);
	$time = $time_end-$time_start;
  	//echo '<HTML><p>'.$time.' micro seconds</p><p>Count: '.$counter.'</p></HTML>';

    //-------------------------------------------------------------------------------------------
	// END OF MAIN PROCESS
	//===========================================================================================


  	//-------------------------------------------------------------------------------------------
	// Functions to clean text from special caracters
	//-------------------------------------------------------------------------------------------

  function cleanText($text) {
		$trans = array("=" => "", "[" => "", "]" => "", "{" => "", "}" => "", "_" => " ", "'" => "", "|" => "/",  "?" => "", "*" => "-", "\"" => "'");
		$clean = mb_strtr ($text, $trans);
		// Experimental remove a lot of reutrns (\n)
		$transW = array( "\n" => "");
		$clean = mb_strtr ($clean, $transW );

		$clean = MediaWikiZhConverter::convert($clean,"zh-cn","utf-8");
		return explode(' ',trim($clean))[0];
	}

  function cleanWikiLink($text) {
		$trans = array("=" => "", "[" => "", "]" => "", "{" => "", "}" => "");
		$clean = mb_strtr ($text, $trans);
		return explode('_',$clean)[0];
	}

	//-------------------------------------------------------------------------------------------
	// Functions to create ToolTip Text
	// Strategy: Text until the next chapter starts, but no more than n (100?) characters.
	//-------------------------------------------------------------------------------------------

	function createToolTipText($text, $len) {
		return "";
		global $chapStart;
		//echo '<br> TTTEXT: '.$text;
		$tttext = removeTags($text);
		//echo '<br> TTTEXT: '.$tttext;
		$i = $len;
		if (mb_strpos($text, $chapStart) >-1) {
			$i = min(mb_strpos($text, $chapStart), $len);
		}
		$tttext = mb_substr($tttext, 0, $i);
		$tttext = cleanText($tttext);
		//$tttext = trim($tttext);
		//echo '<br> TTTEXT: '.$tttext;
		//echo $tttext;
		return $tttext.' [...]';
	}

	// This alghoritm maybe should by used for all the parsing
	function removeClassInfo($text)
	{
		global $catStart, $catEnd;
		$n = mb_strpos($text, $catStart);
		while ($n > -1) {
			$o = mb_strpos($text, $catStart,$n+1);
			$c = mb_strpos($text, $catEnd,$n+1);
			if ( $c > -1 && ($c < $o || !$o)) {
				$text = mb_substr_replace($text,"",$n,$c+1-$n);
				$n = mb_strpos($text, $catStart);
			}
			else {
				$n = $o;
			}
		}
		return $text;
	}

	function removeComments($text)
	{
		$cStart = "<";
		$cEnd	= ">";
		$n = mb_strpos($text, $cStart);

		while ($n > -1) {

			$o = mb_strpos($text, $cStart,$n + mb_strlen($cStart));
			$c = mb_strpos($text, $cEnd,$n + mb_strlen($cStart));
			if ( $c > -1 && ($c < $o || !$o)) {

				$text = mb_substr_replace($text,"",$n,$c + mb_strlen($cEnd)-$n);
				$n = mb_strpos($text, $cStart);
			}
			else {
				$n = $o;
			}
		}
		return $text;
	}


	function removeTags($text)
	{
		$linkStart = "[";
		$linkEnd = "]";
		$n = mb_strpos($text, $linkStart);
		while ($n > -1) {
			$o = mb_strpos($text, $linkStart,$n+1);
			$c = mb_strpos($text, $linkEnd,$n+1);
			if ( $c > -1 && ($c < $o || !$o)) {
				$tag = mb_substr($text,$n + mb_strlen($linkStart),$c-$n - mb_strlen($linkEnd));
				$s = mb_strpos($tag,'|');
				$spec = mb_strpos($tag,':');
				if ($spec > -1) {
					$tag = "";

				}
				elseif ($s > -1) {
					$tag = mb_substr($tag,$s+1,mb_strlen($tag)- $s);

				}
				$text = mb_substr_replace($text,$tag,$n,$c+1-$n);
				$n = mb_strpos($text, $linkStart);
			}
			else {
				$n = $o;
			}
		}

		return $text;
	}

	//extension
	function mb_substr_replace($string, $replacement, $start, $length=NULL) {
		if (is_array($string)) {
			$num = count($string);
			// $replacement
			$replacement = is_array($replacement) ? array_slice($replacement, 0, $num) : array_pad(array($replacement), $num, $replacement);
			// $start
			if (is_array($start)) {
				$start = array_slice($start, 0, $num);
				foreach ($start as $key => $value)
					$start[$key] = is_int($value) ? $value : 0;
			}
			else {
				$start = array_pad(array($start), $num, $start);
			}
			// $length
			if (!isset($length)) {
				$length = array_fill(0, $num, 0);
			}
			elseif (is_array($length)) {
				$length = array_slice($length, 0, $num);
				foreach ($length as $key => $value)
					$length[$key] = isset($value) ? (is_int($value) ? $value : $num) : 0;
			}
			else {
				$length = array_pad(array($length), $num, $length);
			}
			// Recursive call
			return array_map(__FUNCTION__, $string, $replacement, $start, $length);
		}
		preg_match_all('/./us', (string)$string, $smatches);
		preg_match_all('/./us', (string)$replacement, $rmatches);
		if ($length === NULL) $length = mb_strlen($string);
		array_splice($smatches[0], $start, $length, $rmatches[0]);
		return join($smatches[0]);
	}

	function mb_strtr($str,$map){
		$out="";
		$strLn=mb_strlen($str);
		$maxKeyLn=1;
		foreach($map as $key=>$val){
			$keyLn=mb_strlen($key);
			if($keyLn>$maxKeyLn){
				$maxKeyLn=$keyLn;
			}
		}
		for($offset=0; $offset<$strLn; ){
			for($ln=$maxKeyLn; $ln>=1; $ln--){
				$cmp=mb_substr($str,$offset,$ln);
				if(isset($map[$cmp])){
					$out.=$map[$cmp];
					$offset+=$ln;
					continue 2;
				}
			}
			$out.=mb_substr($str,$offset,1);
			$offset++;
		}
		return $out;
	}

?>
