<?php

// ---------------------------
function is_json($string) {
 json_decode($string);
 return (json_last_error() == JSON_ERROR_NONE);
}

// ----------------------------------------------------
function show_error( $message ) {
  echo( "<span class='error'>ERROR: $message</span>" );
}

// ----------------------------------------------------
function show_warning( $message ) {
  echo( "<span class='warning'>WARNING: $message</span>" );
}

// ----------------------------------------------------
function week_before( $date ) {
  date_default_timezone_set("UTC");
  $date = strtotime( $date );
  return date('Y-m-d', strtotime("-7 day", $date) );
}

// ----------------------------------------------------
function item_is_showable( $is_private, $details ) {
   $result = false;

   if ( $details['is_usable'] ) {
      if ( $is_private ) {
        $result = $details['for_private'];
      } else {
        $result = $details['for_public'];
      }
   }

   return $result;
}

// ----------------------------------------------------
function is_private_link() {
  // was the current page referenced by this site's private area

  $result = false;

  if ( isset( $_SERVER['HTTP_REFERER'] ) && $_SERVER['HTTP_REFERER'] ) {
     $refer = strtolower( $_SERVER['HTTP_REFERER'] );
     $here  = strtolower( $_SERVER['SERVER_NAME'] );

     $refer = str_replace( "http://", "", str_replace( "https://", "", $refer ) );
     $here  = str_replace( "http://", "", str_replace( "https://", "", $here  ) );

     // is this the same site
     if ( strpos($refer, $here) === 0 ) {
        if ( strpos($refer, "/private") !== false ) {
           $result = true;
        }
     }

  } else {
     // didn't come from a separate page, check this one
     $page = strtolower( $_SERVER['REQUEST_URI'] );
     if ( strpos($page, "/private") !== false ) {
        $result = true;
     }
  }

  return $result;
}

// ----------------------------------------------------
function sorted_site_names( $sites, &$result ) {
  // given the list of sites, return a mapping of name => id
  // sorted by name

  foreach( $sites as $id => $detail ) {
    $result[$detail['name']] = $id;
  }

  sort( $result );
}

// ----------------------------------------------------
function remove_leading_zeros( $s ) {
  if ( isset( $s ) ) {
    if ( $s != "" ) {
      if ( $s != "0" ) {
         $s = ltrim( $s, "0" );
         if ( $s == "" ) {
           $s = "0";
         }
      } 
    }
  }
  return $s;
}

// ----------------------------------------------------
function time_offset_from_today_utc( $given ) {
  date_default_timezone_set('UTC');
  $date1 = new DateTime( $given );
  $date2 = new DateTime( gmdate("Y-m-d H:i:s") );
  $diff = $date2->diff($date1);
  return $diff->format('%a day %h hours') . " ago";
}

// ----------------------------------------------------
function hours_offset_from_today_utc( $given ) {
  date_default_timezone_set('UTC');
  $date1 = new DateTime( $given );
  $date2 = new DateTime( gmdate("Y-m-d H:i:s") );
  $diff = $date2->diff($date1);
  return $diff->format('%a') * 24 + $diff->format('%h');
}

// ----------------------------------------------------
function is_valid_date( $string ) {
  $ok = false;

  if ( $string ) {
    if ( preg_match( "/^[1-2]\d\d\d-\d\d?-\d\d?$/", $string ) ) {
      list( $year, $month, $day ) = split( "-", $string );
      $ok = checkdate( $month, $day, $year );
    }
  }

  return $ok;
}

// ----------------------------------------------------
function handle_requests() {
  // Use the following to take care of PHP slashes no matter what the setting
  // of magic quotes.

  foreach( $_REQUEST as $key => $value ) {
    if ( is_string( $value ) ) {
      $_REQUEST[$key] = trim( stripslashes( $value ) );
    }
  }
}

// ----------------------------------------------------
function make_sensor_units_for_cdf( $sensor, $sensors ) {
   $units = $sensors[$sensor]['units'];
   if ( $sensors[$sensor]['cdf_units'] ) {
      $units = $sensors[$sensor]['cdf_units'];
   }
   return $units;
}

// ----------------------------------------------------
function make_sensor_label_with_units( $sensor, $sensors ) {
   $label = make_sensor_label( $sensor );
   $units = $sensors[$sensor]['units'];
   if ( isset( $units ) and ( $units != '' ) ) {
     $label .= " (" . $units . ")";
   }
   return $label;
}

// ----------------------------------------------------
function make_sensor_short_label_with_units( $sensor, $sensors ) {
   $label = make_sensor_short_label( $sensor, $sensors[$sensor]['short_label'] );
   $units = $sensors[$sensor]['units'];
   if ( isset( $units ) and ( $units != '' ) ) {
     $label .= " (" . $units . ")";
   }
   return $label;
}

// ----------------------------------------------------
function make_sensor_short_label( $name, $short_label ) {
   $label = $name;
   if ( $short_label ) {
     $label = $short_label;
   } else {
     $label = str_replace( "_", " ", $name );
   }
   return $label;
}

// ----------------------------------------------------
function make_sensor_label( $name ) {
   $label = $name;

   if ( $name == "temperature" ) {
     $label = "water temperature";
   } else {
     $label = str_replace( "_", " ", $name );
   }

   return $label;
}

// ----------------------------------------------------
function site_exists( $name, $list ) {
  // if it does: return the id, otherwise null
  $result = null;
  if ( isset( $name ) ) {
    $name = strtolower( $name );
    foreach( $list as $id => $detail ) {
       if ( $name == $detail['web_key'] ) {
         $result = $id;
         break;
       }
    }
  }
  return $result;
}

?>
