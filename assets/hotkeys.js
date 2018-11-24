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

    $(document).on("keydown", "textarea.wiki", function (e) {
        // Make wiki link from selection.
        if (e.altKey && (e.key == "]" || e.key == "ъ" || e.key == "Ъ")) {
            // TODO: load from outside.
            var fixmap = {
                "нацпарк": "Себежский национальный парк",
                "нацпарка": "Себежский национальный парк",
                "национального парка": "Себежский национальный парк"
            };

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
            x = x.replace(/^(\d{4})$/, '$1 год|$1');

            // Добавляем текст с заглавной буквы.
            // [[коза]] => [[Коза|коза]]
            if (x.indexOf("|") < 0) {
                var title = x[0].toUpperCase() + x.substr(1);
                if (title != x)
                    x = title + "|" + x;
            }

            var text = v.substring(0, s) + "[[" + x + "]]" + v.substring(e);
            this.value = text;

            if (x.indexOf("|") < 0) {
                // this.selectionStart = s + x.length + 4;
                // this.selectionEnd = s + x.length + 4;

                this.selectionStart = s + 2;
                this.selectionEnd = s + x.length + 2;
            } else {
                this.selectionStart = s + 2;
                this.selectionEnd = s + 2 + x.indexOf("|");
            }
        }

        if (e.altKey && (e.key == "." || e.key == "ю")) {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;

            var src = v.substring(s, e);
            var dst = v.substring(0, s) + "«" + src + "»" + v.substring(e);

            this.value = dst;
            this.selectionStart = s + src.length + 2;
            this.selectionEnd = s + src.length + 2;
        }
    });

    $(document).on("keydown", function (e) {
        if (e.keyCode == 191) {
            var a = $(document.activeElement);
            if (!a.is("input.search") && !a.is("input") && !a.is("textarea")) {
                console.log(e.keyCode);
                e.preventDefault();
                $("input.search:first").focus();
                return false;
            }
        }
    });
});
