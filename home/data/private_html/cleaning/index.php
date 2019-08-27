<?php
 include( "../../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-parameters.inc" );
 include( "lib/html-head.html" );

 include( "../../public_html/web-content/page-header.html" );
 echo( "<div id='content'>\n" );

 $params = array();
 $error = get_web_parameters( "\n", $params );

 if ( $error == "" ) {

   // this is the maximum number of rows which will be shown in the table
   $max_items = 120;

   main( $params, $max_items );

 } else {
   show_error( "$error" );
 }

 include( "index.js" );
 echo( "</div>\n" );
 include( "../lib/html-tail.html" );

// -----------------------------------------------------------
function main( $params, $max_items ) {

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

           $start = $params['exact_start'];
           $end   = $params['exact_end'];

           $name = $params['site_names'];;

           echo( "<h1>Data Cleaning for Site '$name'</h1>" );

           include( "page-top.html" );

           // put the sensors in sorted order
           $sensor_names = explode( ",", $params['sensor'] );
           sort( $sensor_names );

           show_data( $dbh, $max_items, $start, $end,
                      $params['comment'],
                      $params['site_ids'], $params['site'], $sensor_names );

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

// -----------------------------------------------------------
function show_data( $dbh, $max_items, $start, $end, $comment,
                    $site_id, $site_key, $sensor_names ) {
   $data = array();
   $n = gather_data( $dbh, $max_items+1, $start, $end, $site_id, $sensor_names, $data );

   if ( $n < 1 ) {
      echo "WARNING: No data";
   } else if ( $n > $max_items ) {
      echo "WARNING: This form is limited to $max_items records. Go back and select a shorter time frame.";
   } else {

   $sensors = join( ",", $sensor_names );

   echo <<<EOF
<input type="hidden" name="sensor" value="$sensors">
<input type="hidden" name="site" value="$site_key">
<input type="hidden" name="n_select" value="$n">
<table id="cleaning-table" cellpadding="6" cellspacing="0">
<thead><tr>
  <th>select
      <br><button type="button" onclick="setAll(true)">all</button>
      <br><button type="button" onclick="invertAll()">invert</button>
      <br><button type="button" onclick="setAll(false)">none</button>
  </th>
  <th>date</th>
EOF;

   foreach( $sensor_names as $sensor ) {
     echo "<th>$sensor</th>";
   }

   echo "</tr></thead><tbody>";

   $i = 0;
   foreach( $data as $time => $values ) {
     $i++;
     echo <<<EOF
<tr>
<td><input id="id$i" type="checkbox" name="t$i" value="$time"></td>
<td><label for="id$i">$time</label></td>
EOF;

     foreach( $values as $sensor => $value ) {
       echo "<td>$value</td>";
     }

     echo "</tr>\n";
   }

   echo <<<EOF
</tbody>
</table>
<br><input type="submit" value="Confirm">
EOF;
    }
}

// -----------------------------------------------------------
function gather_data( $dbh, $n_max, $start, $end, $site_id, $sensor_names, &$data ) {
   $n = 0;

   $no_nulls = "";
   foreach( $sensor_names as $sensor ) {
     $no_nulls .= " and $sensor is not null";
   }

   $sql = "select data_time," . join( ",", $sensor_names ) . " from data"
        . " where site_id=?"
        . " and data_time >= ?"
        . " and data_time <= ?"
        . " and is_usable"
        . $no_nulls
        . " order by data_time"
        . " limit $n_max"
        ;

   $stmt = $dbh->prepare( $sql );
   $stmt->bindParam( 1, $site_id );
   $stmt->bindParam( 2, $start );
   $stmt->bindParam( 3, $end );
   $stmt->execute();

   $n_sensors = sizeof( $sensor_names );

   while( $row = $stmt->fetch() ) {
     $n++;
     $time = $row[0];
     for( $i = 1; $i <= $n_sensors; $i++ ) {
        $data[$time][$i] = $row[$i];
     }
   }
   $stmt = null;

   return $n;
}

?>
