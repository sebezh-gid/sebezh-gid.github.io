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
