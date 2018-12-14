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

        // Bold.
        if (e.ctrlKey && e.keyCode == 66) {
            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd;

            var src = v.substring(s, e);
            if (src == "") {
                console.log("empty selection");
            }

            else if (v[s] == "*") {
                console.log("already bold");
            }

            else if (s > 0 && v[s-1] == "*") {
                console.log("already bold");
            }

            else {
                v = v.substring(0, s) + "**" + v.substring(s, e) + "**" + v.substring(e);
                this.value = v;
                this.selectionStart = s;
                this.selectionEnd = e + 4;
                console.log("making bold");
            }

            return false;
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
                "национального парка": "Себежский национальный парк",
                "ркц": "Районный культурный центр",
                "музей": "Себежский краеведческий музей",
                "музея": "Себежский краеведческий музей",
                "музее": "Себежский краеведческий музей",
                "себежский музей": "Себежский краеведческий музей",
                "себежского музея": "Себежский краеведческий музей",
                "себежском музее": "Себежский краеведческий музей",
                "себежа": "Себеж",
                "себеже": "Себеж"
            };

            var v = this.value,
                s = this.selectionStart,
                e = this.selectionEnd,
                x = v.substring(s, e);

            if (s > 0 && v[s-1] == '[') {
                console.log("already linked");
                return false;
            }

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

    // Show search on slash.
    $(document).on("keydown", function (e) {
        var a = $(document.activeElement);

        if (e.keyCode == 191) {
            if (!a.is("input") && !a.is("textarea")) {
                console.log(e.keyCode);
                e.preventDefault();
                $("#showsearch").click();
                // $("input.search:first").focus();
                return false;
            }
        }

        else if (e.keyCode == 27) {
            if (a.is("input.search")) {
                e.preventDefault();
                $("header button[type=reset]").click();
            }
        }

        else if (e.keyCode == 61) {
            if (!a.is("input") && !a.is("textarea")) {
                $("header #showmap").click();
            }
        }

        else {
            // console.log(e.keyCode);
        }
    });
});
