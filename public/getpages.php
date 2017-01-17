<?php
	header('Content-Type:text/text; charset=utf-8');

	#for convert zh-TW to zh-cn
	define("MEDIAWIKI_PATH", "/var/www/html/mediawiki-1.26.2");
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
	$wiki = $_GET['Wiki'];
 	$topic = $_GET['Topic'];
	//echo $topic;
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

	$url = 'http://'.$wiki.$index_path.'/index.php?title='.$topic.'&action=raw';
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


	$ch = curl_init();
	$timeout = 5; // set to zero for no timeout
	$useragent=$_SERVER['HTTP_USER_AGENT']; // get user agent
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$debug = false;
	if ($debug)
	{//get test data from local
		$file = "animal.txt";
	  $contents = file_get_contents($file);
	}
	else
	{//get data from wikipedia
		$contents = curl_exec($ch);
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
	$contents = preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))",$contents);

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

	while (strpos($contents,$linkStart) > -1 ||
	       strpos($contents,$wwwLinkStart) > -1 ||
		   strpos($contents,$chapStart) > -1 ||
		   strpos($contents,$subChapStart) > -1)
	{
		$counter++;
		// is the next object to parse a section or a wikilink?

		$iChap = strpos($contents, $chapStart);
		$iSubChap = strpos($contents, $subChapStart);
		$iLink = strpos($contents, $linkStart);
		$iWwwLink = strpos($contents, $wwwLinkStart);

		//echo '<br>Chap: '.$iChap.' Link:'.$iLink.' WWW:'.$iWwwLink.'<br>';

		//-----------------------------------------
		// Create Chapter Nodes
		//-----------------------------------------
		if ($iChap > -1 && ($iChap < $iLink || !$iLink) && ($iChap < $iWwwLink || !$iWwwLink) && ($iChap < $iSubChap || !$iSubChap))
		{

			$contents = strstr($contents, $chapStart);
			$contents = mb_substr($contents, strlen($chapStart));
			$Chap = mb_substr($contents,0, strpos($contents,$chapEnd));

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
				$ttorg = mb_substr($contents,strpos($contents,$chapEnd)+2, 500);
				$tooltip = createToolTipText($ttorg, 150);

				$wikilink  = 'http://'.$wiki.$access_path.'/'.$topic.'#'.implode('.',explode('%',urlencode($wChap)));
				echo  "<node TEXT=\"".cleanText($Chap)."\" WIKILINK = \"".cleanWikiLink($wikilink)."\" TOOLTIPTEXT = \"".$tooltip."\" STYLE=\"bubble\">\n";
				//echo  '<node TEXT="'.cleanText($Chap).'" WIKILINK = "'.cleanWikiLink($wikilink).'"  STYLE="bubble">/n';
				//echo 'node TEXT="'.$Chap.'" STYLE="bubble"><br>';
				$openChap = TRUE;

			}

			$contents = strstr($contents,$chapEnd);
			$contents = mb_substr($contents, strlen($chapEnd));

		}

		//-----------------------------------------
		// Create SubChapter Nodes
		//-----------------------------------------
		if ($iSubChap > -1 && ($iSubChap < $iLink || !$iLink) && ($iSubChap < $iWwwLink || !$iWwwLink) && ($iSubChap <= $iChap || !$iChap))
		{
			//echo "SUbCHap";
			$contents = strstr($contents, $subChapStart);
			$contents = mb_substr($contents, strlen($subChapStart));
			$SubChap = mb_substr($contents,0, strpos($contents,$subChapEnd));

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
				$ttorg = mb_substr($contents,strpos($contents,$subChapEnd)+3, 500);
				//$tooltip = createToolTipText($ttorg, 150);

				$wikilink  = 'http://'.$wiki.$access_path.'/'.$topic.'#'.implode('.',explode('%',urlencode($wSubChap)));
				echo  "<node TEXT=\"".cleanText($SubChap)."\" WIKILINK = \"".cleanWikiLink($wikilink)."\" TOOLTIPTEXT = \"".$tooltip."\" STYLE=\"bubble\">\n";
				//echo  '<node TEXT="'.cleanText($Chap).'" WIKILINK = "'.cleanWikiLink($wikilink).'"  STYLE="bubble">/n';
				//echo 'node TEXT="'.$Chap.'" STYLE="bubble"><br>';
				$openSubChap = TRUE;
			}

			$contents = strstr($contents,$subChapEnd);
			$contents = mb_substr($contents, strlen($subChapEnd));
		}


		//-----------------------------------------
		// Create WWW Link Nodes
		//-----------------------------------------
		if ($iWwwLink > -1 && ($iWwwLink < $iLink || !$iLink) && ($iWwwLink < $iChap || !$iChap) && ($iWwwLink < $iSubChap || !$iSubChap))
		{

			$contents = strstr($contents, $wwwLinkStart);
			$contents = mb_substr($contents, strlen($wwwLinkStart));
			$wwwLink  = mb_substr($contents,0, strpos($contents,$wwwLinkEnd));
			if ($wwwLink !="")
			{
				$wwwLinkURL = 'http:'.substr($wwwLink, 0, strpos($wwwLink, " "));
				$wwwLinkName = substr($wwwLink, strpos($wwwLink, " "), strlen($wwwLink));
				echo "<node TEXT=\"".cleanText($wwwLinkName)."\" WEBLINK=\"".$wwwLinkURL."\" STYLE=\"fork\">\n";
				echo "</node>\n";
			}
			$contents = strstr($contents,$wwwLinkEnd);
			$contents = substr($contents,strlen($wwwLinkEnd));
		}

		//-----------------------------------------
		// Create WikiPage Nodes
		//-----------------------------------------
		if ($iLink > -1 && ($iLink < $iWwwLink || !$iWwwLink) && ($iLink < $iChap || !$iChap) && ($iLink < $iSubChap || !$iSubChap))
		{
			$contents = strstr($contents,$linkStart);
			$tag = substr($contents, strlen($linkStart), strpos($contents, $linkEnd)-strlen($linkStart));

			//echo $tag;

			// Keine Bilder etc...
			if (strpos($tag, ':') == FALSE)
			//$tag should be parsed seperately in future (applies for all nodes, end-node and chapter, to do the following things:
			// - if | exists: left part = wikilink, right part = name in text
			// - if : exists, decide, what to do... (add picture to mindmap?
			// - ...
			{
				//No dublicates

				if (in_array($tag, $link[0]) == FALSE)
				{
					if (strpos($tag, '|') != FALSE)
					{
						$wTag = substr($tag,0,strpos($tag,'|'));
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
			$contents = strstr($contents,$linkEnd);
			$contents = substr($contents,strlen($linkEnd));
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
		$clean = strtr ($text, $trans);
		// Experimental remove a lot of reutrns (\n)
		$transW = array( "\n" => "");
		$clean = strtr ($clean, $transW );

		$clean = MediaWikiZhConverter::convert($clean,"zh-cn","utf-8");
		return explode(' ',trim($clean))[0];
	}

  function cleanWikiLink($text) {
		$trans = array("=" => "", "[" => "", "]" => "", "{" => "", "}" => "");
		$clean = strtr ($text, $trans);
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
		if (strpos($text, $chapStart) >-1) {
			$i = min(strpos($text, $chapStart), $len);
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
		$n = strpos($text, $catStart);
		while ($n > -1) {
			$o = strpos($text, $catStart,$n+1);
			$c = strpos($text, $catEnd,$n+1);
			if ( $c > -1 && ($c < $o || !$o)) {
				$text = substr_replace($text,"",$n,$c+1-$n);
				$n = strpos($text, $catStart);
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
		$n = strpos($text, $cStart);

		while ($n > -1) {

			$o = strpos($text, $cStart,$n+strlen($cStart));
			$c = strpos($text, $cEnd,$n+strlen($cStart));
			if ( $c > -1 && ($c < $o || !$o)) {

				$text = substr_replace($text,"",$n,$c+strlen($cEnd)-$n);
				$n = strpos($text, $cStart);
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
		$n = strpos($text, $linkStart);
		while ($n > -1) {
			$o = strpos($text, $linkStart,$n+1);
			$c = strpos($text, $linkEnd,$n+1);
			if ( $c > -1 && ($c < $o || !$o)) {
				$tag = substr($text,$n+strlen($linkStart),$c-$n-strlen($linkEnd));
				$s = strpos($tag,'|');
				$spec = strpos($tag,':');
				if ($spec > -1) {
					$tag = "";

				}
				elseif ($s > -1) {
					$tag = substr($tag,$s+1,strlen($tag)- $s);

				}
				$text = substr_replace($text,$tag,$n,$c+1-$n);
				$n = strpos($text, $linkStart);
			}
			else {
				$n = $o;
			}
		}

		return $text;
	}
?>
