<?php

$excl = array( 'TABLES', 'LISTS', 'IMAGES-CORE', 
'IMAGES-WMF', 'TABLES-ADVANCED-BORDERS', 'UNICODE-FONTS', 'HTMLHEADERS-FOOTERS', 'COLUMNS', 'TOC', 'INDEX', 'BOOKMARKS', 'BARCODES', 'FORMS', 'WATERMARK', 'RTL', 'INDIC', 'CJK', 'ANNOTATIONS', 'GRADIENTS', 'BACKGROUND-IMAGES', 'CSS-FLOAT', 'CSS-IMAGE-FLOAT', 'CSS-POSITION', 'CSS-PAGE', 'BORDER-RADIUS', 'HYPHENATION', 'ENCRYPTION', 'DIRECTW', 'PROGRESS-BAR');


	// *DIRECTW* = Write, WriteText, WriteCell, Text, Shaded_box, AutosizeText
	// IMAGES-CORE = [PNG, GIF, and JPG] NB background-images and watermark images


// Text is marked in mpdf_source.php with e.g. :
/*-- TABLES-ADVANCED-BORDERS --*/
/*-- END TABLES-ADVANCED-BORDERS --*/
	// *TABLES-ADVANCED-BORDERS*


if (!isset($_POST['generate']) || $_POST['generate']!='generate') {


if (!file_exists('mpdf_source.php')) {
	die("ERROR - Could not find mpdf_source.php file in current directory. Please rename mpdf.php as mpdf_source.php"); 
}




echo '<html>
<head>
<script language=javascript>
checked=false;
function checkedAll (frm1) {
	var aa= document.getElementById("frm1");
	 if (checked == false)
          {
           checked = true
          }
        else
          {
          checked = false
          }
	for (var i =0; i < aa.elements.length; i++) 
	{
	 aa.elements[i].checked = checked;
	}
      }
</script>
</head>
<body>
<p><span style="color:red; font-weight: bold;">WARNING</span>: This utility will OVERWRITE mpdf.php file in the current directory.</p>
<p>Select the functions you wish to INCLUDE in your mpdf.php program. When you click generate, a new mpdf.php file will be written to the current directory.</p>
<div><b>Notes</b>
<ul>
<li>For WMF Images, you must include both IMAGES-CORE and IMAGES-WMF</li>
<li>JPG, PNG and JPG images are supported with IMAGES-CORE</li>
<li>IMAGES-CORE are required for BACKGROUND-IMAGES or WATERMARKS to work</li>
<li>DIRECTW includes the functions to Write directly to the PDF file e.g. Write, WriteText, WriteCell, Text, Shaded_box, AutosizeText</li>
</ul>
</div>
<input type="checkbox" name="checkall" onclick="checkedAll(frm1);"> <i>Select/Unselect All</i><br /><br />

<form id="frm1" action="compress.php" method="POST">
';
foreach($excl AS $k=>$ex) {
	echo '<input type="checkbox" value="1" name="inc['.$ex.']"';
	if ($k < 3) {
		echo ' checked="checked"';
	}
	echo ' /> '.$ex.'<br />';
}

echo '<br />
<input type="submit" name="generate" value="generate" />
</form>
</body>
</html>';
exit;
}

$inc = $_POST['inc'];
if (is_array($inc) && count($inc)>0 ) { 
	foreach($inc AS $i=>$v) {
		$key = array_search($i, $excl);
		unset($excl[$key]);
	}
}

set_magic_quotes_runtime(0);

$l = file('mpdf_source.php');
if (!count($l)) { die("ERROR - Could not find mpdf_source.php file in current directory"); }
$exclflags = array();
$x = '';
foreach($l AS $k=>$ln) {
	$exclude = false;
	// *XXXXX*
	preg_match_all("/\/\/ \*([A-Za-z\-]+)\*/", $ln, $m);
	foreach($m[1] AS $mm) {
		if (in_array($mm, $excl)) {
			$exclude = true;
		}
	}
	/*-- XXXXX --*/
	preg_match_all("/\/\*-- ([A-Za-z\-]+) --\*\//", $ln, $m);
	foreach($m[1] AS $mm) {
		if (in_array($mm, $excl)) {
			$exclflags[$mm] = true;
		}
		$exclude = true;
	}
	$exclflags = array_unique($exclflags);
	/*-- END XXXX --*/
	preg_match_all("/\/\*-- END ([A-Za-z\-]+) --\*\//", $ln, $m);
	foreach($m[1] AS $mm) {
		if (in_array($mm, $excl)) {
			unset($exclflags[$mm]);
		}
		$exclude = true;
	}
	if (count($exclflags)==0 && !$exclude) { 
		$x .= $ln; 
	}
}

$check = file_put_contents('mpdf.php', $x);
if (!$check) { die("ERROR - Could not write to mpdf.php file. Are permissions correctly set?"); }
echo '<p><b>mPDF file generated successfully!</b></p>';
echo '<div>mPDF file size '.number_format((strlen($x)/1024)).' kB</div>';

unset($l);
unset($x);

include('mpdf.php');
$mpdf = new mPDF();

echo '<div>Memory usage on loading mPDF class '.number_format((memory_get_usage(true)/(1024*1024)),2).' MB</div>';

exit;

?>