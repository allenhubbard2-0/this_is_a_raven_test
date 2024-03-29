<head>
<title>
fRNAkenstein - Visualization
</title>
</head>
<link rel="STYLESHEET" type="text/css" href="css_dir/style.css">
<link rel="STYLESHEET" type="text/css" href="css_dir/buttonStyle.css">
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<link rel="stylesheet" href="/resources/demos/style.css">

<?php
$subdirectories = "/var/www/subdirectories_for_interface";
$analyses = "$subdirectories/mirFiles";
?>

<script>
$(function() {
var availableTags = <?php echo json_encode(scandir("/var/www")) ?>;
 $( "#tags" ).autocomplete({
source: availableTags, minLength:2, delay:0
});
});

$(function(){
	var analyses = <?php echo json_encode(scandir("/var/www")) ?>;
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



$(document).ready(function(){
	if ( $('#tags').is(":empty") ) 
	{
		alert ("analysis is empty");
		$("#pathway").attr("disabled", true);
	}		 
	
});


</script>




<?php
if(empty($_POST['analysis'])){
	#echo $subdirectories;	
	echo '<div class="container">';
	echo '<table>';
	echo'<th colspan="3" >';
	echo '<img src="images/frnak_banner.png" alt="fRNAkenstein" width="600" > </td> <br> <br>';
	echo '<tr>';
	echo '<td class="menu_button" name = "cummeRbund">';
	echo '<a href="frnakenstein/visualize.php" class="fRNAkbutton">CummeRbund </a>';
	echo '<td class="menu_description">';
	echo '<b> Option1:</b> Visualize Expression Data at the Gene and Isoform Level Using cummeRbund';
	echo '</td>';
	echo '<tr>';
	echo '<td class="menu_button" name = "pathway" id = "pathway">';
	echo '<a href="http://bigbird.anr.udel.edu/~sunliang/pathway/cyto.php" class="fRNAkbutton">Pathway Level</a>';
	echo '<td class="menu_description">';
	echo '<b>Option2:</b> Visualize differential expression data at the pathway level'; 
	echo '</td>';	
	echo '<tr>';
	echo '<td class="menu_button" name = "pathway" id = "pathway">';
	echo '<a href="interpret_results.php" class="fRNAkbutton">Data level</a>';
	echo '<td class="menu_description">';
	echo '<b>Option3:</b> View graphical output of data level analyses such as PCA'; 
	echo '</td>';		
	exit('');
}
?>
<!--
<div class="container">
<table>
<th colspan="3" >';
<img src="images/frnak_banner.png" alt="fRNAkenstein" width="600" > </td> <br> <br>
<tr>
<td class="menu_button">
<a href="visualize.php" class="fRNAkbutton">CummeRbund </a>
<td class="menu_description">
<b> Option1:</b> Visualize Expression Data at the Gene and Isoform Level Using cummeRbund
</td>
<tr>
<td class="menu_button">
<a href="http://bigbird.anr.udel.edu/~sunliang/pathway/cyto.php" class="fRNAkbutton">Pathway Level</a>
<td class="menu_description">
<b>Option2:</b> Visualize differential expression data at the pathway level
</td>
-->
