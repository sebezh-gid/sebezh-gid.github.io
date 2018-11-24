window.leaflet_icons = {};

jQuery(function ($) {

    /**
     * Returns the named icon.
     **/
    var get_icon = function (name) {
        if (name in window.leaflet_icons)
            return window.leaflet_icons[name];

        else if (name == "") {
            icon = new L.Icon.Default();
            window.leaflet_icons[name] = icon;
            return icon;
        }

        else {
            icon = L.icon({
                iconUrl: "/images/map/" + name + ".png",
                iconSize: [32, 37],
                iconAnchor: [16, 37]
            });
            window.leaflet_icons[name] = icon;
            return icon;
        }
    };

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

    // https://github.com/gokertanrisever/leaflet-ruler
    L.control.ruler({
        position: "topleft",
        lengthUnit: {
            display: "км",
            decimal: 2,
            factor: null,
            label: "Расстояние:"
        },
        angleUnit: {
            display: "&deg;",
            decimal: 2,
            factor: null,
            label: "Азимут:"
        }
    }).addTo(map);

    map.on("focus", function () {
        map.scrollWheelZoom.enable();
    });

    map.on("blur", function () {
        map.scrollWheelZoom.disable();
    });

    window.my_map = map;
    return map;
  };


    var clickr = function (map) {
        map.on("click", function (e) {
            var ctl = $("#map_ll");
            if (ctl.length > 0) {
                console.log(sfmt("map: new ll: {0},{1}", e.latlng.lat, e.latlng.lng));
                ctl.val(e.latlng.lat + "," + e.latlng.lng);
                window.map_marker.setLatLng(e.latlng);
            }
        });
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

                if (m.image && m.link) {
                    html += sfmt("<p><a href='{0}'><img src='{1}' width='300'/></a></p>", m.link, m.image);
                } else if (m.image) {
                    html += sfmt("<p><img src='{0}' width='300'/></p>", m.image);
                }

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

        clickr(map);
    };


    var center_map = function (div_id, ll, zoom) {
        var draggable = $("#map_ll:first").length == 1;

        var map = create_map(div_id);
        var marker = L.marker(ll, {
            draggable: draggable
        }).addTo(map);

        marker.on("dragend", function (e) {
            var ll = marker.getLatLng(),
                text = ll.lat + "," + ll.lng;
            $("#map_ll").val(text);
            console.log("map: new ll: " + text);
        });

        map.setView(ll, zoom);

        $("#map_ll").val(ll[0] + "," + ll[1]);

        window.map_marker = marker;
        clickr(map);
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
            var tree = $.extend({
                latlng: null,
                title: null,
                link: null,
                description: null,
                icon: null
            }, res.markers[idx]);

            if (tree.latlng) {
              points.push(tree.latlng);

              if (tree.icon) {
                var icon = get_icon(tree.icon);
                var m = L.marker(tree.latlng, {icon: icon});
                m.addTo(cluster);
              } else {
                var m = L.marker(tree.latlng);
                m.addTo(cluster);
              }

              var html;
              if (tree.link)
                  html = "<p><a href='" + tree.link + "'>" + tree.title + "</a></p>";
              else
                  html = "<p>" + tree.title + "</p>";

              if (tree.description)
                  html += "<div class='poi-description'>" + tree.description + "</div>";

              m.bindPopup(html);
            }
          }

          map.addLayer(cluster);

          if (points.length == 1) {
            map.setView(points[0], 12);
          } else {
            var bounds = L.latLngBounds(points);
            map.fitBounds(bounds);
          }
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

            /*
            if (p.link == null)
                p.link = "/wiki?name=" + encodeURI(p.title);
            */

            markers.push(p);
        }

        cluster_map(div.attr("id"), markers);
      }

      else if (center) {
        var ll = center.split(/,\s*/);
        if (ll.length == 2) {
            center_map(div.attr("id"), ll, zoom);
            var icon = div.attr("data-icon");
            if (icon)
                window.map_marker.setIcon(get_icon(icon));
        }
      }

      else {
          console && console.log("Map center not defined.");
      }
    });

    $(document).on("click", "#addmap", function (e) {
        var ctl = $(this).closest("form").find("textarea"),
            ta = ctl[0],
            dlg = $("#dlg-map");

        if (dlg.length == 0)
            return;

        e.preventDefault();

        if (dlg.is(":visible"))
            return;

        // Insert the tag if necessary.
        if (ta.value.indexOf("[[map:") < 0) {
            var name = $(this).closest("form").find("input[name=page_name]").val(),
                code = "[[map:" + name + "]]";

            var ts = ta.selectionStart;
            text = ta.value.substring(0, ts);
            text += code;
            text += ta.value.substring(ts);
            ta.value = text;
            ta.selectionStart = ts + code.length;
            ta.selectionEnd = ta.selectionStart;
            ctl.focus();
        }

        $.ajax({
            url: "/map/suggest-ll",
            data: {tag: name},
            type: "POST",
            dataType: "json"
        }).done(function (res) {
            $("#dlg-map .msgbox").hide();
            $("#dlg-map").show();
            $("#dlg-map .title").focus();

            if (!$("#map_dlg").hasClass("leaflet-container")) {
                center_map("map_dlg", res.ll, 12);
            }
        });
    });

    $(document).on("change", "select.map_icon", function (e) {
        var name = $(this).val();

        var marker = window.map_marker,
            icon = get_icon(name);
        marker.setIcon(icon);
    });

    $(document).on("change", "#map_ll", function (e) {
        var parts = $(this).val().split(","),
            ll = [parseFloat(parts[0]), parseFloat(parts[1])];
        window.map_marker.setLatLng(ll);
        window.my_map.setView(ll, my_map.getZoom());
    });
});


window.map_embed_close = function () {
    $("#dlg-map").hide();
    $("textarea.wiki").focus();
};
