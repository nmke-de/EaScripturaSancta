<!doctype html>
<html>
<body>
<ul>
<?php

$ls = shell_exec("ls | grep -v index.php");
$bibles = explode("\n",$ls);
foreach ($bibles as $bible){
	if ($bible)
		echo "<li><a href='$bible'>$bible</a></li>\n";	
}

?>
</ul>
</body>
</html>
