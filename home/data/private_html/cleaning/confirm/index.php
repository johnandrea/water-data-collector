<?php
 include( "../../../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-parameters.inc" );
 include( "lib/html-head.html" );

 include( "../../../public_html/web-content/page-header.html" );
 echo( "<div id='content'>\n" );

 $params = array();
 $error = get_web_parameters( "<br>", $params );

 if ( $error == "" ) {

    $cleaning_params = array();
    $error = get_cleaning_params( "<br>", $cleaning_params );

    if ( $error == "" ) {

       main( $params, $cleaning_params );

    } else {
      show_error( $error );
    }

 } else {
   show_error( $error );
 }

 include( "index.js" );
 echo( "</div>\n" );
 include( "../lib/html-tail.html" );

// -----------------------------------------------------------
function main( $params, $cleaning_params ) {

  try {
     $dbh = connect_for_read();
     if ( $dbh ) {

       $sensors = array();
       $sites   = array();

       get_sensors( $dbh, $sensors );
       get_sites( $dbh, $sites );

       $error = verify_params( "\n", $sites, $sensors, $params );
       if ( $error == "" ) {

         $sites = explode( ",", $params['site_ids'] );
         if ( sizeof( $sites ) > 1 ) {
           show_error( "Only one site at a time can be cleaned" );
         } else {

           //print_r( $params );
           //print_r( $_REQUEST );
           //print_r( $cleaning_params );

           $name = $params['site_names'];;

           echo( "<h1>Data Cleaning for Site '$name'</h1>" );
           echo( "<h2>Confirmation step</h2>" );

           $comment = $cleaning_params['comment'];
           $site    = $params['site'];
           $action  = $cleaning_params['action'];
           $sensors = $params['sensor'];
           $times   = $cleaning_params['selection'];

           // don't pass n_select because the times are passed as a comma delim list
           //$n_select=$cleaning_params['n_select'];

           if ( $comment ) {
             echo "$comment<br><br>";
           }

           if ( $action == 'hide' ) {
             echo "Hiding data at times of:";
           } else {
             echo "Erasing values of ";
             echo $params['sensor'];
             echo " at times of:";
           }

           echo "<br><br>" . str_replace( ",", "<br>", $times );

           echo "<form method='post' action='submit/'>";
           echo "<input type='hidden' name='comment' value='$comment'>\n";
           echo "<input type='hidden' name='action' value='$action'>\n";
           echo "<input type='hidden' name='selection' value='$times'>\n";
           echo "<input type='hidden' name='sensor' value='$sensors'>\n";
           echo "<input type='hidden' name='site' value='$site'>\n";
           echo "<br><input type='submit' value='Submit'>\n";
           echo "</form>\n";

         }
       } else {
         show_error( $error );
       }

       $dbh = null;

     } else {
       show_error( "Can't connect to database" );
     }

  } catch (PDOException $e) {
    show_error( "Database error" );
    //echo( $e->getMessage() );
  }

}

?>
