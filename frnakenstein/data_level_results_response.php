<?php
$analysis = strip_tags (htmlspecialchars(escapeshellcmd($_POST['analysis'])));
echo "$analysis is analysis";
$toScan = "/var/www/subdirectories_for_interface/diffexpress_output/$analysis";
echo $toScan;
echo "is the place to scan!!!!";
$files = scandir("$toScan");
$imagePath = "test_output";
foreach($files as $file)
{
	if ($file !== "." and $file !== "..")
	{
		$pattern = "/(.*).png/";
		preg_match($pattern, $file, $matches);
		$title = $matches[1];
		#echo $title;
		if(strlen($title) > 0)
		{				
			echo "<h2><center>".$title."</center></h2>";
			echo "<td><img src=\"$toScan/$file\" alt=\"fRNAkenstein\" width=\"480\" > </td> <br> <br>";
			echo "<a href=\"$toScan/$file\" download=\"$title\" title=\"Download\"><center>Download this image</center></a><br><br><hr>";
			echo "the path is $toScan/$file";
		}	
	}
}

#http://localhost/diag_test_diagram
?>
 
