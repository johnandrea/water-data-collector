var host           = window.location.protocol + '//' + window.location.hostname;
var siteGeoJsonUrl = host + '/ajax.sites.geojson.php';
var popupUrl       = host + '/ajax.summary.php?site=';
var getMapCenter   = host + '/ajax.estimate-map-center.php';

// https://github.com/pointhi/leaflet-color-markers
var greenIcon = new L.Icon({
   iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
   shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
   iconSize: [25, 41],
   iconAnchor: [12, 41],
   popupAnchor: [1, -34],
   shadowSize: [41, 41]
});
var blueIcon = new L.Icon({
   iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
   shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
   iconSize: [25, 41],
   iconAnchor: [12, 41],
   popupAnchor: [1, -34],
   shadowSize: [41, 41]
});

var map = L.map('map-area');

// pick a possibly local area to start, the bounds will be reset later

$.ajax({
  url: getMapCenter,
  dataType: 'json'
})
.done(function(data){
   map.setView( [data.latitude, data.longitude], 5 );
})
.fail(function(data){
  console.log( 'Failed to obtain estimate of map center.' );
  map.setView( [42, -72], 4 );
});

map.touchZoom.disable();
// don't disable double click zoon
// siteMap.doubleClickZoom.disable();

L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1Ijoiam9obmFuZHJlYSIsImEiOiJjanVxOWU4MzgwNHJ6NDRxdWswajFoOWR0In0.oCGf6pv6BDXtvXTYAB15bg', {
  attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
  '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
  'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
	id: 'mapbox.streets'
}).addTo(map);

// elements defined as "Data" will be clickable, others otherwise not clickable

$.getJSON( siteGeoJsonUrl )
.then(function(data) {
    var allsites = L.geoJson(data);

    var sites = L.geoJson(data, {
        filter: function(feature, layer) {
            return feature.properties.siteType == "Data";
        },
        pointToLayer: function(feature, latlng) {
            return L.marker(latlng, {
                icon: greenIcon
            }).on('mouseover', function() {
                this.bindPopup(feature.properties.Name).openPopup();
            }).on('click', function() {
                var url = popupUrl + feature.properties.siteKey;
                $.get( url, function(data) {
                   var popup = L.popup()
                       .setLatLng(latlng)
                       .setContent( data )
                       .addTo(map);
                })
                .fail( function(error) {
                   var popup = L.popup()
                       .setLatLng(latlng)
                       .setContent( 'N/A' )
                       .addTo(map);
                   console.log( error );
                });
            });
        }
    });

    var others = L.geoJson(data, {
        filter: function(feature, layer) {
            return feature.properties.siteType != "Data";
        },
        pointToLayer: function(feature, latlng) {
            return L.marker(latlng, {
                 icon: blueIcon
            }).on('mouseover', function() {
                this.bindPopup(feature.properties.Name).openPopup();
            });
        }
    });

    map.fitBounds(allsites.getBounds(), {
        padding: [50, 50]
    });

    // the padding on the above bounds should have set a reasonable zoom level
    //map.setZoom( 6 );

    sites.addTo(map)
    others.addTo(map)
    map.addLayer(sites)
    map.addLayer(others)
})
.fail( function(err) {
   console.log( err.responseText );
});
