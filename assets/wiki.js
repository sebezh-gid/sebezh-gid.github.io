var is_local = (window.location.host == "local.sebezh-gid.ru");


window.onerror = function (msg, url, line, col, error) {
  alert("Error: " + msg + "\nLine: " + line + ", col: " + col + "\nurl: " + url);
};


function sfmt(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/{(\d+)}/g, function (match, number) {
        return typeof args[number] == "undefined" ? match : args[number];
    });
}


// Parse query string, put to location.queryString.
(function () {
    location.queryString = {};
    location.search.substr(1).split("&").forEach(function (pair) {
        if (pair === "") return;
        var parts = pair.split("=");
        location.queryString[parts[0]] = parts[1] &&
            decodeURIComponent(parts[1].replace(/\+/g, " "));
    });
})();


jQuery(function ($) {
    enable_async_forms();
    enable_edit_hotkey();
    enable_wiki_fancybox();
    enable_map();
    enable_toolbar();
    enable_qrcode_form();
});


function enable_edit_hotkey()
{
    $(document).on("keydown", function (e) {
        if (e.ctrlKey && e.keyCode == 69) {  // "e"
            var link = null;

            if (window.location.pathname == "/wiki") {
                var link = "/w/edit" + window.location.search;
                window.location.href = link;
            }
        }
    });

    $(document).on("keydown", "textarea.wiki", function (e) {
        // Make wiki link from selection.
        if (e.altKey && (e.key == "]" || e.key == "ъ" || e.key == "Ъ")) {
            var fixmap = {
                "нацпарк": "Себежский национальный парк",
                "нацпарка": "Себежский национальный парк",
                "национального парка": "Себежский национальный парк",
                "себежского района": "Себежский район"
            };

            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd,
                x = v.substring(s, e);

            // Autocorrect things.
            var _x = x.toLowerCase();
            for (k in fixmap) {
                if (k == _x) {
                    x = fixmap[k] + "|" + x;
                    break;
                }
            }

            // Отдельный случай для годов.
            x = x.replace(/^(\d{4}) год(|а|у|ом)$/, '$1 год|' + x);

            var text = v.substring(0, s) + "[[" + x + "]]" + v.substring(e);
            this.value = text;
            this.selectionStart = s + x.length + 4;
            this.selectionEnd = s + x.length + 4;
        }

        // External markdown links.
        if (e.altKey && (e.key == "[" || e.key == "х")) {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;
            var text = v.substring(0, s) + "[" + v.substring(s, e) + "]()" + v.substring(e);
            this.value = text;
            this.selectionStart = e + 3;
            this.selectionEnd = e + 3;
        }

        // Make itemized list from selected lines
        if (e.altKey && e.key == "-") {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;

            var src = v.substring(s, e);

            var lines = src.match(/[^\r\n]+/g);
            for (var i in lines) {
                var line = lines[i];
                line = "- " + line.replace(/^\s+|\s+$/, "");
                while (line.substring(0, 4) == "- - ")
                    line = line.substring(2);
                lines[i] = line;
            }

            lines = lines.join("\n") + "\n";
            var dst = v.substring(0, s) + lines + v.substring(e);

            this.value = dst;
            this.selectionStart = s + lines.length;
            this.selectionEnd = s + lines.length;
        }
    });

    $(document).on("keydown", "form", function (e) {
        if (e.ctrlKey && e.keyCode == 13) {
            $(this).find(".btn-primary").eq(0).click();
        }
    });
}


function enable_map()
{
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
          var markers = [];

          markers.push({
              latlng: [parseFloat(parts[0]), parseFloat(parts[1])],
              title: div.attr("data-title") || $("h1:first").text()
          });

          /*
          markers.push({
              latlng: [56.28333, 28.48333],
              title: "Себеж",
              link: "wiki?name=Себеж"
          });
          */

          cluster_map(div.attr("id"), markers);
        }
      }

      else {
          console && console.log("Map center not defined.");
      }
    });
}


/**
 * Включение просмотра картинок через Fancybox.
 *
 * Заворачивает уменьшенные превьюшки в ссылки на исходные изображения.
 **/
function enable_wiki_fancybox()
{
    $("formatted img").each(function () {
      if ($(this).parent().is("a")) {
          $(this).parent().attr("data-fancybox", "gallery");
      } else {
          var link = $(this).attr("src");
          link = $(this).attr("data-large") || link.replace(/_small\./, ".");

          $(this).wrap("<a></a>");

          var p = $(this).closest("a");
          p.attr("href", link);
          p.attr("data-fancybox", "gallery");

          var t = $(this).attr("alt");
          if (t != "")
              p.attr("data-caption", t);
      }
    });
}


function enable_toolbar()
{
    $(document).on("click", "a.tool", function (e) {
        var dsel = $(this).attr("data-dialog");
        if (dsel) {
            $(dsel).dialog({
                autoOpen: true,
                modal: true,
                open: function () {
                    if ($(this).is("form"))
                        $(this)[0].reset();  // clean up the fields
                    $(this).find(".msgbox").hide();
                }
            });
            e.preventDefault();
        }

        var action = $(this).attr("data-action");
        if (action == "map") {
            $("#dlgMap").show();
            e.preventDefault();
        }
    });

    $(document).on("change", "#filePhoto", function (e) {
        $(this).closest("form").submit();
    });
}


function enable_async_forms()
{
    $(document).on("submit", "form.async", function (e) {
        e.preventDefault();
        var form = $(this);

        if (window.FormData === undefined) {
            alert("This function does not work in your old browser.");
            return;
        }

        $("body").addClass("wait");

        var buttons = form.find("buttons");
        var msgbox = form.find(".msgbox");
        var pgbar = form.find(".progressbar");

        buttons.prop("disabled", true);

        msgbox.hide();

        var fd = new FormData($(this)[0]);

        var show_progress = function (percent, loaded, total) {
            if ("console" in window) console.log("upload progress: " + percent + "%");

            if (true || total >= 102400) {
                var mbs = function (bytes) { return Math.round(bytes / 1048576 * 100) / 100; };

                var label = mbs(loaded) + " MB / " + mbs(total) + " MB";
                pgbar.find(".label").html(label);

                pgbar.find(".done").css("width", parseInt(percent) + "%");

                pgbar.show();
            }
        };

        var show_message = function (msg) {
            if (msgbox.length > 0) {
                msgbox.text(msg);
                msgbox.show();
            } else {
                alert(msg);
            }
        };

        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            cache: false,
            dataType: "json",
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                xhr.upload.onprogress = function (e) {
                    var pc = Math.round(e.loaded / e.total * 100);
                    show_progress(pc, e.loaded, e.total);
                };
                return xhr;
            }
        }).done(function (res) {
            res = $.extend({
                code: null,
                message: null,
                direct: null
            }, res);

            if (res.redirect) {
                window.location.href = res.redirect;
                return;
            }

            if (res.message) {
                msgbox.text(res.message);
                msgbox.show();
            }

            if (res.code) {
                var ta = $("textarea.wiki")[0],
                    tv = ta.value,
                    ss = ta.selectionStart,
                    se = ta.selectionEnd,
                    tt = tv.substring(ss, se);

                var text = tv.substring(0, ss) + res.code + tv.substring(ss);
                ta.value = text;
                ta.selectionStart = ss + res.code.length;
                ta.selectionEnd = ss + res.code.length;
                ta.focus();

                $(".dlg").dialog("close");
                ta.focus();
            }
        }).always(function () {
            $("body").removeClass("wait");
            pgbar.hide();
            buttons.prop("disabled", false);
        }).fail(function (xhr, status, message) {
            if (xhr.status == 404)
                show_message("Form handler not found.");
            else if (message == "Debug Output")
                show_message(xhr.responseText);
            else if (status == "error" && message == "")
                ;  // aborted, e.g. F5 pressed.
            else if (xhr.responseText)
                show_message("Request failed: " + xhr.responseText);
            else
                show_message(sfmt("Request failed: {0}\n\n{1}", message, xhr.responseText));
        });
    });
}


function enable_qrcode_form()
{
    var form = $("form.qrcode");
    if (form.length) {
        var update_preview = function () {
            var src = "/short/preview?" + form.serialize();
            $("#qrimg").attr("src", src).attr("data-large", src);
        };

        update_preview();
    }

}
