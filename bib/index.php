<!doctype html>
<html>
<body>
<ul>
<?php

$ls = shell_exec("ls | grep -v index.php");
$bibles = explode("\n",$ls);
foreach ($bibles as $bible){
	echo "<li><a href='https://www.nmke.de/$bible.bib'>$bible</a></li>\n";	
}

?>
</ul>
</body>
</html>
