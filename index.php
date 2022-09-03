<?php
//echo json_encode($_GET);
if ($_GET["embed"] != "true"){
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
<h4><a href='https://www.nmke.de'>NMKE</a></h4>
<ul>
<li><a href='https://www.nmke.de/'>Startseite</a></li>
<li><a href='https://www.nmke.de/ess.cdo'>Ea Scriptura Sancta</a></li>
<li><a href='https://wiby.me/'>Wiby.me - Suchmaschine</a></li>
</ul>
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

$cmd = "bib/".$_GET["src"]." ";
if ($_GET["query"] == "-l") $cmd = $cmd . $_GET["query"];
elseif ($_GET["query"] == "") $cmd = "echo \"Help\\n0:0\\tRead the syntax section!\"";
else $cmd = $cmd . "-W " . $_GET["query"];
$txt = shell_exec(escapeshellcmd($cmd));

if ($_GET["embed"] != "true"){
	//echo $txt;
	if ($_GET["query"] == "-l"){
		echo "<pre><code>".$txt."</code></pre>";
		$li = explode("/\\n/u",$txt);
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

?>
