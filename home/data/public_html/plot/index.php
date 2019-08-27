<?php
 include( "../../not-served/db-config.inc" );
 include( "lib/db.inc" );
 include( "lib/common.inc" );
 include( "lib/web-parameters.inc" );
 include( "./graph.inc" );
 include( "lib/html-head-with-plotly.html" );

 include( "../web-content/page-header.html" );
 echo "<div id='content'>\n";

 main( is_private_link() );

 echo "</div>\n";
 include( "lib/html-tail.html" );

// ----------------------------------------------------
function main( $is_private ) {
  $is_private = true; //development

  echo "<div id='content'>\n";

  // get the params before going to the database

  $params = array();
  $error = get_web_parameters( "<br>", $params );

  if ( $error == "" ) {
    try {
       $dbh = connect_for_read();
       if ( $dbh ) {

         $sensors = array();
         $sites   = array();

         get_sensors( $dbh, $sensors );
         get_sites( $dbh, $sites );

         $error = verify_params( "<br>", $sites, $sensors, $params );
         if ( $error == "" ) {

            setup_help_text();

            // form a reverse lookup of the sites by name
            $site_id_by_key = array();
            foreach( $sites as $id => $detail ) {
               $site_id_by_key[$detail['web_key']] = $id;
            }

            // important that the sensors have their traces and labels
            // added to the graph in self-consistent ordering, so sort
            // them here before passing to lower level routines

            $selected_sensors = explode( ",", $params['sensor'] );
            sort( $selected_sensors );

            // sort the sites by name too

            $selected_sites = explode( ",", $params['site_names'] );
            sort( $selected_sites );

            // each selected site has all of the selected sensors
            // and order it by site name

            // if there is only one site, should the site name be part of the label?
            // for now: will do it that way

            $trace_selections     = array();
            $selected_site_by_key = array();

            foreach( $selected_sites as $site_key ) {
              $site_id   = $site_id_by_key[$site_key];
              $site_name = $sites[$site_id]['name'];
              $selected_site_by_key[$site_key] = $site_name;

              foreach( $selected_sensors as $sensor_name ) {
                 $selection = array();
                 $selection['label'] = $site_name . "/"
                                     . make_sensor_short_label_with_units( $sensor_name, $sensors );
                 $selection['site_id']  = $site_id;
                 $selection['sensor']   = $sensor_name;
                 $selection['site_key'] = $site_key; 
                 array_push( $trace_selections, $selection );
              }
            }

            $trace_sets = array();
            $trace_sets['visible'] = '';
            $trace_sets['sites']   = '';
            $trace_sets['sensors'] = '';

            $shown = graphing( $dbh, $params['exact_start'], $params['exact_end'],
                               $trace_selections, $trace_sets );

            setup_javascript( $params['start'], $params['end'],
                              $params['site_names'], $params['sensor'],
                              $trace_sets );

            if ( $shown ) {
               show_buttons( $is_private, $selected_site_by_key );
            }

         } else {
           show_error( $error );
         }

       } else {
         show_error( "Can't connect to database" );
       }

    } catch (PDOException $e) {
      show_error( "Database error" );
    }

    $dbh = null;

  } else {
    show_error( $error );
  }

  echo "</div>";
}

function setup_help_text() {
   echo <<<EOF
<div id='helpArea' style="width: 300px; z-index: 1001;"><p id="helpAreaData">
The Permalink button will display the URL which would return to this plot with
the selected options. Click the associated icon to copy the URL. The "subselect"
offers the options changed by zooming on time and traces.
<br><br>
In order to make a permalink to the data downloads: click the plain values button
to obtain the URL then change the format= setting to csv, tsv, or json.
</p></div>

EOF;
}

//---------------------------------------------------------------
function setup_javascript( $start, $end, $sites_list, $sensors_list,
                           $trace_sets ) {
  // setup global variables which will hold status information
  // for the plot interaction events
  //
  // the passed in sensors should be a sorted list of the
  // selected sensor names

  // results should be like this
  //var originalSite = 'site1,site2';
  //var originalSensors = 'conductivity,oxygen,relative_humidity,temperature';
  //var traceSensors = ['conductivity','oxygen','relative_humidity','temperature','oxygen','temperature'];
  //var traceSites = ['site1','site1','site1','site1','site2','site2'];
  //var traceVisible = [true,true,true,true,true,true];

  // The original selections  could be different from what is actually
  // displayed because the existing data might not be available at all
  // options. But the permalink will include all options because the data
  // could show up later.

  $dates = "var originalStart = '$start';\n"
         . "var originalEnd = '$end';\n"
         . "var subselectStart = '$start';\n"
         . "var subselectEnd = '$end';\n"
         ;

  $original = "var originalSite = '" . $sites_list . "';\n"
            . "var originalSensors = '" . $sensors_list . "';\n"
            ;

  // these ones are arrays of the names and boolean values of the
  // visibility of those names. these will be tied to the events
  // of clicking on the legend to hide/show traces, and those
  // event tags report the index of the labels, which is why these
  // are arrays

  $tracevisible = "var traceVisible = [" . $trace_sets['visible'] . "];\n";
  $tracesites   = "var traceSites   = [" . $trace_sets['sites'] . "];\n";
  $tracesensors = "var traceSensors = [" . $trace_sets['sensors'] . "];\n";

  echo( "<script>\n$dates$original$tracevisible$tracesites$tracesensors</script>\n" );
}

//---------------------------------------------------------------
function graphing( $dbh, $start, $end, $selections, &$trace_sets ) {
   $shown_graph = true;

   $title = "";

   begin_graph();

   $trace_comma = "";
   $trace_names = "";
   $s = 0;

   foreach( $selections as $id => $selection ) {
      $site_id = $selection['site_id'];
      $sensor  = $selection['sensor'];
      $label   = $selection['label'];

      $s++;

      $x = '';
      $y = '';

      $sql = "select data_time,$sensor"
           . " from data where is_usable and site_id=?"
           . " and data_time >= ? and data_time <= ?"
           . " and $sensor is not null"
           . " order by data_time";

      $stmt = $dbh->prepare( $sql );

      $stmt->bindParam( 1, $site_id );
      $stmt->bindParam( 2, $start );
      $stmt->bindParam( 3, $end );

      $stmt->execute();

      $n = 0;
      $comma = "";
      while( $row = $stmt->fetch() ) {
        $n++;

        $x .= $comma . "'" . $row[0] . "'";
        //$y .= $comma . sprintf( "%.4f", $row[1] );
        $y .= $comma . $row[1];

        $comma = ",";
      }

      // if there is no data don't show a label, or the empty data set
      // but the trace set counter still accounts for the visibility index

      if ( $n > 1 ) {
        $trace_name   = "set" . $s;

        $trace_sets['sites']   .= $trace_comma . "'" . $selection['site_key'] . "'";
        $trace_sets['sensors'] .= $trace_comma . "'" . $sensor . "'";
        $trace_sets['visible'] .= $trace_comma . "'true'";
        $trace_names           .= $trace_comma . $trace_name;

        $trace_comma = ",";

        echo "var $trace_name={\n";
        echo " x: [$x],\n";
        echo " y: [$y],\n";
        echo " name: '" . $label . "',\n";
        echo " mode: 'lines+markers',\n";
        echo " type: 'scatter'\n";
        echo "};\n";

      }

      $stmt = null;
   }

   if ( $trace_names == "" ) {
     $title = "No data in selected time range";
     $shown_graph = false;
   }

   end_graph( $trace_names, $title );

   return $shown_graph;
}

//---------------------------------------------------------------
function show_buttons( $is_private, $site_names ) {
  // the javascript copy function won't work from a hidden input area
  // so make it visible, but displayed way off the screen area

  $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  // since PATH_INFO doesn't work poperly, use request and clean it up
  $url = preg_replace( '/\?.*/', '', $url );

  $value_url = str_replace( "/plot", "/values", $url );
  $clean_url = str_replace( "/plot", "/private/cleaning", $url );

  // the "copy to clipboard" javascript method can only take its
  // text from an "input text" element, and that element can't be
  // hidden but it can be off screen so that its not shown
  // the "permalinkCopyArea" is tht input area.

  echo <<<EOF

<input id="permalinkCopyArea" type="text" value=""
     style="position:absolute; left:-9999px;">
<table border="0" cellspacing="0" cellpadding="2">
<tr>
  <td><button onClick="window.open('/');"><img class="button-img" title="Home" alt="" src="/images/icons8-home-32.png"></button></td>
  <td><button onClick="showOriginalPermalink('Pemalink','$url');">Permalink</button></td>
  <td><button title="in browser" onClick="valuesOriginal('$value_url','text');">Values</button></td>
  <td><button title="download" onClick="valuesOriginal('$value_url','csv');">CSV</button></td>
  <td><button title="download" onClick="valuesOriginal('$value_url','tsv');">TSV</button></td>
  <td><button title="download" onClick="valuesOriginal('$value_url','json');">JSON</button></td>
EOF;

  if ( $is_private ) {
     echo <<<EOF
  <td><button title="interactive cleaning" onClick="valuesOriginal('$clean_url',null);">Cleaning</button></td>
EOF;
  }

  echo <<<EOF
</tr>
<tr>
  <td><button onClick="showHelpPopup('Help');"><img class="button-img" title="Help" alt="" src="/images/icons8-help-blue.png"></button></td>
  <td><button onClick="showSubselectPermalink('Pemalink subselect','$url');">Permalink subselect</button></td>
  <td><button title="in browser" onClick="valuesSubselect('$value_url','text');">Values subselect</button></td>
  <td><button title="download" onClick="valuesSubselect('$value_url','csv');">CSV subselect</button></td>
  <td><button title="download" onClick="valuesSubselect('$value_url','tsv');">TSV subselect</button></td>
  <td><button title="download" onClick="valuesSubselect('$value_url','json');">JSON subselect</button></td>
EOF;

  if ( $is_private ) {
     echo <<<EOF
  <td><button title="interactive cleaning" onClick="valuesSubselect('$clean_url',null);">Cleaning subselect</button></td>
EOF;
  }

  echo <<<EOF
</tr>
</table>

EOF;
}

?>
