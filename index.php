<head>
	<style>
		body{
			padding-top: 50px;
			background-color: black;
			color: white;
			text-align: center;
			font-family: arial;
		}
		
		a:link{
			color:white;
		}
		
		a:visited{
			color:white;
		}
		
		a:active{
			color:#ccffcc;
		}
		
		a:hover{
			color:#ccffcc;
		}
		
		.pagelink:link{
			color:#eeffee;
		}
		
		.pagelink:visited{
			color:#eeffee;
		}
		
		.pagelink:active{
			color:#ccffcc;
		}
		
		.pagelink:hover{
			color:#ccffcc;
		}
		
		.clickable{
			cursor: pointer;
		}
		
		.currentfilter:link{
			color:#ccffcc;
		}
	</style>
	<script type='text/javascript'>
		function createCookie(name,value,days) {
			if (days) {
				var date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				var expires = "; expires="+date.toGMTString();
			}
			else var expires = "";
			document.cookie = name+"="+value+expires+"; path=/";
			document.getElementById(name).src = "animals/egg.png";
			document.getElementById(name).onclick = function(){ eraseCookie(name);};
		}

		function readCookie(name) {
			var nameEQ = name + "=";
			var ca = document.cookie.split(';');
			for(var i=0;i < ca.length;i++) {
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
			}
			return null;
		}

		function eraseCookie(name) {
			createCookie(name,"",-1);
			document.getElementById(name).src = "animals/egg2.png";
			document.getElementById(name).onclick = function(){ createCookie(name,1,700);};
		}
		
		function ShowList(cl){
			$(".game").hide();
			$("."+cl).show();
			$(".filter-btn").removeClass("currentfilter");
			$(".btn-"+cl).addClass("currentfilter");
		}
		
		
	</script>
	<script src='../../../js/analytics.js'></script>
	<script src='../../../js/jquery.js'></script>
</head>
<body>

<?php

//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

$entriesPerFile = 100;
$scrapesPerRefresh = 250;

$event = (isset($_GET["event"]) ? "ludum-dare-".intval($_GET["event"]) : "ludum-dare-35");

//$animals = Array("chicken1", "chicken2", "chicken3");
$animals = Array();
$animalIndex = 0;


function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}
function get_inner_html( $node ) {
    $innerHTML= '';
    $children = $node->childNodes; 
    foreach ($children as $child) {
        $innerHTML .= $child->ownerDocument->saveXML( $child );
    }
    return $innerHTML;
} 

function getElementsByClass(&$parentNode, $tagName, $className) {
    $nodes=array();

    $childNodeList = $parentNode->getElementsByTagName($tagName);
    for ($i = 0; $i < $childNodeList->length; $i++) {
        $temp = $childNodeList->item($i);
        if (stripos($temp->getAttribute('class'), $className) !== false) {
            $nodes[]=$temp;
        }
    }

    return $nodes;
}

function innerHTML( $contentdiv ) {
	$r = '';
	$elements = $contentdiv->childNodes;
	foreach( $elements as $element ) { 
		if ( $element->nodeType == XML_TEXT_NODE ) {
			$text = $element->nodeValue;
			// IIRC the next line was for working around a
			// WordPress bug
			//$text = str_replace( '<', '&lt;', $text );
			$r .= $text;
		}	 
		// FIXME we should return comments as well
		elseif ( $element->nodeType == XML_COMMENT_NODE ) {
			$r .= '';
		}	 
		else {
			$r .= '<';
			$r .= $element->nodeName;
			if ( $element->hasAttributes() ) { 
				$attributes = $element->attributes;
				foreach ( $attributes as $attribute )
					$r .= " {$attribute->nodeName}='{$attribute->nodeValue}'" ;
			}	 
			$r .= '>';
			$r .= innerHTML( $element );
			$r .= "</{$element->nodeName}>";
		}	 
	}	 
	return $r;
}

$data = Array();
$data = json_decode(file_get_contents("out-$event.json"), true);
//$threshold = (isset($_GET["threshold"]) ? intval($_GET["threshold"]) : 17);
$threshold = 17;

$savedNum = $data["GAMES_DONE"];
$unsavedNum = $data["GAMES_REMAINING"];

if(strtotime($data["TIME"]) - Time() < 0){
	//print_r($data);
	$data = Array();
	$html = file_get_contents("http://ludumdare.com/compo/$event/?action=misc_links");
	$dom = new DOMDocument;
	$dom->loadHTML($html);
	$savedNum = 0;
	$unsavedNum = 0;

	$trlist = $dom->getElementsByTagName('tr');

	$count = 0;
	foreach ($trlist as $tr) {
		$url = "unknown";
		$tdnodes = $tr->getElementsByTagName('td');
		$tdnum = 0;
		$entryID = 0;
		$absurl = "unknown";
		$gameName = "unknown";
		$gameAuthor = "unknown";
		$platforms = Array();
		$votesCast = 0;
		$votesReceived = 0;
		$entryType = "unknown";
		$entryDescription = "unknown";
		$comments = Array();
		
		foreach ($tdnodes as $td) {
			$tdnum = $tdnum % 6;
			switch($tdnum){
				case 0:
					$linkNode = null;
					$links = $tr->getElementsByTagName('a');
					foreach ($links as $link) {
						$tmpurl = $link->getAttribute('href');
						if(startsWith($tmpurl, "?action=preview&uid=")){
							$url = $tmpurl;
							$linkNode = $link;
						}else{
							$platform = Array();
							$platform["TEXT"] = $link->textContent;
							$platform["LOCATION"] = $link->getAttribute("href");
							$platforms[] = $platform;
						}
					}
		
					$entryID = intval(str_replace("?action=preview&uid=", "", $url));
					$absurl = "http://ludumdare.com/compo/$event/".$url;
					
					$gameName = str_replace("<", "&lt", $linkNode->textContent);
					$gameName = str_replace(">", "&gt", $gameName);
				break;
				case 1:
					$gameAuthor = $td->textContent;
				break;
				case 2:
				
				break;
				case 3:
					$votesReceived = intval($td->textContent);
				break;
				case 4:
					$votesCast = intval($td->textContent);
				break;
				case 5:
					$entryType = $td->textContent;
				break;
			}
			$tdnum++;
		}
		
		if($votesReceived >= $threshold){
			$savedNum++;
			continue;
		}else{
			$unsavedNum++;
		}
		
		$data["GAMES"][] = Array("ID" => $entryID, "AUTHOR" => $gameAuthor, "NAME" => $gameName, "VOTES" => $votesReceived, "CAST" => $votesCast, "URL" => $absurl, "PLATFORMS" => $platforms);
	}
	$data["TIME"] = date("j F Y H:i:s", Time() + 60);
	$data["GAMES_DONE"] = $savedNum;
	$data["GAMES_REMAINING"] = $unsavedNum;
	file_put_contents("out-$event.json", json_encode($data)); 
}

$nextUpdate = strtotime($data["TIME"]) - Time();

function cmp($a, $b){
	if($a["VOTES"] != $b["VOTES"]){
		return $a["VOTES"] < $b["VOTES"];
	}
	return $a["CAST"] < $b["CAST"];
}

usort($data["GAMES"], "cmp");

$num = rand(0, count($data["GAMES"]));
$rndName = $data["GAMES"][$num]["AUTHOR"];
$rndURL = $data["GAMES"][$num]["URL"];

print "<div align='center'><a href='http://ludumdare.com'><img src='ldlogo.png' style='width: 700px; border: 0px;'></a><br /><table style='text-align: center; height: 60px;' align='center;'><tr><td>";
//print "<img src='animals/chicken1.png' style='height: 60px'>";
print "</td><td style='padding: 0 30;'><b style='font-size: 20px;'>Let's help <a href='$rndURL'>$rndName</a> build a chicken coop! Hooray!</b></td><td>";
//print "<img src='animals/chicken2r.png' style='height: 60px'>";
print "</b></tr></table></div>";

print "For people to get a rating, they need <b>$threshold votes.</b><br />";
print "This page's content updates once per minute. Next update in $nextUpdate seconds.<br />";
print "Click the <img src='animals/egg3.png' style='position: relative; top: 7px;'> to mark it as done!";

print "<p><span style='font-size: 20px; font-weight: bold;'>$savedNum games reviewed! $unsavedNum to go!</span></p>";

print "<p>Filter: 
       <b><a href='#' class='filter-btn btn-all currentfilter' onclick='ShowList(\"all\"); return false;'>ALL</a></b>
       <b><a href='#' class='filter-btn btn-Windows' onclick='ShowList(\"Windows\"); return false;'>WINDOWS</a></b>
       <b><a href='#' class='filter-btn btn-Mac' onclick='ShowList(\"Mac\"); return false;'>MAC</a></b>
       <b><a href='#' class='filter-btn btn-Linux' onclick='ShowList(\"Linux\"); return false;'>LINUX</a></b>
       <b><a href='#' class='filter-btn btn-Android' onclick='ShowList(\"Android\"); return false;'>ANDROID</a></b>
       <b><a href='#' class='filter-btn btn-Web' onclick='ShowList(\"Web\"); return false;'>WEB</a></b>
       <b><a href='#' class='filter-btn btn-Java' onclick='ShowList(\"Java\"); return false;'>JAVA</a></b>
       <b><a href='#' class='filter-btn btn-Flash' onclick='ShowList(\"Flash\"); return false;'>FLASH</a></b>
       <b><a href='#' class='filter-btn btn-VR' onclick='ShowList(\"VR\"); return false;'>VR</a></b>
       <b><a href='#' class='filter-btn btn-Paper' onclick='ShowList(\"Paper\"); return false;'>PAPER</a></b>
       <b><a href='#' class='filter-btn btn-Media' onclick='ShowList(\"Media\"); return false;'>MEDIA</a></b>
       <b><a href='#' class='filter-btn btn-Source' onclick='ShowList(\"Source\"); return false;'>SOURCE</a></b></p>";

print "<div style='align: center'>";
$votesArchive = -1;
foreach($data["GAMES"] as $i => $d){
	$author = $d["AUTHOR"];
	$title = $d["NAME"];
	$votes = $d["VOTES"];
	$url = $d["URL"];
	$id = $d["ID"];
	$platforms = $d["PLATFORMS"];
	
	if($votes >= $threshold){
		continue;
	}
	
	if($votes != $votesArchive){
		$votesArchive = $votes;
		print "<table align='center' style='margin-top: 20px;'><tr>";
		if($animalIndex < count($animals)){
			print "<td rowspan='2'><img src='animals/".$animals[$animalIndex].".png' style='margin-right: 20px;'></td>";
		}
		print "<td style='text-align: center;'><h2 style='color: #ccffcc; margin-top: 10px; margin-bottom: 0px;'><b>Games with $votes votes</b></h2>";
		print "<span style='font-size: 12px;'>".($threshold - $votes)." more vote".((($threshold - $votes) > 1) ? "s" : "")." needed!</span></td>";
		if($animalIndex < count($animals)){
			print "<td rowspan='2'><img src='animals/".$animals[$animalIndex]."r.png' style='margin-left: 20px;'></td>";
		}
		print "</tr>";
		
		print "<tr><td><div style='text-align: center; width: 100%;'>";
		$total = 0;
		for($j = 0; $j < min(17, $votes); $j++){
			print "<img src='animals/egg4.png' style='margin: 0 2; width: 10px;'>";
			$total++;
		}
		for($j = 0; $j < min(17 - $total, $threshold - $votes); $j++){
			print "<img src='animals/egg3.png' style='margin: 0 2; width: 10px;'>";
		}
		print "</div></td>";
		print "</tr>";
		print "</table>";
		$animalIndex++;
	}
	
	$platformList = Array();
	$isWin = false;
	$isMac = false;
	$isLinux = false;
	$isWeb = false;
	$isSource = false;
	$isPaper = false;
	$isMedia = false;
	$isJava = false;
	$isAndroid = false;
	$isFlash = false;
	$isVR = false;
	foreach($platforms as $j => $p){
		$pass = false;
		if((strpos(strtolower($p["TEXT"]), "win") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "exe") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all os") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all operating") !== false) ||
		   (strtolower($p["TEXT"]) == "all") ||
		   (strpos(strtolower($p["TEXT"]), "all versions") !== false)){
			$isWin = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "mac") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "os/x") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "os x") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "osx") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all os") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all operating") !== false) ||
		   (strtolower($p["TEXT"]) == "all") ||
		   (strpos(strtolower($p["TEXT"]), "all versions") !== false)){
			$isMac = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "linux") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all os") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "all operating") !== false) ||
		   (strtolower($p["TEXT"]) == "all") ||
		   (strpos(strtolower($p["TEXT"]), "all versions") !== false)){
			$isLinux = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "web") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "itch") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "online") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "jolt") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "newground") !== false) ||
			strpos(strtolower($p["TEXT"]), "html") !== false){
			$isWeb = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "source") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "src") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "love") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "dropbox") !== false)){
			$isSource = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "android") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "google play") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "play store") !== false)){
			$isAndroid = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "timelapse") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "video") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "scoreboard") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "post") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "homepage") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "soundtrack") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "gameplay") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "blog") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "youtube") !== false)){
			$isMedia = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "jar") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "java") !== false)){
			$isJava = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "flash") !== false)
		   ){
			$isFlash = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "vr") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "virtual reality") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "vive") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "oculus") !== false) || 
		   (strpos(strtolower($p["TEXT"]), "rift") !== false)
		   ){
			$isVR = true;
			$pass = true;
		}
		if((strpos(strtolower($p["TEXT"]), "pdf") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "doc") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "rtf") !== false) ||
		   (strpos(strtolower($p["TEXT"]), "odt") !== false)){
			$isPaper = true;
			$pass = true;
		}
		
		if(!$pass){
			$platformList[] = "<font>".$p["TEXT"]."</font>";
		}
	}
	if($isWin){
		$platformList[] = "Windows";
	}
	if($isMac){
		$platformList[] = "Mac";
	}
	if($isLinux){
		$platformList[] = "Linux";
	}
	if($isWeb){
		$platformList[] = "Web";
	}
	if($isSource){
		$platformList[] = "Source";
	}
	if($isPaper){
		$platformList[] = "Paper";
	}
	if($isMedia){
		$platformList[] = "Media";
	}
	if($isJava){
		$platformList[] = "Java";
	}
	if($isAndroid){
		$platformList[] = "Android";
	}
	if($isFlash){
		$platformList[] = "Flash";
	}
	if($isFlash){
		$platformList[] = "VR";
	}
	
	print "<p class='game all ";
	print implode(" ", $platformList);
	print"'>";
	print "<table align='center'><tr><td>";
	if(isset($_COOKIE["DONE-$id"])){
		print "<img class='clickable' id='DONE-$id' src='animals/egg.png' alt='Mark done' onclick='eraseCookie(\"DONE-$id\")'>";
	}else{
		print "<img class='clickable' id='DONE-$id' src='animals/egg2.png' alt='Mark done' onclick='createCookie(\"DONE-$id\",1,700)'>";
	}
	print "</td><td style='text-align: center;'><span font-weight: bold;'><a href='$url' class='pagelink'>$title</a></span><br /><span style='font-size: 12px;'>by $author - ";
	print implode(", ", $platformList);
	print "</span></td></tr></table></p>";
	
	continue;
}
print "</div>";
?>

<p>
	Art from <a href='http://stardewvalley.net/'>Stardew Valley</a>
</p>

</body>