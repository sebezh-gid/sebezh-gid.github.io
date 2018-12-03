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
    enable_wiki_fancybox();
    enable_toolbar();
    enable_qrcode_form();
});


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


/**
 * Embed contents by URL.
 *
 * Sends all pasted clipboard data to the server.  If it detects and URL and handles it,
 * replaces the inserted text with an embed code.  Works for images.
 **/
jQuery(function ($) {
    $(document).on("paste", "textarea.wiki", function (e) {
        var clip = e.originalEvent.clipboardData.getData("text"),
            ctl = $(this);

        var ta = ctl[0],
            tass = ta.selectionStart,
            tase = ta.selectionEnd,
            text = ta.value;

        if (tass > 0 && text[tass-1] == '"')
            return;

        $.ajax({
            url: "/wiki/embed-clipboard",
            data: {text: clip},
            type: "POST",
            dataType: "json"
        }).done(function (res) {
            var ta = ctl[0],
                tass = ta.selectionStart,
                tase = ta.selectionEnd,
                text = ta.value;

            res = $.extend({
                replace: [],
                open: []
            }, res);

            for (var idx in res.replace) {
                var src = res.replace[idx].src,
                    dst = res.replace[idx].dst;

                var i = text.indexOf(src);
                if (i >= 0) {
                    // selection inside the url
                    if (tass >= i && tass < i + src.length) {
                        tass = i;
                        tase = i;
                    }

                    // selection after the url, shift by length difference
                    if (tass >= i + src.length) {
                        var diff = src.length - dst.length;
                        tass -= diff;
                        tase -= diff;
                    }
                }

                text = text.replace(src, dst);
            }

            ta.value = text;
            ta.selectionStart = tass;
            ta.selectionEnd = tase;

            for (var idx in res.open) {
                var url = res.open[idx];
                window.open(url, "_blank");
            }

            $(ctl).focus();
        });
    });
});


/**
 * Edit page sections.
 **/
jQuery(function ($) {
    var link = $("link[rel=edit]:first");
    if (link.length == 0)
        return;

    var base = link.attr("href");

    $(".formatted h1, .formatted h2, .formatted h3, .formatted h4, .formatted h5").each(function () {
        if ($(this).closest("#toc").length > 0)
            return;

        var text = $(this).text().replace('\xa0', ' ');
        var link = base + "&section=" + encodeURI(text);
        $(this).append("<a class='edit' href='" + link + "'>edit</a>");
    });
});
