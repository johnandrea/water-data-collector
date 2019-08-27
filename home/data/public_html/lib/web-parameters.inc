<?php

// ----------------------------------------------------
function make_filename_header( $file ) {
  return "Content-Disposition: attachment; filename=\"$file\"";
}

// ----------------------------------------------------
function make_filename( $start, $end, $suffix ) {
  // make a filename to go with the values download option
  $result = "data-";

  //$result .= date_create('now')->format('Ymd-His');
  $result .= str_replace( '-', '', $start ) ."-". str_replace( '-', '', $end );

  return $result . "." . $suffix;
}

// ----------------------------------------------------
function get_cleaning_params( $newline, &$results ) {
  $error = "";

  $item = "comment";
  $results[$item] = trim( $_REQUEST[$item] );
  if ( $results[$item] == "Comment" ) {
    $results[$item] = null;
  }

  $item = "n_select";
  if ( isset( $_REQUEST[$item] ) ) {
    $value  = trim( $_REQUEST[$item] );
    if ( is_numeric( $value ) ) {
      if ( $value > 0 ) {
        if ( $value < 10000 ) {

          $n_select = $value;

          // at this point n_select is the max possible, need to count
          // how many actually exist

          // not going to bother to check that the value is a time
          // but the string muse be at least this length
          $min_time_len = strlen( "yyyyddmmhhmm" );

          $n = 0;
          $comma = "";
          for ( $i = 1; $i <= $n_select; $i++ ) {
            $item = "t" . $i;
            if ( isset( $_REQUEST[$item] ) ) {
              $value = trim( $_REQUEST[$item] );
              if ( strlen( $value ) >= $min_time_len ) {
                $n++;
                $results['selection'] .= $comma . $value;
                $comma = ",";
              }
            }
          }
          $results['n_select'] = $n;
          if ( $n < 1 ) {
            $error .= $newline . "No times selected.";
          }

        } else {
          $error .= $newline . "Number of selections is impossibly large.";
        }
      } else {
        $error .= $newline . "Number of selections is less than one.";
      }
    } else {
      $error .= $newline . "Number of selections is not numeric.";
    }

  } else {

    // if a count of possible times is not given, then there should be
    // a comma delim list of times

    $item = "selection";
    if ( isset( $_REQUEST[$item] ) ) {
      $selections = $_REQUEST[$item];
      if ( $selections ) {
        $selection_list = explode( ",", $selections );
        if ( sizeof( $selection_list ) > 0 ) {
          $results['selection'] .= $selections;
        } else {
          $error .= $newline . "No time selections.";
        }
      } else {
        $error .= $newline . "Time selections are empty.";
      }
    } else {
      $error .= $newline . "Missing time selections.";
    }

  }

  $item = "action";
  if ( isset( $_REQUEST[$item] ) ) {
    $value = strtolower( trim( $_REQUEST[$item] ) );
    if ( ($value == "hide") or ($value == "erase") ) {
      $results[$item] = $value;
    } else {
      $error .= $newline . "Cleaning action is not valid.";
    }
  } else {
    $error .= $newline . "Missing action parameter.";
  }

  return $error;
}

// ----------------------------------------------------
function get_web_parameters( $newline, &$results ) {
  // return data in an array
  // and an error message as the return value

  $error = "";

  if ( isset( $_REQUEST["site"] ) ) {
    $results["site"] = param_to_list( "site" );
  } else {
    $error .= $newline . "Missing site selection.";
  }

  if ( isset( $_REQUEST["sensor"] ) ) {
    $results["sensor"] = param_to_list( "sensor" );
  } else {
    $error .= $newline . "Missing sensor selection.";
  }

  if ( isset( $_REQUEST["start"] ) ) {
    $results["start"] = trim( $_REQUEST["start"] );
  } else {
    $results["start"] = "1990-01-01";
  }

  if ( isset( $_REQUEST["end"] ) ) {
    $results["end"] = trim( $_REQUEST["end"] );
  } else {
    $results["end"] = "2099-01-01";
  }

  if ( isset( $_REQUEST["format"] ) ) {
    $results["format"] = trim( $_REQUEST["format"] );
  } else {
    $results["format"] = "text";
  }

  return $error;
}

// ----------------------------------------------------
function param_to_list( $item ) {
  $list = "";

  $given = $_REQUEST[$item];

  if ( ! is_array( $given ) ) {
    $given = explode( ",", $given );
  }

  $comma = "";
  foreach( $given as $name ) {
     // some special cases where the user might type a name which is abbreviated
     if ( $name == "wave_direction" ) { $name = "wave_dir"; }
     if ( $name == "wind_direction" ) { $name = "wind_dir"; }

     $list .= $comma . $name;
     $comma = ",";
  }

  return $list;
}

// ----------------------------------------------------
function verify_params( $newline, $known_sites, $known_sensors, &$params ) {
  // possibly return a modified set of params

  $error = "";

  if ( is_valid_date( $params["start"] ) ) {
    // parameters specify dates, but use the time as well to make comparisons
    $params["exact_start"] = $params["start"] . " 00:00:00";
  } else {
    $error .= $newline . "Start date is invalid.";
  }
  if ( is_valid_date( $params["end"] ) ) {
    // parameters specify dates, but use the time as well to make comparisons
    $params["exact_end"] = $params["end"] . " 23:59:59";
  } else {
    $error .= $newline . "End date is invalid.";
  }

  $list    = explode( ",", $params["site"] );
  $good    = "";
  $id_list = "";
  $comma   = "";
  $already = array();
  foreach( $list as $name ) {
     $name = trim( strtolower( $name ) );
     if ( $name != "" ) {
       if ( ! isset( $already[$name] ) ) {
         $already[$name] = true;
         $site_id = site_exists( $name, $known_sites );
         if ( isset( $site_id ) ) {
           $good    .= $comma . $name;
           $id_list .= $comma . $site_id;
           $comma = ",";
         } else {
           $error .= $newline . "Site $name does not exist.";
         }
       }
     }
  }
  $params["site_names"] = $good;
  $params["site_ids"]   = $id_list;

  // don't complain about validity of each sensor, only it there are no good ones
  $list   = explode( ",", $params["sensor"] );
  $good   = "";
  $comma  = "";
  $already= array();
  foreach( $list as $name ) {
    $name = trim( strtolower( $name ) );
    if ( $name != "" ) {
       if ( ! isset( $already[$name] ) ) {
         $already[$name] = true;
         if ( isset( $known_sensors[$name] ) ) {
           $good .= $comma . $name;
           $comma = ",";
         }
      }
    }
  }
  $params["sensor"] = $good;

  $format = $params['format'];
  if ( in_array( $format, array("tsv", "csv", "json", "text", "cdl") ) ) {
  } else {
    $error .= $newline . "Invalid format selected.";
  }

  if ( $params['site_names'] == "" ) {
    $error .= $newline . "No valid sites selected.";
  }
  if ( $params['sensor'] == "" ) {
    $error .= $newline . "No valid sensors selected.";
  }

  return $error;
}

?>
