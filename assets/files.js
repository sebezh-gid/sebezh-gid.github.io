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
        $(this).closest("form").submit();
    });
});
