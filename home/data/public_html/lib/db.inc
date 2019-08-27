<?php

// Common database functions

// --------------------------------------------------
function get_website_texts( $dbh, &$data ) {
   $sql = "select value from settings where name='website header'";
   $data['header'] = read_one_value( $dbh, $sql );

   $sql = "select value from settings where name='icon url'";
   $value = read_one_value( $dbh, $sql );
   if ( $value ) {
     $data['icon url'] = $value;
   } else {
     $data['icon url'] = '#';
   }
}

// --------------------------------------------------
function get_newest_date( $dbh ) {
  $sql = "select max(data_time) from data where is_usable";
  return read_one_value( $dbh, $sql );
}

// --------------------------------------------------
function read_one_value_with_params( $dbh, $sql, $list ) {
  $result = null;

  if ( $stmt = $dbh->prepare( $sql ) ) {
     $i = 0;
     foreach( $list as $value ) {
        $i++;
        $stmt->bindParam( $i, $value );
     }
     if ( $stmt->execute() ) {
        $row = $stmt->fetch();
        $result = $row[0];
     }
  }
  $stmt = null;

  return $result;
}

// --------------------------------------------------
function read_two_values_with_params( $dbh, $sql, $list ) {
  $one = null;
  $two = null;

  if ( $stmt = $dbh->prepare( $sql ) ) {
     $i = 0;
     foreach( $list as $value ) {
        $i++;
        $stmt->bindParam( $i, $value );
     }
     if ( $stmt->execute() ) {
        $row = $stmt->fetch();
        $one = $row[0];
        $two = $row[1];
     }
  }
  $stmt = null;

  return array( $one, $two );
}

// --------------------------------------------------
function read_one_value( $dbh, $sql ) {
  $result = null;

  if ( $stmt = $dbh->prepare( $sql ) ) {
     if ( $stmt->execute() ) {
        $row = $stmt->fetch();
        $result = $row[0];
     }
  }
  $stmt = null;

  return $result;
}

// --------------------------------------------------
function read_two_values( $dbh, $sql ) {
  $one = null;
  $two = null;

  if ( $stmt = $dbh->prepare( $sql ) ) {
     if ( $stmt->execute() ) {
        $row = $stmt->fetch();
        $one = $row[0];
        $two = $row[1];
     }
  }
  $stmt = null;

  return array( $one, $two );
}

// --------------------------------------------------
function get_sensors( $dbh, &$list ) {
  $sql = "select name, units, description,cdf_units"
       . ",is_usable,for_summary,for_public,for_private,checked"
       . ",notify_on_failure,notify_on_limit,notify_limit_low,notify_limit_high"
       . ",discard_limit_low,discard_limit_high"
       . ",is_computed,failure_count,limit_count"
       . " from sensors";

  $stmt = $dbh->prepare( $sql );
  $stmt->execute();

  while( $row = $stmt->fetch() ) {
    $name = trim( $row[0] );
    $list[$name]['name']        = $name;
    $list[$name]['units']       = trim( $row[1] );
    $list[$name]['description'] = trim( $row[2] );
    $list[$name]['cdf_units']   = trim( $row[3] );
    $list[$name]['is_usable']   = $row[4];
    $list[$name]['for_summary'] = $row[5];
    $list[$name]['for_public']  = $row[6];
    $list[$name]['for_private'] = $row[7];
    $list[$name]['checked']     = $row[8];
    $list[$name]['notify_on_failure']  = $row[9];
    $list[$name]['notify_on_limit']    = $row[10];
    $list[$name]['notify_limit_low']   = $row[11];
    $list[$name]['notify_limit_high']  = $row[12];
    $list[$name]['discard_limit_low']  = $row[13];
    $list[$name]['discard_limit_high'] = $row[14];
    $list[$name]['is_computed']   = $row[15];
    $list[$name]['failure_count'] = $row[16];
    $list[$name]['limit_count']   = $row[16];
  }

  $stmt = null;
}

// --------------------------------------------------
function get_sites( $dbh, &$list ) {
  $sql = "select id, name, web_key, description, storx_serial, latitude, longitude"
       . ",is_usable,for_summary,for_public,for_private,checked"
       . ",notify_on_missing_email, notify_on_sensor_fail, notify_on_sensor_range"
       . ",newest_arrival, hours_after_email_missing, missing_email_count"
       . ",show_summary_comment, summary_comment, reported_missing_at"
       . " from sites";

  $stmt = $dbh->prepare( $sql );
  $stmt->execute();

  while( $row = $stmt->fetch() ) {
    $id = $row[0];
    $list[$id]['name']      = trim( $row[1] );
    $list[$id]['web_key']   = trim( $row[2] );
    $list[$id]['desc']      = trim( $row[3] );
    $list[$id]['storx']     = $row[4];
    $list[$id]['latitude']  = $row[5];
    $list[$id]['longitude'] = $row[6];
    $list[$id]['is_usable'] = $row[7];
    $list[$id]['for_summary']= $row[8];
    $list[$id]['for_public'] = $row[9];
    $list[$id]['for_private']= $row[10];
    $list[$id]['checked']    = $row[11];

    $list[$id]['notify_on_missing_email'] = $row[12];
    $list[$id]['notify_on_sensor_fail']   = $row[13];
    $list[$id]['notify_on_sensor_range']  = $row[14];
    $list[$id]['newest_arrival']          = $row[15];
    $list[$id]['hours_after_email_missing'] = $row[16];
    $list[$id]['missing_email_count']     = $row[17];
    $list[$id]['show_summary_comment']    = $row[18];
    $list[$id]['summary_comment']         = $row[19];
    $list[$id]['reported_missing_at']     = $row[20];
  }

  $stmt = null;
}

// --------------------------------------------------
function get_data_table_columns( $dbh, &$list ) {
  get_table_columns( $dbh, "data", $list );
}

// --------------------------------------------------
function get_table_columns( $dbh, $table, &$list ) {
  $rs = $dbh->query('SELECT * FROM data LIMIT 0');
  for ($i = 0; $i < $rs->columnCount(); $i++) {
     $col = $rs->getColumnMeta($i);
     array_push( $list, $col['name'] );
  }
}

?>
