<?php
header("Access-Control-Allow-Origin:*");
header("Content-Type: text/plain");

 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );

 main( 42, -72 );

function main( $default_lat, $default_lon ) {
  // output lat/lon for a map center from all the sites

  $dbh = connect_for_read();
  if ( $dbh ) {
    try {

      $sql = "select avg(latitude) from sites where is_usable";
      $lat = read_one_value( $dbh, $sql );

      $sql = "select avg(longitude) from sites where is_usable";
      $lon = read_one_value( $dbh, $sql );

      if ( $lat && $lon ) {
        output( $lat, $lon );
      } else {
        output( $default_lat, $default_lon );
      }

    } catch (PDOException $e) {
      output( $default_lat, $default_lon );
      //echo $e;
    }

    $dbh = null;

  } else {
    output( $default_lat, $default_lon );
  }

}

function output( $lat, $lon ) {
  echo <<<EOF
{
  "latitude": $lat,
  "longitude": $lon
}
EOF;
}

?>
