<?php
 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-parameters.inc" );
 include( "lib/html-head.html" );

 include( "../public_html/web-content/page-header.html" );
 echo( "<div id='content'>\n" );

 main();

 echo( "</div>\n" );
 include( "../lib/html-tail.html" );

// -----------------------------------------------------------
function main() {

  echo <<<EOF
Data file Configurations:
<ul>
<li><a target='_blank' href='conf-upload/'>Upload frame configuration for an instrument</a>
<li><a target='_blank' href='conf-manage/'>Manage the frame configurations</a>
<li><a target='_blank' href='conf-test/'>Test configuration before upload</a>
</ul>

Server setup:
<ul>
<li><a target='_blank' href='stats/'>stats</a>
<li><a target='_blank' href='setup/'>settings</a>
</ul>

RAW files
<ul>
<li><a target='_blank' href='raw-upload/'>Upload raw files for re/processing</a>
</ul>
EOF;
}

?>
