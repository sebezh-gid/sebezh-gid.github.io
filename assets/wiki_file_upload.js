/**
 * Adds the upload file button.
 **/
jQuery(function ($) {
    $(".wiki_buttons").append('<li><a id="wiki_btn_upload" class="btn btn-default" href="/wiki/files" target="_blank" title="Загрузить фото"><i class="fa fa-image"></i></a></li>');

    $(document).on("click", "#wiki_btn_upload", function (e) {
        if ($("#dlg-upload").length == 0) {
            alert("#dlg-upload not found");
            return;
        }

        e.preventDefault();

        $("#dlg-upload .msgbox").hide();
        $("#dlg-upload, #block").show();
        $("#dlg-upload")[0].reset();
        $(".uploadLink").focus();
    });
});
