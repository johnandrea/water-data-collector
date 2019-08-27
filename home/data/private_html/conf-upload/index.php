<?php
 include( "lib/common.inc" );
 include( "lib/web-pages.inc" );
 include( "lib/html-head-main.html" );

 include( "../../public_html/web-content/page-header.html" );
 echo "<div id='content'>\n";

 main();

 echo "</div>\n";
 include( "lib/html-tail.html" );

// ----------------------------------------------------
function main() {
  echo <<<EOF
<h1>Upload an instrument configuration file in JSON format</h1>
<br><br>
<form enctype="multipart/form-data" action="upload.php" method="POST">
File: <input name="userfile" type="file">
<br><br>
<input type="submit" value="Submit">
</form>
EOF;
}

?>
