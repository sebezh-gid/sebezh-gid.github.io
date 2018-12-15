/**
 * Photoalbum animation.
 * Shows the more-less buttons.
 **/
jQuery(function ($) {
    $(".photoalbum").each(function () {
        var width = 0,
            max_width = $(this).width() - 20;

        $(this).find("a.image").each(function () {
            width += $(this).width() + 5;
            if (width >= max_width)
                $(this).addClass("overflow");
        });

        var of = $(this).find("a.image.overflow");
        if (of.length > 0) {
            of.hide();
            $(this).append("<div class='showmore'><i class='fas fa-chevron-circle-right'></i></div>");
        }
    });

    $(document).on("click", ".photoalbum .showmore", function (e) {
        $(this).closest(".photoalbum").find(".overflow").show();
        $(this).hide();
    });
});
