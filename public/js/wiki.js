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

    $(document).on("keydown", "textarea.wiki", function (e) {
        // Make wiki link from selection.
        if (e.altKey && (e.key == "]" || e.key == "ъ" || e.key == "Ъ")) {
            var fixmap = {};

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
