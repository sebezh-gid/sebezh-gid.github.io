jQuery(function ($) {
	enable_edit_hotkey();
});


function enable_edit_hotkey()
{
    $(document).on("keydown", function (e) {
        if (e.ctrlKey && e.keyCode == 69) {  // "e"
            var link = null;

			if (window.location.pathname == "/wiki") {
				var link = "/edit" + window.location.search;
				window.location.href = link;
			}
        }
    });
}
