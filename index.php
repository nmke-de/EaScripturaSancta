<?php

function parsequery ($query) {
	$filter = array();
	$filter["search"] = "\x00";
	$filter["book"] = "\x00";
	$filter["chapter"] = 1;
	$filter["chapter-end"] = 150;
	$filter["verse"] = 1;
	$filter["verse-end"] = 176;

	if ($query == "/.*" || preg_match("/^\/.$/u", $query))
		return $filter;

	if (preg_match("/^([1-9]?[a-zA-Z\x80-\xff ]+)/", $query, $matched)) {
		$filter["search"] = ".*";
		$filter["book"] = trim($matched[1]);
		$query = substr($query, strlen($matched[1]));
	} else if (preg_match("/^\/(.*)$/u", $query, $matched)) {
		$filter["search"] = $matched[1];
		$filter["book"] = ".*";
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

function ls() {
	$ls = shell_exec("ls bib/ | grep -v index.php | sort | uniq | cut -d. -f1");
	$bibles = explode("\n",$ls);
	echo "<ul>\n";
	foreach ($bibles as $bible){
		if ($bible)
			echo "<li><a href='./?src=$bible'>$bible</a></li>\n";	
	}
	echo "</ul>\n";
}

$f = fopen("bib/" . $_GET["src"] . ".tsv", "r");

$embed = $_GET["embed"] == "true";
$debug = $_GET["debug"] == "on";

if (!$embed) {
	echo "<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8' />
<link rel='stylesheet' type='text/css' href='./lunil.css' />
<link rel='shortcut icon' type='image/svg' href='icon.svg' />
<title>ESS - $_GET[src]</title>
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
<li><a href='index.php'>Home</a></li>
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
<li>[book]</li>
<li>[book] [chapter]</li>
<li>[book] [chapter]-[chapter]</li>
<li>[book] [chapter]:[verse]-[verse]</li>
<li>[book] [chapter]:[verse]-[chapter]:[verse]</li>
<li>/[search term]</li>
<li>[book]/[search term]</li>
<li>[book] [chapter]/[search term]</li>
</div>
<h2 style='text-transform:uppercase;'>".$_GET["src"]."</h2>
<form action='index.php' method='get'>
<input type='text' id='q' placeholder='Amos 1:1' style='font-size:1.2rem;' name='query' value='".$_GET["query"]."' />
<input type='hidden' name='src' value='".$_GET["src"]."'/>
<button>Send</button>
<label name='debug'><input type='checkbox' name='debug'/>Debug</label>
</form>\n";
}

$filter = parsequery($_GET["query"]);
if ($debug)
	echo "<code>" . json_encode($filter) . "</code>\n";
$result = matchverses($f, $filter);

//echo "<code>" . json_encode($result) . "</code>\n";

if (!$_GET["src"]) {
	ls();
} else {
	$lastbook = "";
	foreach ($result as $current) {
		if ($lastbook != $current[0]) {
			if ($lastbook != "")
				if ($embed)
					echo "\n";
				else
					echo "</dl>\n";
			$lastbook = $current[0];
			if ($embed)
				echo "$current[0]\n\n";
			else
				echo "<h2>$current[0]</h2>\n<dl>\n";
		}
		if ($embed)
			echo "$current[3]:$current[4]\t$current[5]";
		else
			echo "<dt>$current[3]:$current[4]</dt><dd>$current[5]</dd>\n";
	}
	if (!$embed)
		echo "</dl>";
}
if (!$embed)
	echo "</article>\n</div>\n</body>\n</html>\n";

fclose($f);

?>
