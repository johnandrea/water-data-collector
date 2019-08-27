<?php
header("Access-Control-Allow-Origin:*");
header("Content-Type: text/plain");

 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );

 main( is_private_link() );

function main( $is_private ) {
  // output geoJson data to draw the items on the map

  $dbh = connect_for_read();
  if ( $dbh ) {
    try {

      $sites = array();
      get_sites( $dbh, $sites );

      begin_output();

      // assuming public because no parameters defined to make the test

      $comma = "\n";
      foreach ( $sites as $id => $detail ) {
         if ( $detail['for_summary'] && item_is_showable( $is_private, $detail ) ) {
           echo $comma;
           output_site( $detail );
           $comma = ",";
         }
      }

      end_output();

    } catch (PDOException $e) {
      echo "";
    }

    $dbh = null;

  } else {
    echo "";
  }

}

function begin_output() {
  echo <<<EOF
{ "type": "FeatureCollection",
  "features": [

EOF;
}

function end_output() {
   echo <<<EOF

  ]
}
EOF;
}

function output_site( $data ) {

   $name = $data['name'];
   $key  = $data['web_key'];
   $lat  = $data['latitude'];
   $lon  = $data['longitude'];

   // note the lon before lat in this format

   echo <<<EOF
  { "type": "Feature",
    "properties": {
       "Name": "$name",
       "siteType": "Data",
       "siteKey": "$key",
       "Description": "$name"
    },
    "geometry": {
       "type": "Point",
       "coordinates": [$lon, $lat]
    }
  }
EOF;
}

?>
