<?php

// -----------------------------------------------------------------------
function main_webpage( $is_private, $data_files, $max_date, $texts, $sites, $sensors ) {
   if ( $texts['notes'] ) {
      echo "<div id='notes'>" . $texts['notes'] . "</div>";
   }

   echo "<div id='lhs'>\n";
   show_lhs( $is_private, $data_files, $max_date, $sites, $sensors );
   echo "</div>\n";

   echo "<div id='rhs'>\n";
   show_rhs();
   echo "</div>\n";
}

// -----------------------------------------------------------------------
function show_lhs( $is_private, $data_files, $max_date, $sites, $sensors ) {
   echo "<div id='plot'>\n";

   show_plot_selection( $is_private, $max_date, $sites, $sensors );

   echo "</div>\n";

   // specially restricted links
   if ( $is_private ) {
      echo <<<EOF
<br><br>
<div id="data">
<a href="/data">Raw data</a>
<br><br>
<a href="/private/admin.php">Admin pages</a>
</div>
EOF;
   }
}

// -----------------------------------------------------------------------
function show_plot_selection( $is_private, $max_date, $sites, $sensors ) {
   echo <<<EOF
<span class="title">Custom plots</span>
<br><br>
<form method="post" action="/plot/" target="_blank">
<table id="plot-options"><tr>
<td>
EOF;
   show_sensors( $is_private, $sensors );

   echo <<<EOF
</td>
<td>
EOF;

   show_sites( $is_private, $sites );

   echo <<<EOF
<br><br>
<div id="date-selection">
EOF;

   show_dates( $max_date );

   echo <<<EOF
</div>
<br><input type="submit" value="Go">
</td>
</tr></table>
</form>
EOF;
}

// -----------------------------------------------------------------------
function show_dates( $max_date ) {
   $end   = explode( " ", $max_date )[0];
   $start = week_before( $end );

   echo <<<EOF
Start <span style="font-size: smaller;">(UTC)</span><br>
<input type="text" name="start" id="startDatePicker" style="width: 100px;" value="$start">
<br><br>End <span style="font-size: smaller;">(UTC)</span><br>
<input type="text" name="end" id="endDatePicker" style="width: 100px;" value="$end">
EOF;
}

// -----------------------------------------------------------------------
function show_sites( $is_private, $sites ) {
   // sites are indexed by id, don't bother doing sort on name for now
   foreach( $sites as $id => $detail ) {
      if ( item_is_showable( $is_private, $detail ) ) {
         $name = $detail['name'];
         $key  = $detail['web_key'];
         $check= "";
         if ( $detail['checked'] ) {
            $check = " checked";
         }
         echo <<<EOF
<label><input type="checkbox" name="site[]" value="$key"$check>$name</label>

EOF;
      }
   }
}

// -----------------------------------------------------------------------
function show_sensors( $is_private, $sensors ) {
   ksort( $sensors );
   foreach( $sensors as $name => $detail ) {
      if ( item_is_showable( $is_private, $detail ) ) {
         $label = make_sensor_short_label( $name, $detail['short_label'] );
         $check= "";
         if ( $detail['checked'] ) {
            $check = " checked";
         }
         echo <<<EOF
<label><input type="checkbox" name="sensor[]" value="$name"$check>$label</label>

EOF;
      }
   }
}

// -----------------------------------------------------------------------
function show_rhs() {
   echo <<<EOF
<span class="caption">Click on a marker for latest data summary.
Legend: <img class="mark-now" src="/images/icons8-black-open-circle.png" alt="2h">: Within 2h,
        <img class="mark-today" src="/images/icons8-black-half-circle.png" alt="24h">: 24h,
        <img class="mark-old" src="/images/icons8-black-full-circle.png" alt="older">: older.</span>
<div id="map-area"></div>
<script src="/map.js"></script>
EOF;
}

?>
