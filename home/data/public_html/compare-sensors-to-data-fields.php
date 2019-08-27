<?php
 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 //include( "lib/web-pages.inc" );
 include( "lib/html-head-main.html" );

 $dbh = connect_for_read();
 if ( $dbh ) {
    try {

      $sites = array();
      get_sites( $dbh, $sites );

      //print_r( $sites );

      $sensors = array();
      get_sensors( $dbh, $sensors );
      //print_r( $sensors );

      $data_columns = array();
      get_data_table_columns( $dbh, $data_columns );
      //print_r( $data_columns );

      // compare data colums with sensor names
      $all_cols = array();
      foreach ( $data_columns as $name ) {
         if ( $name == "site_id" ) { continue; }
         if ( $name == "data_file" ) { continue; }
         if ( $name == "data_time" ) { continue; }
         if ( $name == "is_usable" ) { continue; }
         $all_cols[$name]['data'] = true;
      }
      foreach ( $sensors as $name => $info ) {
         if ( $name == "is_usable" ) { continue; }
         $all_cols[$name]['sensors'] = true;
      }
      echo( "<table>" );
      echo( "<tr><th>name</th><th>data table</th><th>sensor name</th></tr>\n" );
      ksort( $all_cols );
      foreach ( $all_cols as $name => $info ) {
         echo( "<tr>" );
         echo( "<td>$name</td>" );
         $result = "&nbsp;";
         if ( $info['data'] ) { $result = "y"; }
         echo( "<td>$result</td>" );
         $result = "&nbsp;";
         if ( $info['sensors'] ) { $result = "y"; }
         echo( "<td>$result</td>" );
         echo( "</tr>\n" );
      }
      echo( "</table>\n" );

      //$texts = array();
      //get_website_texts( $dbh, $texts );

      //$new_date = get_newest_date( $dbh );

      //$data_files = array();
      //$data_files['CSV'] = 'data/csv';
      //$data_files['JSON'] = 'data/json';
      //$data_files['NetCDF'] = 'data/netcdf';

      //main_webpage( is_private_link(), $data_files, $new_date, $texts, $sites, $sensors );

    } catch (PDOException $e) {
      echo "Database error";
    }

    $dbh = null;
 } else {
   echo "Database error";
 }

 include( "lib/html-tail.html" );

?>
