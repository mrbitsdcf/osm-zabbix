<!DOCTYPE HTML>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Ugly page with map</title>
    <link rel="stylesheet" href="http://openlayers.org/dev/theme/default/style.css" type="text/css">
    <link rel="stylesheet" href="http://openlayers.org/dev/examples/style.css" type="text/css">
    <style type="text/css">
      html, body, #basicMap {
          width: 95%;
          height: 95%;
          margin: 2%;
      }
      .olControlLoadingPanel {
            background-image:url(images/loading.gif);
            position: relative;
            width: 195px;
            height: 11px;
            left: 45%;
            top: 45%;
            background-position:center;
            background-repeat:no-repeat;
            display: none;
        }
    </style>
    <script src="openlayers/OpenLayers.js"></script>
    <script src="openlayers/lib/OpenLayers/Control/LoadingPanel.js"></script>
    <script>
        var fromProjection = new OpenLayers.Projection("EPSG:4326"); // transform from WGS 1984
        var toProjection = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection
        var centerPosition = new OpenLayers.LonLat(<?php require('osm-zabbix.conf.php'); print($center_lon . "," . $center_lat); ?>).transform(fromProjection, toProjection);
        function init() {
            var options = {
              controls: [
                new OpenLayers.Control.Navigation(),
                new OpenLayers.Control.PanZoomBar(),
                new OpenLayers.Control.LayerSwitcher(),
                new OpenLayers.Control.Attribution(),
                new OpenLayers.Control.LoadingPanel(),
                new OpenLayers.Control.MousePosition({
                    displayProjection: fromProjection,
                    prefix: 'Longitude, latitude: '
                })
              ]
            };
            map = new OpenLayers.Map("basicMap", options);
            var mapnik         = new OpenLayers.Layer.OSM();
            var zoom           = <?php require('osm-zabbix.conf.php'); print($zoom_level); ?>;
            map.addLayer(mapnik);
            map.setCenter(centerPosition, zoom);

            function toggleLoadingPanel() {
                map.getControlsByClass('OpenLayers.Control.LoadingPanel')[0].toggle();
            }

            <?php 
                require('osm-zabbix.conf.php');
                require('osm-zabbix.php');
                
                $api = connect_to_api($zbx_url,$zbx_api_user,$zbx_api_pass);
                $groupids = get_groupids($api);
                $layers = array();
                foreach ($groupids as $groupid) {
                    $groupname = get_group_name($api,$groupid);
                    $layer = "objlayer_" . $groupid . "_ok";
                    array_push($layers, $layer);
                    print("\tvar " . $layer . " = new OpenLayers.Layer.Vector( \"" . $groupname . " - OK\", { strategies: [new OpenLayers.Strategy.BBOX({resFactor: 1.1})], protocol: new OpenLayers.Protocol.HTTP({ url:\"osm-zabbix.php?withproblems=no&groupid=" . $groupid . "\", format: new OpenLayers.Format.Text() }) });\n");
                    print("\tmap.addLayer(" . $layer . ");\n");
                    print("\t" . $layer . ".setVisibility(false);\n");
                    print("\t" . $layer . ".events.on({ 'featureselected': onFeatureSelect, 'featureunselected': onFeatureUnselect });\n");
                    $layer = "objlayer_" . $groupid . "_problems";
                    array_push($layers, $layer);
                    print("\tvar " . $layer . " = new OpenLayers.Layer.Vector( \"" . $groupname . " - problems\", { strategies: [new OpenLayers.Strategy.BBOX({resFactor: 1.1})], protocol: new OpenLayers.Protocol.HTTP({ url:\"osm-zabbix.php?withproblems=yes&groupid=" . $groupid . "\", format: new OpenLayers.Format.Text() }) });\n");
                    print("\tmap.addLayer(" . $layer . ");\n");
                    print("\t" . $layer . ".events.on({ 'featureselected': onFeatureSelect, 'featureunselected': onFeatureUnselect });\n");
                }
                print("\tselectControl = new OpenLayers.Control.SelectFeature([" . implode(",",$layers) . "], { hover: true });\n");
                print("\tmap.addControl(selectControl);\n");
                print("\tselectControl.activate();\n");
            ?>
        /*  function onPopupClose(evt) {
                var feature = this.feature;
                if (feature.layer) {
                    selectControl.unselect(feature);
                } else {
                    this.destroy();
                }
            } */
            function onFeatureSelect(evt) {
                feature = evt.feature;
                popup = new OpenLayers.Popup.FramedCloud("featurePopup",
                                         feature.geometry.getBounds().getCenterLonLat(),
                                         new OpenLayers.Size(100,100),
                                         "<h2>"+feature.attributes.title + "</h2>" +
                                         feature.attributes.description,
                                         //null, false, onPopupClose);
                                         null);
                feature.popup = popup;
                popup.feature = feature;
                map.addPopup(popup, true);
            }
            function onFeatureUnselect(evt) {
                feature = evt.feature;
                if (feature.popup) {
                    popup.feature = null;
                    map.removePopup(feature.popup);
                    feature.popup.destroy();
                    feature.popup = null;
                }
            }
        }
    </script>
  </head>
  <body onload="init();">
    <h3>Ugly page with map</h3>
    Only 'problem' layers are shown by default. Use layer switcher in the right upper corner of the map to show/hide layers.
    <div id="basicMap"></div>
  </body>
</html>