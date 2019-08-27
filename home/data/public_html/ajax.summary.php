<?php
header("Access-Control-Allow-Origin:*");
header("Content-Type: text/plain");

 include( "../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );

 main( is_private_link() );

function main( $is_private ) {

  $dbh = connect_for_read();
  if ( $dbh ) {
    try {

      $sites = array();
      get_sites( $dbh, $sites );

      if ( isset( $_REQUEST['site'] ) ) {
         $site_id = site_exists( $_REQUEST['site'], $sites );
         if ( $site_id ) {
            // don't bother to check for the public/private visibility of
            // this site because the sensors will have the check

            $sensors = array();
            get_sensors( $dbh, $sensors );

            $wanted = array();
            foreach ( $sensors as $sensor => $detail ) {
               if ( $detail['for_summary'] && item_is_showable( $is_private, $detail ) ) {
                 array_push( $wanted, $sensor );
               }
            }
            sort( $wanted );

            echo $sites[$site_id]['name'] . "<br>\n";

            get_data( $dbh, $site_id, $wanted, $sensors );

         } else {
           echo "Unknown site";
         }
      } else {
        echo "Missing site";
      }

    } catch (PDOException $e) {
      echo "N/A";
    }

    $dbh = null;

  } else {
    echo "N/A";
  }

}

function get_data( $dbh, $site, $selected, $sensors ) {
   $sql = "select max(data_time) from data where site_id=? and is_usable";
   $max_time = read_one_value_with_params( $dbh, $sql, array($site) );

   list( $max_day, $max_hms ) = explode( " ", $max_time );

   echo "<table class='map-summary' border='0' cellspacing='3' cellpadding='0'>";

   // show the marker for the newest date
   // plus, mouse hover over the date will show the full offset

   echo "<tr>";
   echo "<td>" . offset_marker( $max_time ) . "</td>";
   echo "<td colspan='3'><span title='"
        . time_offset_from_today_utc( $max_time )
        . "'>$max_time UTC</span></td>";
   echo "</tr>\n";

   foreach ( $selected as $sensor ) {
      $sql = "select data_time, $sensor from data"
           . " where site_id=? and is_usable"
           . " order by data_time desc limit 1";

      list( $time, $value ) = read_two_values_with_params( $dbh, $sql, array($site) );

      $label = make_sensor_label( $sensor );
      $units = $sensors[$sensor]['units'];
      if ( isset( $units ) and ($units != '' ) ) {
         $units = "(" . $units . ")";
      }

      if ( isset( $value ) ) {
        $value = sprintf( "%.1f", $value );
      } else {
        $value = "N/A";
        $units = "&nbsp;";
      }

      $timer = offset_marker( $time );
      echo "<tr><td>$timer</td><td>$label</td><td>$value</td><td>$units</td></tr>\n";
   }
   echo "</table>";
}

function offset_marker( $data_time ) {
   // show a circle to show the age of the item
   // open = within 2 hours
   // half = within 24 hours
   // closed = older

   $hours = hours_offset_from_today_utc( $data_time );

   $timer = "&nbsp;";

   if ( $hours <= 2 ) {
     $timer = "<img class='mark-now' src='/images/icons8-black-open-circle.png'>"; //all white

   } elseif ( $hours <= 24 ) {
     $timer = "<img class='mark-today' src='/images/icons8-black-half-circle.png'>"; // half

   } else {
     $timer = "<img class='mark-old' src='/images/icons8-black-full-circle.png'>"; //all black
   }

   return $timer;
}

?>
