<?php

function parsequery ($query) {
	$filter = array();
	$filter["search"] = ".*";
	$filter["book"] = ".*";
	$filter["chapter"] = 1;
	$filter["chapter-end"] = 150;
	$filter["verse"] = 1;
	$filter["verse-end"] = 176;

	if (preg_match("/^([1-9]?[a-zA-Z ]+)/u", $query, $matched)) {
		$filter["book"] = trim($matched[1]);
		$query = substr($query, strlen($matched[1]));
	} else if (preg_match("/^\/(.*)$/u", $query, $matched)) {
		$filter["search"] = $matched[1];
		return $filter;
	} else return $filter;

	if (preg_match("/^[ :]?([1-9][0-9]*)/u", $query, $matched)) {
		$filter["chapter"] = (int)$matched[1];
		$filter["chapter-end"] = (int)$matched[1];
		$query = substr($query, strlen($matched[1]));
	} else if (preg_match("/^\/(.*)$/u", $query, $matched)) {
		$filter["search"] = $matched[1];
		return $filter;
	} else return $filter;

	if (preg_match("/^:([1-9][0-9]*)/u", $query, $matched)) {
		$filter["verse"] = (int)$matched[1];
		$filter["verse-end"] = (int)$matched[1];
		$query = substr($query, strlen($matched[0]));
	} else if (preg_match("/^-([1-9][0-9]*)$/u", $query, $matched)) {
		$filter["chapter-end"] = (int)$matched[1];
		return $filter;
	} else if (preg_match("/^\/(.*)$/u", $query, $matched)) {
		$filter["search"] = $matched[1];
		return $filter;
	} else return $filter;

	if (preg_match("/^-([1-9][0-9]*)$/u", $query, $matched)) {
		$filter["verse-end"] = (int)$matched[1];
		return $filter;
	} else if (preg_match("/^-([1-9][0-9]*)/u", $query, $matched)) {
		$filter["chapter-end"] = (int)$matched[1];
		$query = substr($query, strlen($matched[0]));
	} else return $filter;

	if (preg_match("/^:([1-9][0-9]*)$/u", $query, $matched)) {
		$filter["verse-end"] = (int)$matched[1];
	}
	return $filter;
}

function matchverses($file, $filter) {
	$verses = array();
	while (($line = fgets($file)) !== false) {
		$entry = explode("\t", $line);
		$bookfilter = (preg_match("/$filter[book]/ui", $entry[0]) || $filter["book"] == $entry[1]);
		$chapterrange = ($filter["chapter"] <= (int)$entry[3] && $filter["chapter-end"] >= (int)$entry[3]);
		$searchfilter = (preg_match("/$filter[search]/iu", $entry[5]));
		$versestart = ($filter["chapter"] == (int)$entry[3] ? $filter["verse"] <= (int)$entry[4] : true);
		$verseend = ($filter["chapter-end"] == (int)$entry[3] ? $filter["verse-end"] >= (int)$entry[4] : true);
		if ($bookfilter && $chapterrange && $versestart && $verseend && $searchfilter)
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
echo "<code>" . json_encode($filter) . "</code>\n";
$cmd = "bib/".$_GET["src"]." ";
if ($_GET["query"] == "-l") $cmd = $cmd . $_GET["query"];
elseif ($_GET["query"] == "") $cmd = "echo \"Help\\n0:0\\tRead the syntax section!\"";
else $cmd = $cmd . "-W " . $_GET["query"];
$result = matchverses($f, $filter);

echo "<code>" . json_encode($result) . "</code>\n";

if ($_GET["embed"] != "true"){
	$lastbook = "";
	foreach ($result as $current) {
		if ($lastbook != $current[0]) {
			if ($lastbook != "")
				echo "</dl>\n";
			$lastbook = $current[0];
			echo "<h2>$current[0]</h2>\n<dl>\n";
		}
		echo "<dt>$current[3]:$current[4]</dt><dd>$current[5]</dd>\n";
	}
	echo "</dl>";
	echo "</article>\n</div>\n</body>\n</html>\n";
}else{
	echo $result;
	//echo escapeshellcmd($cmd);
}

fclose($f);

?>
