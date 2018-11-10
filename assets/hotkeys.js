jQuery(function ($) {
    $(document).on("keydown", function (e) {
        if (e.ctrlKey && e.keyCode == 69) {  // "e"
            var links = $("link[rel=edit]");
            if (links.length == 1) {
                window.location.href = links.eq(0).attr("href");
            }
        }

        if (e.ctrlKey && e.key == "D") {  // "d"
            e.preventDefault();
            var link = window.location.href;
            if (window.location.search)
                link += "&debug=tpl";
            else
                link += "?debug=tpl";
            window.location.href = link;
        }
    });

    $(document).on("keydown", "form", function (e) {
        if (e.ctrlKey && e.keyCode == 13) {
            $(this).find(".btn-primary").eq(0).click();
        }
    });

    $(document).on("keydown", "textarea.markdown", function (e) {
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
});
