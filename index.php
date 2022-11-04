<?php

// Associative array to filter verses
$filter = array();
$filter["search"] = ".*";
$filter["book"] = ".*";
$filter["chapter"] = 1;
$filter["chapter-end"] = 150;
$filter["verse"] = 1;
$filter["verse-end"] = 176;

$NUMBER = "[1-9][0-9]*";
$BOOK = "[1-9]?[a-zA-Z ]+";
$SEARCH = "/(.*)$";

$AFTERVERSE = "-($NUMBER):($NUMBER)|-($NUMBER)|(,$NUMBER)*";
$AFTERCHAPTER = "$SEARCH|-($NUMBER)|:($NUMBER)($AFTERVERSE)?";
$AFTERBOOK = "$SEARCH|[ :]?($NUMBER)($AFTERCHAPTER)?";

function parsequery ($query) {
	/*if ($query[0] == "/")
		return function ($entry) {return preg_match($query . "/ui", $entry[5]);};
	preg_match("/^($BOOK)($AFTERBOOK)$/u", $query, $matches);
	$book = $matches[1];
	if ($matches[2][0] == "/")
		return function ($entry) {return (preg_match($book, $entry[0]) || $book = $entry[1]) && preg_match($matches[2] . "/ui", $entry[5]);};
	;*/
	$filter = array();
	$filter["search"] = ".*";
	$filter["book"] = ".*";
	$filter["chapter"] = 1;
	$filter["chapter-end"] = 150;
	$filter["verse"] = 1;
	$filter["verse-end"] = 176;
	if (preg_match("/^([1-9]?[a-zA-Z ]+)/u", $query, $matched)) {
		$filter["book"] = $matched[1];
		$query = substr($query, strlen($matched[1]));
	} else if (preg_match("/\/(.*)$/u", $query, $matched)) {
		$filter["search"] = $matched[1];
		return $filter;
	} else return $filter;
	return $filter;
}

function matchverses($file) {
	$verses = array();
	while (($line = fgets($file)) !== false) {
		$entry = explode("\t", $line);
		if ((preg_match("/" . $filter["book"] . "/ui", $entry[0]) || $filter["book"] == $entry[1]) && $filter["chapter"] <= (int)$entry[3] && $filter["chapter-end"] >= (int)$entry[3] && $filter["verse"] <= (int)$entry[4] && $filter["verse-end"] >= (int)$entry[4] && preg_match("/" . $filter["search"] . "/ui", $entry[5]))
			$verses[] = $entry;
	}
	return $verses;
}

$f = fopen("bib/" . $_GET["src"] . ".tsv", "r");

if ($_GET["embed"] != "true") {
	echo "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8' />
<link rel='stylesheet' type='text/css' href='lunil.css' />
<link rel='shortcut icon' type='image/svg' href='icon.svg' />
<title>ESS - elb</title>
</head>
<body>
<div id=\"box\">
<nav>
<h3><a style='float:left;' href='index.php'><img src='icon.svg' 
	alt='ESS-Icon' width='20' height='20'/></a>
Navigation</h3>
<a href='https://www.nmke.de/impressum.cdo'>Impressum</a>
<div class='dd'>
<h4><a href='index.php'>ESS</a></h4>
<ul>
<li><a href='index.php'>Startseite</a></li>
<li><a href='https://github.com/nmke-de/EaScripturaSancta'>Source Code</a></li>
<hr>\n";
	$lastbook = "";
	while (($line = fgets($f)) !== false) {
		$entry = explode("\t", $line);
		if ($lastbook !== $entry[0]) {
			$lastbook = $entry[0];
			echo "<li><a href='index.php?query=" . $entry[1] . "&src=" . $_GET["src"] . "'>" . $entry[0] . "</a></li>\n";
		}
	}
	fseek($f, 0);
	echo "</ul>
</div>
</nav>
<article id='main-article' style='display:block;'>
<h1>Ea Scriptura Sancta</h1>
<div id='syntax'><h2 style='text-align:center;'>Syntax</h2>
<ul>
<li>[Buch]</li>
<li>[Buch] [Kapitel]</li>
<li>[Buch] [Kapitel]:[Vers](,[Vers])...</li>
<li>[Buch] [Kapitel]-[Kapitel]</li>
<li>[Buch] [Kapitel]:[Vers]-[Vers]</li>
<li>[Buch] [Kapitel]:[Vers]-[Kapitel]:[Vers]</li>
<li>/[Suchbegriff]</li>
<li>[Buch]/[Suchbegriff]</li>
<li>[Buch] [Kapitel]/[Suchbegriff]</li>
<li><code>-l</code></li>
</ul><p>ESS hat Probleme mit Nicht-ASCII-Zeichen. Daher bietet es sich an, die Alternativnamen zu nutzen, die eingeklammert bei <code>-l</code> stehen.</p>
</div>
<h2 style='text-transform:uppercase;'>".$_GET["src"]."</h2>
<form action='index.php' method='get'>
<input type='text' id='q' placeholder='Amos 1:1' style='font-size:1.2rem;' name='query' value='".$_GET["query"]."' />
<input type='hidden' name='src' value='".$_GET["src"]."'/>
<button>Send</button>
</form>\n";
}

$filter = parsequery($_GET["query"]);
echo json_encode($filter);
$cmd = "bib/".$_GET["src"]." ";
if ($_GET["query"] == "-l") $cmd = $cmd . $_GET["query"];
elseif ($_GET["query"] == "") $cmd = "echo \"Help\\n0:0\\tRead the syntax section!\"";
else $cmd = $cmd . "-W " . $_GET["query"];
$txt = "";
if ($_GET["query"][0] == "/") $txt = json_encode(matchverses($f));
else $txt = shell_exec(escapeshellcmd($cmd));

if ($_GET["embed"] != "true"){
	//echo $txt;
	if ($_GET["query"] == "-l"){
		echo "<pre><code>".$txt."</code></pre>";
		$li = explode("/\n/gm",$txt);
		echo json_encode($li);
		$txt = "";
		foreach($li as $it){
			if(!$it) continue;
			echo "<ol>";
			echo "<li>".$it."</li>";
			echo "</ol>";
			$bookid = explode("(",$it);
			$bookid = $bookid[1];
			$bookid = explode(")",$bookid);
			$bookid = $bookid[0];
			echo $bookid;
			$txt = $txt."\n<li><a href='https://www.nmke.de/EaScripturaSancta/?query=$bookid&src=".$_GET["src"]."'>$it</a></li>";
		}
		//$txt = preg_replace("/\\n/u","</li>\n<li>",$txt);
		$txt = "<ol>".$txt."\n</ol>";
	}else if ($_GET["query"][0] == "/") {
		$lastbook = "";
		foreach ($txt as $entry) {
			if ($lastbook != $entry[0]) {
				if ($lastbook != "")
					echo "</table>\n";
				$lastbook = $entry[0];
				echo "<table><caption>$entry[0]</caption>\n";
			}
			echo "<tr><td>$entry[3]:$entry[4]</td><td>$entry[5]</td></tr>\n";
			//echo $entry[3] . ":" . $entry[4] . "\t" . $entry[5] . "<br />\n";
		}
		echo "</table>";
	}else if(!preg_match("/^Unknown reference: /u",$txt)){
		/*"if(!txt.match(/^Unknown reference: /)){
				txt = txt.replace(/^(.*?)\\n/g,(match,bname)=>{return '<h3>'+bname+'</h3>';});
				txt = txt.replace(/\\n((\\d )?[^0-9]*?)\\n/g,(match,bname)=>{return '<h3>'+bname+'</h3>';});
				txt = txt.replace(/\\n/g,'<br />');
			}"*/
		$txt = preg_replace_callback("/^(.*?)\\n/u",function($match){return "<h3>".$match[1]."</h3>";},$txt);
		$txt = preg_replace_callback("/\\n((\\d )?[^0-9]*?)\\n/u",function($match){return "<h3>".$match[1]."</h3>";},$txt);
		$txt = preg_replace("/\\n/u","<br />",$txt);
	}
	echo $txt;
	echo "</article>\n</div>\n</body>\n</html>\n";
}else{
	echo $txt;
	//echo escapeshellcmd($cmd);
}

fclose($f);

?>
