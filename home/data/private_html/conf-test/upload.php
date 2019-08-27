<?php
 include( "../../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-pages.inc" );
 include( "lib/html-head-main.html" );

 include( "../../public_html/web-content/page-header.html" );
 echo "<div id='content'>\n";

 // some sites do not need the check against database sensor names
 // in which case this can be set to false

 $check_sensors = true;

 main( $check_sensors );

 echo "</div>\n";
 include( "lib/html-tail.html" );

// ----------------------------------------------------------
function main( $check_sensors ) {
  $error = null;

  echo "<h1>Configuration Testing</h1>";

  // these strings are the words which represent 'true' for field reportability
  $true_words = array( 'true', 't', '1', 'ok', 'on', 'yes', 'y' );

  if ( $_FILES['userfile']['error'] === UPLOAD_ERR_OK
       && is_uploaded_file($_FILES['userfile']['tmp_name']) ) {

    $data = file_get_contents($_FILES['userfile']['tmp_name']);

    if ( is_json( $data ) ) {

       $sensor_names = array();
       if ( $check_sensors ) {
          $error = get_sensor_names( $sensor_names );
       }

       if ( ! $error ) {
          $error = check_contents( $true_words, $data,
                                   $check_sensors, $sensor_names );
       }

    } else {
      $error = 'File is not in JSON format. Or has an error';
    }

    if ( $error ) {
      show_error( $error );
    } else {
      include( 'more-tests.html' );
    }

  } else {
    show_error( 'Upload failed' );
  }
}

// ------------------------------------------------------------------
function get_sensor_names( &$list ) {
  $error = null;

  $dbh = connect_for_read();
  if ( $dbh ) {
     try {

       $sensors = array();
       get_sensors( $dbh, $sensors );

       $list = array_keys( $sensors );

     } catch (PDOException $e) {
       $error = "Database error";
     }

     $dbh = null;

  } else {
    $error = "Database connect error";
  }
  return $error;
}

// ----------------------------------------------------------
function check_contents( $true_words, $file_contents, $check_sensors, $sensor_names ) {
  $error = null;

  $data = json_decode( $file_contents, true );

  if ( isset($data['instrument']) && isset($data['serial']) ) {

    // check that something is reportable
    // and that the reported names exist as sensors

    if ( isset( $data['fields'] ) ) {
      $error .= check_fields( $true_words, $data['fields'], $check_sensors, $sensor_names );
    } else {
      $error .= ' Missing fields section';
    }

  } else {
    $error .= ' Missing instrument name and/or serial number';
  }

  return $error;
}

// ----------------------------------------------------------
function check_fields( $true_words, $data, $check_sensors, $sensor_names ) {
  $error = null;

  $break = " ";

  $n_reported = 0;
  $n_no_sensor = 0;

  foreach( $data as $i => $items ) {
    $name   = null;
    $report = null;
    // run over all the items and convert key to lowercase
    foreach( $items as $key => $value ) {
      $key = strtolower( $key );
      if ( $key == 'name'   ) { $name   = strtolower( $value ); }
      if ( $key == 'report' ) { $report = strtolower( $value ); }
    }

    $report = in_array( $report, $true_words );

    if ( isset( $name ) ) {
       if ( $report ) {
          $n_reported++;

          if ( $check_sensors ) {
             if ( ! in_array( $name, $sensor_names ) ) {
                $n_no_sensor++;
                $error .= $break . "Field $i '$name' is not a sensor in the database.";
                $break = "<br>";
             }
          }
       }
    } //if name
  } //for

  return $error;
}

?>
