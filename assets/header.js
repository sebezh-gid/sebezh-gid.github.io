/**
 * Header animation.
 **/
jQuery(function ($) {
    $(document).on("click", "#showsearch", function (e) {
        e.preventDefault();
        $("header .toggle").toggleClass("hidden");
        $("header input[type=text]").focus();
    });

    $(document).on("click", "#showmap", function (e) {
        e.preventDefault();
        $("header #hmap").slideToggle(100);
        $(this).blur();
    });

    $(document).on("click", "header button[type=reset]", function (e) {
        $("header .toggle").toggleClass("hidden");
    });
});
