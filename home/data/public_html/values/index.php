<?php
 include( "../../not-served/db-config.inc" );
 include( "../lib/db.inc" );
 include( "../lib/common.inc" );
 include( "../lib/web-parameters.inc" );

 $params = array();
 $error = get_web_parameters( "\n", $params );

 if ( $error == "" ) {

    $format = $params['format'];
    $start  = $params['start'];
    $end    = $params['end'];

    if ( $format == "text" ) {
      // in the browser
      header("Content-Type: text/plain");
      main( "", $format, $params );

    } elseif ( $format == "tsv" ) {
      header("Content-Type: text/tab-separated-values");
      header( make_filename_header( make_filename( $start, $end, $format ) ) );
      main( "", $format, $params );

    } elseif( $format == "csv" ) {
      header("Content-Type: text/csv");
      header( make_filename_header( make_filename( $start, $end, $format ) ) );
      main( "", $format, $params );

    } elseif( $format == "json" ) {
      header("Content-Type: application/json");
      header( make_filename_header( make_filename( $start, $end, $format) ) );
      main( "", $format, $params );

    } elseif( $format == "cdl" ) {
      header("Content-Type: text/plain");
      $outfile = make_filename( $start, $end, $format );
      header( make_filename_header( $outfile ) );
      main( $outfile, $format, $params );

    } else {
      header("Content-Type: text/plain");
      echo( "ERROR: unimplemented format: $format" );
    }

 } else {
   header("Content-Type: text/plain");
   echo( "ERROR: $error" );
 }

function main( $outfile, $format, $params ) {

  try {
     $dbh = connect_for_read();
     if ( $dbh ) {

       $sensors = array();
       $sites   = array();

       get_sensors( $dbh, $sensors );
       get_sites( $dbh, $sites );

       $error = verify_params( "\n", $sites, $sensors, $params );
       if ( $error == "" ) {

         $list_of_sites   = explode( ",", $params['site_ids'] );
         $list_of_sensors = explode( ",", $params['sensor'] );

         sort( $list_of_sensors );

         $start = $params['exact_start'];
         $end   = $params['exact_end'];

         if ( $format == "text" ) {
            text_output( $dbh, $sites, $sensors,
                         $list_of_sites, $list_of_sensors, $start, $end );

         } elseif ( $format == "tsv" ) {
            sv_output( $dbh, $sites, $sensors, "\t", "",
                       $list_of_sites, $list_of_sensors, $start, $end );

         } elseif ( $format == "csv" ) {
            sv_output( $dbh, $sites, $sensors, ",", '"',
                       $list_of_sites, $list_of_sensors, $start, $end );

         } elseif ( $format == "json" ) {
            json_output( $dbh, $sites, $sensors,
                         $list_of_sites, $list_of_sensors, $start, $end );

         } elseif ( $format == "cdl" ) {
            cdl_output( $dbh, $outfile, $sites, $sensors, $outfile,
                        $list_of_sites, $list_of_sensors, $start, $end );


         } else {
           echo( "ERROR: unimplmented format: $format" );
         }

       } else {
         echo( "ERROR: $error" );
       }

       $dbh = null;

     } else {
       echo( "ERROR: Can't connect to database" );
     }

  } catch (PDOException $e) {
    echo( "ERROR: Database error" );
    //echo( $e );
  }

}

function cdl_output( $dbh, $outfile,
                     $sites, $sensors, $outfile,
                     $selected_sites, $selected_sensors, $start, $end ) {

   // CDL/netCDF can't handle multiple locations

   $site_id = null;

   $n = 0;
   foreach ( $selected_sites as $site ) {
       $n++;
       $site_id = $site;
   }

   if ( $n > 1 ) {
      echo "Error: CDL can only be used for a single location.\n";
   }

   // location is important as an identifier, make lat/log required sensors

   $found_lat = false;
   $found_lon = false;
   foreach( $selected_sensors as $sensor ) {
     if ( $sensor == "latitude" )  { $found_lat = true; }
     if ( $sensor == "longitude" ) { $found_lon = true; }
   }
   if ( ! $found_lat ) { array_push( $selected_sensors, "latitude" );  }
   if ( ! $found_lon ) { array_push( $selected_sensors, "longitude" ); }

   // represent data time as minutes since this date
   $since = '2019-01-01 00:00:00';

   echo "netcdf $outfile {\n";

   echo "\n";
   echo "dimensions:\n";
   echo "   time = unlimited ;\n";

   // all data is numeric

   echo "\n";
   echo "variables:\n";
   echo "   :title = \"Site: " . $sites[$site_id]['name']
        . " " . $start . " to " . $end
        . "\" ;\n";

   echo "   int64 time(time);\n";
   echo "     time:units = \"minutes since $since\" ;\n";
   echo "     time:calendar = \"proleptic_gregorian\" ;\n";
   foreach ( $selected_sensors as $sensor ) {
       echo "   double $sensor(time) ;\n";
       echo "     $sensor:long_name = \"" . $sensors[$sensor]['desc'] . "\" ;\n";
       echo "     $sensor:units = \"" . make_sensor_units_for_cdf( $sensor, $sensors ) . "\" ;\n";
       echo "     $sensor:_FillValue = NaN ;\n";
   }
   echo "\n";

   $n_sensor = 0;
   $sensor_names = array();

   $sql = "select round(extract( epoch from (data_time - timestamp'$since')::interval)/60)";
   $sensor_names[$n_sensor] = 'time';

   foreach ( $selected_sensors as $sensor ) {
     $n_sensor++;
     $sensor_names[$n_sensor] = $sensor;
     $sql .= "," . $sensor;
   }
   $sql .= " from data where is_usable and site_id=?"
        . " and data_time >=? and data_time <=?"
        . " and latitude is not null and longitude is not null"
        . " order by data_time";

   $stmt = $dbh->prepare( $sql );

   $stmt->bindParam( 1, $site_id );
   $stmt->bindParam( 2, $start );
   $stmt->bindParam( 3, $end );

   $stmt->execute();

   $data      = array();
   $have_data = array();

   $n = 0;
   $comma = "";
   while( $row = $stmt->fetch() ) {
      $n++;
      for ( $i = 0; $i <= $n_sensor; $i++ ) {
         $name = $sensor_names[$i];
         if ( isset( $row[$i] ) ) {
            $value = $row[$i];
            $have_data[$name] = true;
         } else {
            $value = "NaN";
         }
         $data[$name] .= $comma . $value;
      }
      $comma = ",";
   }

   $stmt = null;

   if ( $n > 0 ) {
      echo "data:\n";
      echo "   time = " . $data['time'] . " ;\n";
      foreach( $selected_sensors as $sensor ) {
         if ( $have_data[$sensor] ) {
            echo "   $sensor = " . $data[$sensor] . " ;\n";
         }
      }
   }

   echo "}\n";
}

function json_output( $dbh, $sites, $sensors,
                      $selected_sites, $selected_sensors, $start, $end ) {

   $data = array();

   $tag = "units";
   $data[$tag] = array();

   $data[$tag]['date'] = 'UTC';

   $n_sensor = 0;
   $sensor_names = array();
   $sql = "select data_time";
   foreach ( $selected_sensors as $sensor ) {
     $n_sensor++;
     $label = make_sensor_label( $sensor );
     $sensor_names[$n_sensor] = $label;
     $sql .= "," . $sensor;

     $data[$tag][$label] = $sensors[$sensor]['units'];
   }
   $sql .= " from data where is_usable and site_id=?"
        . " and data_time >=? and data_time <=?"
        . " order by data_time";

   $tag = "values";
   $data[$tag] = array();

   foreach( $selected_sites as $site_id ) {
     $site_name = $sites[$site_id]['name'];
     $site_needs_adding = true;

     $stmt = $dbh->prepare( $sql );

     $stmt->bindParam( 1, $site_id );
     $stmt->bindParam( 2, $start );
     $stmt->bindParam( 3, $end );

     $stmt->execute();

     while( $row = $stmt->fetch() ) {
        $date = $row[0];  //date is always first

        $values = array();

        $got_non_null = false;
        for ( $i = 1; $i <= $n_sensor; $i++ ) {
           if ( isset( $row[$i] ) ) {
             $values[$sensor_names[$i]] = $row[$i];
             $got_non_null = true;
           }
        }

        // don't show a row if all the values were null
        if ( $got_non_null ) {
          if ( $site_needs_adding ) {
            $site_needs_adding = false;
            $data[$tag][$site_name] = array();
          }
          $data[$tag][$site_name][$date] = array();
          foreach( $values as $sensor => $value ) {
             $data[$tag][$site_name][$date][$sensor] = $value;
          }
        }
     }

     $stmt = null;
   }

   echo json_encode($data, JSON_PRETTY_PRINT);
}

function sv_output( $dbh, $sites, $sensors, $delim, $quote,
                    $selected_sites, $selected_sensors, $start, $end ) {
  // for the sql statement
  // and the header at the same time
  // if a sensor item doesn't exist, don't check - just output nothing

  $sites_clause = " and d.site_id in (";
  $comma = "";
  $site_ids = array();
  foreach( $selected_sites as $site_id ) {
     array_push( $site_ids, (int)$site_id );
     $sites_clause .= $comma . "?";
     $comma = ",";
  }
  $sites_clause .= ")";

  $sql    = "select d.data_time, s.name";
  $header = $quote . "date (UTC)" . $quote . $delim . $quote . "site" . $quote;

  $n_sensor = 0;
  foreach ( $selected_sensors as $sensor ) {
     $n_sensor++;
     $sql    .= ",d." . $sensor;

     $header .= $delim . $quote . make_sensor_label_with_units( $sensor, $sensors ) . $quote;
  }

  $sql    .= " from data d, sites s where d.is_usable"
          . " and d.data_time >=? and d.data_time <=?"
          . $sites_clause
          . " and d.site_id = s.id"
          . " order by d.data_time,s.name";
  $header .= "\n";

  $stmt = $dbh->prepare( $sql );

  $stmt->bindParam( 1, $start );
  $stmt->bindParam( 2, $end );

  // binding to params requires unique variable references, so use
  // the array access rather than the 'as' value
  $i = 0;
  foreach( $site_ids as $site_id ) {
     $stmt->bindParam( 3+$i, $site_ids[$i] );
     $i++;
  }

  $stmt->execute();

  echo( $header );

  while( $row = $stmt->fetch() ) {
     $output  = $row[0];           //date is always first
     $output .= $delim . $quote . $row[1] . $quote;  //site name second

     $got_non_null = false;
     $values       = "";
     for ( $i = 0; $i < $n_sensor; $i++ ) {
        $j = $i + 2;
        $values .= $delim;
        if ( isset( $row[$j] ) ) {
          $values .= $row[$j];
          $got_non_null = true;
        }
     }

     // don't show a row if all the values were null
     if ( $got_non_null ) {
       echo( $output );
       echo( $values );
       echo( "\n" );
     }

  }
  $stmt = null;
}
function text_output( $dbh, $sites, $sensors,
                      $selected_sites, $selected_sensors, $start, $end ) {

  // run through the data twice
  // once to get the column sizes
  // second time to do the output

  $sites_clause = " and d.site_id in (";
  $comma = "";
  $site_ids = array();
  foreach( $selected_sites as $site_id ) {
     array_push( $site_ids, (int)$site_id );
     $sites_clause .= $comma . "?";
     $comma = ",";
  }
  $sites_clause .= ")";

  $sql    = "select d.data_time, s.name";

  $column_sizes = array();
  $headers      = array();

  $headers[0] = "date (UTC)";
  $headers[1] = "site";

  $n_col = 0; $column_sizes[$n_col] = strlen( $headers[0] );
  $n_col = 1; $column_sizes[$n_col] = strlen( $headers[1] );

  $n_sensor = 0;
  foreach ( $selected_sensors as $sensor ) {
     $n_sensor++;
     $sql    .= ",d." . $sensor;
     $n_col++;
     $headers[$n_col] = make_sensor_label_with_units( $sensor, $sensors );
     $column_sizes[$n_col] = strlen( $headers[$n_col] );
  }

  $sql    .= " from data d, sites s where d.is_usable"
          . " and d.data_time >=? and d.data_time <=?"
          . $sites_clause
          . " and d.site_id = s.id"
          . " order by d.data_time,s.name";
  $header .= "\n";

  $stmt = $dbh->prepare( $sql );

  $stmt->bindParam( 1, $start );
  $stmt->bindParam( 2, $end );

  // binding to params requires unique variable references, so use
  // the array access rather than the 'as' value
  $i = 0;
  foreach( $site_ids as $site_id ) {
     $stmt->bindParam( 3+$i, $site_ids[$i] );
     $i++;
  }

  $stmt->execute();

  while( $row = $stmt->fetch() ) {
    for ( $i = 0; $i <= $n_col; $i++ ) {
       if ( $row[$i] ) {
         $column_sizes[$i] = max( $column_sizes[$i], strlen( $row[$i] ) );
       }
    }
  }

  // now output
  // go back to the database rather than storing the data from the first
  // pass in memory

  $column_formats = array();
  for ( $i = 0; $i <= $n_col; $i++ ) {
     $column_formats[$i] = "%-" . $column_sizes[$i] . "s";
  }

  $space = "";
  for ( $i = 0; $i <= $n_col; $i++ ) {
     echo( $space . sprintf( $column_formats[$i], $headers[$i] ) );
     $space = " ";
  }
  echo( "\n" );

 $stmt->execute();

  while( $row = $stmt->fetch() ) {
     $output  = sprintf( $column_formats[0], $row[0] ); //date is always first
     $output .= " ";
     $output .= sprintf( $column_formats[1], $row[1] ); //site name second

     $got_non_null = false;
     $values       = "";
     for ( $i = 0; $i < $n_sensor; $i++ ) {
        $j = $i + 2;
        $values .= " ";
        if ( isset( $row[$j] ) ) {
          $values .= sprintf( $column_formats[$j], $row[$j] );
          $got_non_null = true;
        } else {
          $values .= sprintf( $column_formats[$j], " " );
        }
     }

     // don't show a row if all the values were null
     if ( $got_non_null ) {
       echo( $output );
       echo( $values );
       echo( "\n" );
     }
  }

  $stmt = null;
}

?>
