jQuery(function ($) {
    $(document).on("keydown", function (e) {
        if (e.keyCode == 27) {
            $(".dialog, #block").hide();
        }
    });

    $(document).on("click", ".dialog .cancel", function (e) {
        e.preventDefault();
        $(this).closest(".dialog").hide();

        if ($(".dialog:visible").length == 0)
            $("#block").hide();
    });
});
