<?php
 include( "../../../../not-served/db-config.inc" );
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
     $dbh = connect_for_write();
     if ( $dbh ) {
       $dbh->beginTransaction();

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

           $name   = $params['site_names'];;
           $site   = $params['site_ids'];
           $action = $cleaning_params['action'];
           $comment= $cleaning_params['comment'];
           $times  = explode( ",",  $cleaning_params['selection'] );

           echo( "<h1>Data Cleaning for Site '$name'</h1>" );

           if ( $action == 'hide' ) {
             hide_data( $dbh, $comment, $site, $times );
           } else {
             $sensors = explode( ",", $params['sensor'] );
             erase_data( $dbh, $comment, $site, $times, $sensors );
           }

           echo "Done";

         }
       } else {
         show_error( $error );
       }

       $dbh->commit();

       $dbh = null;

     } else {
       show_error( "Can't connect to database" );
     }

  } catch (PDOException $e) {
    $db->rollBack();
    show_error( "Database error" );
    //echo( $e->getMessage() );
  }

}

// -----------------------------------------------------------
function hide_data( $dbh, $comment, $site, $times ) {
  $sql = "update data set is_usable=false"
       . " where site_id=?"
       . " and data_time=?"
       ;

  $stmt = $dbh->prepare( $sql );
  $stmt->bindParam( 1, $site );

  foreach( $times as $time ) {
    $stmt->bindParam( 2, $time );
    $stmt->execute();
  }

  $stmt = null;

  // now track those changes

  $sql = "insert into cleaned_data"
       . " (operation,name,site_id,comment,data_time)"
       . " values ('Hiding row','-whole-row-',?,?,?)"
       ;

  $stmt = $dbh->prepare( $sql );
  $stmt->bindParam( 1, $site );
  $stmt->bindParam( 2, $comment );

  foreach( $times as $time ) {
    $stmt->bindParam( 3, $time );
    $stmt->execute();
  }

  $stmt = null;
}

// -----------------------------------------------------------
function erase_data( $dbh, $comment, $site, $times, $sensors ) {
  // save the data before erasing

  foreach( $sensors as $sensor ) {
     $sub_value = "(select $sensor from data where site_id=? and data_time=?)";

     $sql = "insert into cleaned_data"
          //   1       2         3       4    5         (6site) 7time)
          . " (operation,site_id,comment,name,data_time,value)"
          . " values ('Erase',?,?,?,?,$sub_value)"
          ;
     $stmt = $dbh->prepare( $sql );
     $stmt->bindParam( 1, $site );
     $stmt->bindParam( 2, $comment );
     $stmt->bindParam( 3, $sensor );
     foreach( $times as $time ) {
       $stmt->bindParam( 4, $time );
       $stmt->bindParam( 5, $site );
       $stmt->bindParam( 6, $time );
       $stmt->execute();
     }
     $stmt = null;
  }

  // now the erasing

  foreach( $sensors as $sensor ) {
     $sql = "update data set $sensor=null"
          . " where site_id=?"
          . " and data_time=?"
          ;
     $stmt = $dbh->prepare( $sql );
     $stmt->bindParam( 1, $site );
     foreach( $times as $time ) {
       $stmt->bindParam( 2, $time );
       $stmt->execute();
     }
     $stmt = null;
  }
}

?>
