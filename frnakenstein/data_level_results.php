<head>
<title>
fRNAkenstein Access Results
</title>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script>
$(function() {
var availableTags = <?php echo json_encode(scandir("/var/www/subdirectories_for_interface/diffexpress_output")) ?>;
 $( "#tags" ).autocomplete({
source: availableTags, minLength:2, delay:0
});
});

$(function(){
	var analyses = <?php echo json_encode(scandir("/var/www/subdirectories_for_interface/diffexpress_output")) ?>;
	//alert(analyses);
	$("#adder").click(function(){ 
		var inp = $("#tags");
		var name = inp.val();
				
		if ( $('#tags').is(":empty") ) {
		alert ("analysis is empty");
		
		
		}
		
		if(inp.val().length > 0)
		{
			if(jQuery.inArray(analyses, name) == -1)
			{

				alert("that is not a proper analysis!!");

			}
		
		}
	});
});
</script>

<?php
//if(empty($_POST['analysis'])){
//echo '<form method="POST" action="interpret_results.php">';
//echo '<h4>Analysis Name:</h4>';
//echo '<span class="ui-widget">';
//echo'<input id="tags" name="analysis">';
//echo'</span><button id="adder" type="submit">Select Analysis</button>';
//echo '</form>';
//echo '<br>';
//exit('');
//}
//else{
//$analysis = $_POST['analysis'];
//}


if(empty($_POST['analysis'])){
	echo '<form method="POST" action="data_level_results.php"';	
	echo '<h4>Analysis Name:</h4>';	
	echo '<span class="ui-widget">';
	echo'<input id="tags" name="analysis">';
	echo'</span><button id="adder" type="submit">Select Analysis</button></form>';	
	exit('');
}
$analysis = htmlspecialchars($_POST['analysis']);

?>
<form class="go-bottom" id='submitform' onsubmit="return valthisform(this);" action='data_level_results_response.php' method='post' target='formresponse'>

<!--
Send the analysis path also!
-->

<input type="hidden" value="<?php echo $analysispath; ?>" name="analysispath" />
<input type="hidden" value="<?php echo $analysis; ?>" name="analysis" />

<center>

<!--
################################
# Beginning of alignment table #
################################
-->
<table>
<tr style="padding:0px; margin:0px;">
<td valign="top" style="padding-top:12px;padding-left:8px;width:300px">

<!--
################################################
# Create checkboxes for fastq files (lib nums) #
################################################
-->
<div class='container'>
<br><br><br>
<?php
#$analysis = $_POST['analysis']
#$files = scandir("$subdirectories/$analysis");
echo '<form method="post" action="data_level_results_response.php">';
$pathToScan = "/var/www/subdirectories_for_interface/diffexpress_output/$analysis";
echo "$analysis";
echo "is analysis !!";
echo $pathToScan;
echo "is the pathToScan!!";
$files = scandir("$pathToScan");
#match all of the text files, which are going to be the output from
#the combining algorithm

foreach ($files as $file)
{
	
	if($file != "." AND $file != "..")
	{	
		$pattern = "/(.*(\d.txt))/";
      		#$pattern = "/(.*(.txt))/";
		preg_match($pattern, $file, $matches);
      		$pngName = $matches[1];
		$length=strlen($pngName);
		#echo "$length";
		if($length > 0)
		{
			echo strlen($fileName);
			#echo "<input type=\"checkbox\" name=\"filename[]\" value=\"$file\">$pngName<br>";  
			echo "<a href=\"$pathToScan/$file\" download=\"$file\" title=\"Download\"><center>Download $pngName</center></a><br><br><hr>";	
		}	

		#echo strlen($fileName);
		#echo "<input type=\"checkbox\" name=\"filename[]\" value=\"$file\">$pngName<br>";  
		#echo "<a href=\"$pathToScanOnVM/$file\" download=\"$pngName\" title=\"Download\"><center>Download $pngName</center></a><br><br><hr>";	
	}
}
?>
<br><br><br><br><br>

<!--
###########################
# Submit and Menu Buttons #
###########################
-->
<div class='container'>
<center>
<form action = 'data_level_results_response.php' target = 'formresponse'>
<button id='crunch' class='crunch' type="submit">"Show data level images"</button>
</form>
<br> <br> <br>

<form action="menu.php">
    <input align="bottom" type="submit" value="Return to Menu">
</form>


<!--
###################
# Response iFrame #
###################
-->
<td valign="top" style="padding-left:0px;align:left">
<br>
<iframe name='formresponse' src='placeholder_response.html' style="border: outset; background-color:#d0eace" width='500px' height='<?php echo count($fqfiles)*16+500;?>' frameborder='0'>
</iframe>
</td>
</tr>
</table>





