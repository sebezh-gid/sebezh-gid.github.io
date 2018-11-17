jQuery(function ($) {
  var create_map = function (div_id) {
    var osm_layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: 'Map data © <a href="http://openstreetmap.org">OSM</a> contributors'
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
      fullscreenControl: true,
      scrollWheelZoom: false
    });

    L.control.layers({
      "OpenStreetMap": osm_layer,
      // "OSM France (больше зум)": osmfr_layer,
      "Google Satellite": google_hybrid_layer
    }).addTo(map);

    map.on("focus", function () {
        map.scrollWheelZoom.enable();
    });

    map.on("blur", function () {
        map.scrollWheelZoom.disable();
    });

    return map;
  };


    var cluster_map = function (div_id, markers)
    {
        var map = create_map(div_id);

        var points = [];
        var cluster = L.markerClusterGroup();

        for (var idx in markers) {
            var m = $.extend({
                latlng: null,
                html: null,
                link: null,
                title: null,
                image: null
            }, markers[idx]);

            if (m.latlng) {
                points.push(m.latlng);

                var m2 = L.marker(m.latlng);
                m2.addTo(cluster);

                var html = null;
                if (m.html !== null)
                    html = m.html;
                else if (m.link && m.title)
                    html = sfmt("<p><a href='{0}'>{1}</a></p>", m.link, m.title);
                else if (m.title)
                    html = sfmt("<p>{0}</p>", m.title);
                if (m.image)
                    html += sfmt("<p><a href='{0}'><img src='{1}' width='300'/></a></p>", m.link, m.image);

                if (html !== null)
                    m2.bindPopup(html);
            }
        }

        map.addLayer(cluster);

        if (markers.length > 1) {
            var bounds = L.latLngBounds(points);
            map.fitBounds(bounds);
        } else {
            map.setView(markers[0].latlng, 12);
        }

        map.on("click", function (e) {
            if (div_id == 'testmap') {
                var ll = sfmt("{0},{1}", e.latlng.lat, e.latlng.lng);
                var html = sfmt("<div class='map' data-center='{0}'></div>", ll);
                $("pre:first code").text(html);
            } else if (e.originalEvent.ctrlKey) {
                var ll = sfmt("{0},{1}", e.latlng.lat, e.latlng.lng);
                var html = sfmt("<div class=\"map\" data-center=\"{0}\"></div>", ll);
                console.log("map center: " + ll);
                console.log("map html: " + html);
            }
        });
    };


    var center_map = function (div_id, ll, zoom) {
        var map = create_map(div_id);
        var marker = L.marker(ll).addTo(map);
        map.setView(ll, zoom);
    };

    $(".map").each(function () {
      var div = $(this);
      if (!div.attr("id")) {
          var id = "map_" + Math.round(Math.random() * 999999);
          div.attr("id", id);
      }

      div.html("");

      var source = div.attr("data-src");
      var points = div.attr("data-points");
      var center = div.attr("data-center");
      var zoom = parseInt(div.attr("data-zoom") || 13);

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

      else if (points) {
        var points = JSON.parse(points),
            markers = [];

        for (var idx in points) {
            var p = $.extend({
                title: null,
                link: null,
                image: null
                }, points[idx]);

            if (p.link == null)
                p.link = "/wiki?name=" + encodeURI(p.title);

            markers.push(p);
        }

        cluster_map(div.attr("id"), markers);
      }

      else if (center) {
        var ll = center.split(/,\s*/);
        if (ll.length == 2) {
            center_map(div.attr("id"), ll, zoom);
        }
      }

      else {
          console && console.log("Map center not defined.");
      }
    });
});
