<?php
 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-pages.inc" );
 include( "lib/html-head-main.html" );

 include( "../public_html/web-content/page-header.html" );
 echo "<div id='content'>\n";

 main( is_private_link() );

 echo "</div>\n";
 include( "lib/html-tail.html" );

// ----------------------------------------------------
function main( $is_private ) {
  $dbh = connect_for_read();
  if ( $dbh ) {
     try {

       $sites = array();
       get_sites( $dbh, $sites );

       $sensors = array();
       get_sensors( $dbh, $sensors );

       $texts = array();
       get_website_texts( $dbh, $texts );

       $new_date = get_newest_date( $dbh );

       $data_files = array();
       $data_files['CSV'] = 'data/csv';
       $data_files['JSON'] = 'data/json';
       $data_files['NetCDF'] = 'data/netcdf';

       main_webpage( $is_private, $data_files, $new_date, $texts, $sites, $sensors );

     } catch (PDOException $e) {
       show_error( "Database error" );
       //echo "<br>DEBUG:" . $e->getMessage();
     }

     $dbh = null;
  } else {
    show_error( "Database error. Unconnected." );
  }
}

?>
