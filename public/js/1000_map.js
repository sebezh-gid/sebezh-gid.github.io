jQuery(function ($) {
  var create_map = function (div_id) {
    var osm_layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });

    /*
    var osmfr_layer = L.tileLayer('http://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
      maxZoom: 20,
      attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    });
    */

    // UNOFFICIAL HACK.
    // http://stackoverflow.com/questions/9394190/leaflet-map-api-with-google-satellite-layer
    /*
    var google_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
      maxZoom: 19,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });
    */

    var google_hybrid_layer = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
      maxZoom: 19,
      subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
    });

    var map = L.map(div_id, {
      layers: [osm_layer],
      loadingControl: true,
      fullscreenControl: true
    });

    L.control.layers({
      "OpenStreetMap": osm_layer,
      // "OSM France (больше зум)": osmfr_layer,
      "Google Satellite": google_hybrid_layer
    }).addTo(map);

    return map;
  };

  $(".map").each(function () {
    var div = $(this);
    if (!div.attr("id"))
      return;

    div.html("");

    var source = div.attr("data-src");
    var center = div.attr("data-center");
    var zoom = parseInt(div.attr("data-zoom") || 13);
    var draggable = div.hasClass("draggable");

    if (source) {
      $.ajax({
        url: source,
        dataType: "json"
      }).done(function (res) {
        res = $.extend({
          markers: []
        }, res);

        var map = create_map(div.attr("id"));

        var points = [];
        var cluster = L.markerClusterGroup();

        for (var idx in res.markers) {
          var tree = res.markers[idx];
          if (tree.latlng) {
            points.push(tree.latlng);

            var m = L.marker(tree.latlng);
            m.addTo(cluster);

            var html = "<p><a href='" + tree.link + "'>" + tree.title + "</a></p>";
            m.bindPopup(html);
          }
        }

        map.addLayer(cluster);

        var bounds = L.latLngBounds(points);
        map.fitBounds(bounds);
      });
    }

    else if (center) {
      var set_ll = function (ll) {
          var set = function (attr, value) {
            var sel = div.attr(attr);
            if (sel) {
              var ctl = $(sel);
              if (ctl.length)
                ctl.val(value);
            }
          }

          set("data-for", ll.lat + ", " + ll.lng);
          set("data-for-lat", ll.lat);
          set("data-for-lng", ll.lng);
      };

      var parts = center.split(/,\s*/);
      if (parts.length == 2) {
        var latlng = [parseFloat(parts[0]), parseFloat(parts[1])];
        var map = create_map(div.attr("id"));

        var marker = L.marker(latlng, {
          draggable: draggable
        });
        marker.on("dragend", function (e) {
          set_ll(marker.getLatLng());
        });
        marker.addTo(map);

        map.on("click", function (e) {
          if (draggable) {
            marker.setLatLng(e.latlng);
            set_ll(e.latlng);
          }
        });

        map.setView(latlng, zoom);
      }
    }
  });
});
