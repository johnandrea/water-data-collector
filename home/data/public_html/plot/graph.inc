<?php

function begin_graph() {
   echo <<<EOF
<div id='grapharea' style="width: 850; height: 500;"></div>
<div id='infoArea' style="width: 300px; z-index: 1001;">
  <p id="infoAreaData"></p>
</div>
<script>
$( "#infoArea").dialog({ autoOpen: false });
$( "#helpArea").dialog({ autoOpen: false });
function showHelpPopup( t, s ) {
   $( "#helpArea" ).dialog({ title: t });
   $( "#helpArea" ).dialog( "open" );
}
function formSubselectUrl( u ) {
  var url = u + '?start=' + subselectStart + '&end=' + subselectEnd;
  url += '&site=';
  // multiples of the same type exist, so don't list twice
  var alreadySeen = [];
  var comma = '';
  for ( i = 0; i <= traceSites.length; i++ ) {
     if ( traceVisible[i] ) {
        var theName = traceSites[i];
        if ( ! alreadySeen[theName] ) {
           alreadySeen[theName] = true;
           url += comma + theName;
           comma = ',';
        }
     }
  }
  url += '&sensor=';
  // also check for duplicates
  var alreadySeen = [];
  var comma = '';
  for ( i = 0; i < traceSensors.length; i++ ) {
     if ( traceVisible[i] ) {
       var theName = traceSensors[i];
       if ( ! alreadySeen[theName] ) {
          alreadySeen[theName] = true;
          url += comma + theName;
          comma = ',';
       }
     }
  }
  return url;
}
function formCdlSubselectUrl( u, site ) {
  var url = u + '?start=' + subselectStart + '&end=' + subselectEnd;
  url += '&site=' + site;
  url += '&sensor=';
  // also check for duplicates
  var alreadySeen = [];
  var comma = '';
  for ( i = 0; i < traceSensors.length; i++ ) {
     if ( traceVisible[i] ) {
       var theName = traceSensors[i];
       if ( ! alreadySeen[theName] ) {
          alreadySeen[theName] = true;
          url += comma + theName;
          comma = ',';
       }
     }
  }
  return url;
}
function formCdlOriginalUrl( u, site ) {
  var url = u + '?start=' + originalStart + '&end=' + originalEnd;
      url += '&site=' + site + '&sensor=' + originalSensors;
  return url;
}
function formOriginalUrl( u ) {
  var url = u + '?start=' + originalStart + '&end=' + originalEnd;
      url += '&site=' + originalSite + '&sensor=' + originalSensors;
  return url;
}
function showSubselectPermalink( t, u ) {
  var x = formSubselectUrl( u );
  showPermalinkOption( t, x ); 
}
function showOriginalPermalink( t, u ) {
  showPermalinkOption( t, formOriginalUrl( u ) ); 
}
function showPermalinkOption( t, u ) {
   document.getElementById( 'permalinkCopyArea' ).value = u;
   var copyButton = '<span title="Copy link to clipboard" onClick="permalinkCopy();">&#x1f4cb;</span>';
   document.getElementById( 'infoAreaData' ).innerHTML = copyButton +'&nbsp;'+ u;
   $( "#infoArea" ).dialog({ title: t });
   $( "#infoArea" ).dialog( "open" );
}
function permalinkCopy() {
  var theText = document.getElementById( 'permalinkCopyArea' );
  theText.select();
  document.execCommand( 'copy' );
}
function valuesCdlOriginal( url, site ) {
  // open a new tab for displaying a text output of the data values
  newWin = window.open( formCdlOriginalUrl( url, site )+'&format=cdl', '_blank' );
}
function valuesOriginal( url, format ) {
  // open a new tab for displaying a text output of the data values
  formatPart = '';
  if ( format ) { formatPart = '&format='+format; }
  newWin = window.open( formOriginalUrl( url )+formatPart, '_blank' );
}
function valuesCdlSubselect( url, site ) {
  // open a new tab for displaying a text output of the data values
  newWin = window.open( formCdlSubselectUrl( url, site )+'&format=cdl', '_blank' );
}
function valuesSubselect( url, format ) {
  // open a new tab for displaying a text output of the data values
  formatPart = '';
  if ( format ) { formatPart = '&format='+format; }
  newWin = window.open( formSubselectUrl( url )+formatPart, '_blank' );
}

// options for the graph interface
var options = { modeBarButtonsToRemove: [ 'sendDataToCloud',
                    'toggleSpikelines','hoverCompareCartesian',
                    'hoverClosestCartesian','toImage' ],
                displaylogo: false,
                modeBarButtonsToAdd: [{
                    name: 'saveImage', icon: Plotly.Icons.camera,
                    click: function(gd) { Plotly.downloadImage(
                       gd, {format: 'png', filename: 'savefile'} )}
                }]
              };

EOF;
}

function end_graph( $datanames, $label ) {
   echo <<<EOF
var graphLayout = {
  title: '$label', width: 850, height: 500,
  margin: { t: 25 },
  showlegend: true, xaxis: {type: 'date', title: {text: 'UTC'}} };
Plotly.newPlot( 'grapharea', [$datanames], graphLayout, options );

grapharea.on('plotly_relayout', function(eventdata){
   // event to grab the x axis after a zoom even
  var xMin = eventdata['xaxis.range[0]'];
  var xMax = eventdata['xaxis.range[1]'];
  // split on the space to get the day portion
  if ( xMin ) { xMin = xMin.split(' ')[0]; } else { xMin = originalStart; }
  if ( xMax ) { xMax = xMax.split(' ')[0]; } else { xMax = originalEnd; }
  subselectStart = xMin;
  subselectEnd   = xMax;
  });

grapharea.on('plotly_legendclick', function(eventdata){
  // after clicking on a label, flip the variable tracking the visibility
  var traceNum = eventdata.curveNumber;
  traceVisible[traceNum] = ! traceVisible[traceNum];
  });
</script>
EOF;
}

?>
