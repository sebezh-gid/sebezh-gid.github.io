/**
 * Show image thumbnails only when they're visible.
 * This saves a lot of resources on pages with a lot of images,
 * like, when you display the whole photo archive.
 **/
jQuery(function ($) {
    var tid = null;

    var update = function () {
        if (tid === null) {
            tid = setTimeout(function () {
                $(".thumbnail.lazy").each(function (e) {
                    var it = $(this).offset().top,
                        wt = $(window).scrollTop(),
                        wb = wt + $(window).height();

                    if (it >= wt && it <= wb) {
                        var src = $(this).attr("data-src");
                        $(this).css("background-image", "url(" + src + ")");
                        $(this).removeClass("lazy");
                    }
                });

                tid = null;
            }, 100);
        }
    };

    if ($(".thumbnail.lazy").length > 0) {
        update();

        $(window).on("scroll", update);
        $(window).on("resize", update);
    }

    $(document).on("paste", "input.uploadLink", function (e) {
        var f = $(this).closest("form");
        setTimeout(function () {
            f.submit();
        }, 100);
    });

    $(document).on("click", ".showUpload", function (e) {
        e.preventDefault();
        $("#dlg-upload .msgbox").hide();
        $("#dlg-upload, #block").show();
        $("#dlg-upload")[0].reset();
        $(".uploadLink").focus();
    });
});


window.editor_insert = function (text)
{
    var ta = $("textarea.wiki")[0];

    var v = ta.value,
        s = ta.selectionStart,
        e = ta.selectionEnd;

    var ntext = v.substring(0, s) + text + v.substring(e);
    ta.value = ntext;
    ta.selectionStart = e + text.length;
    ta.selectionEnd = e + text.length;

    $("#block, .dialog").hide();
    $("textarea.wiki").focus();
}
