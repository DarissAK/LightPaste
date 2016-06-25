var editor = null;
var visible_content = null;
var editor_vbarpos = 90;
var ctrl_down = false;
var alt_down = false;

function resizePanels()
{
    var top = $("#panel-top");
    var bottom = $("#panel-bottom");
    var left = $("#panel-left");
    var right = $("#panel-right");
    var center = $("#panel-center");
    // window size
    var window_width = $(window).width();
    var window_height = $(window).height();
    // panel sizes
    var topheight = top.outerHeight();
    var bottomheight = bottom.outerHeight();
    var leftwidth = left.outerWidth();
    var rightwidth = right.outerWidth();
    var centerwidth = window_width - (leftwidth + rightwidth);
    var centerheight = window_height - (topheight + bottomheight);
    // resize top and bottom panels
    top.css("width", window_width + "px");
    bottom.css("width", window_width + "px");
    // position left and right panels
    left.css("bottom", bottomheight + "px");
    right.css("bottom", bottomheight + "px");
    // resize left and right panels
    var height = window_height - (topheight + bottomheight);
    left.css("height", height + "px");
    right.css("height", height + "px");
    // resize center panel
    center.css("width", centerwidth + "px")
    center.css("height", centerheight + "px");
    // position center panel
    center.css("bottom", bottomheight + "px");
    center.css("right", rightwidth + "px");
    // position editor
    var codemirror = $(".CodeMirror");
    if(codemirror.length > 0) {
        codemirror.css("position", "absolute");
        codemirror.css("bottom", bottomheight + "px");
        codemirror.css("right", rightwidth + "px");
        editor.setSize(centerwidth, centerheight);
    } else {
        var password_content = $("#password-content");
        if(password_content.length > 0) {
            password_content.css("position", "absolute");
            password_content.css("bottom", bottomheight + "px");
            password_content.css("right", rightwidth + "px");
            password_content.css("width", centerwidth + "px");
            password_content.css("height", centerheight + "px");
        }
    }
}

function toggleContent(id)
{
    var obj = $("#" + id);
    var center = $("#panel-center");
    if(obj.length == 0) {
        return;
    }
    $(".button-active").removeClass("button-active");
    if(obj.is(":visible")) {
        center.hide();
        obj.hide();
        visible_content = null;
        window.location.hash = "#";
    } else {
        if(visible_content) {
            visible_content.hide();
        }
        $("#" + id + "-button").addClass("button-active");
        visible_content = obj
        obj.show();
        center.show();
        window.location.hash = "#" + id;
    }
    resizePanels();
}

function toggleLanguage(mode, mode_complex)
{
    if(mode_complex) {
        mode_complex = mode_complex.replace(/[']/g, '"');
        editor.setOption("mode", $.parseJSON(mode_complex));
    } else {
        editor.setOption("mode", mode);
    }
    editor_mode = mode;
}

function setCookie(name, value, expiration)
{
    document.cookie = name + "=" + value + "; expires=" + expiration + "; path=/";
}

function toggleToolTip(id)
{
    $(".tooltip").hide();
    if(!id) {
        return;
    }
    var tooltip = $("#" + id);
    if(tooltip.length == 0) {
        return;
    }
    var target = $("#" + tooltip.data("target"));
    tooltip.show();
    tooltip.css("top", target.offset().top + "px");
    tooltip.css("left", target.offset().left + target.outerWidth() + "px");
}

$(document).ready(function() {
    var cookie_expiration = "Mon, 1 Jan 2040 08:00:00 UTC"
    $(".button").mouseenter(function() {
        toggleToolTip($(this).attr("id") + "-tooltip");
    });
    $(".button").mouseleave(function() {
        toggleToolTip();
    });
    $("#languages").change(function() {
        var mode = $(this).find(":selected").data("mode");
        if($(this).find(":selected").data("modecomplex")) {
            toggleLanguage(mode, $(this).find(":selected").data("modecomplex"));
        } else {
            toggleLanguage(mode);
        }
        $(".CodeMirror").attr("id", "CodeMirror-mode-" + mode);
    });
    $("#linenumbers_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("lineNumbers", true);
            setCookie("editor_line_numbers", "1", cookie_expiration);
        } else {
            editor.setOption("lineNumbers", false);
            setCookie("editor_line_numbers", "0", cookie_expiration);
        }
    });
    $("#wordwrap_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("lineWrapping", true);
            setCookie("editor_line_wrapping", "1", cookie_expiration);
        } else {
            editor.setOption("lineWrapping", false);
            setCookie("editor_line_wrapping", "0", cookie_expiration);
        }
    });
    $("#smartindent_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("smartIndent", true);
            setCookie("editor_smart_indent", "1", cookie_expiration);
        } else {
            editor.setOption("smartIndent", false);
            setCookie("editor_smart_indent", "0", cookie_expiration);
        }
    });
    $("#matchbrackets_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("matchBrackets", true);
            setCookie("editor_match_brackets", "1", cookie_expiration);
        } else {
            editor.setOption("matchBrackets", false);
            setCookie("editor_match_brackets", "0", cookie_expiration);
        }
    });
    $("#matchtags_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("matchTags", {bothTags: true});
            setCookie("editor_match_tags", "1", cookie_expiration);
        } else {
            editor.setOption("matchTags", false);
            setCookie("editor_match_tags", "0", cookie_expiration);
        }
    });
    $("#highlight_active_line_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("styleActiveLine", true);
            setCookie("editor_highlight_active_line", "1", cookie_expiration);
        } else {
            editor.setOption("styleActiveLine", false);
            setCookie("editor_highlight_active_line", "0", cookie_expiration);
        }
    });
    $("#highlight_occurrences_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("highlightSelectionMatches", {showToken: /\w/});
            setCookie("editor_highlight_occurrences", "1", cookie_expiration);
        } else {
            editor.setOption("highlightSelectionMatches", false);
            setCookie("editor_highlight_occurrences", "0", cookie_expiration);
        }
    });
    $("#vertical_ruler_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("rulers", [{column: editor_vbarpos, className: "editor-ruler"}]);
            setCookie("editor_vertical_ruler", "1", cookie_expiration);
        } else {
            editor.setOption("rulers", false);
            setCookie("editor_vertical_ruler", "0", cookie_expiration);
        }
    });
    $("#folding_checkbox").change(function() {
        if($(this).is(":checked")) {
            editor.setOption("foldGutter", true);
            setCookie("editor_folding", "1", cookie_expiration);
        } else {
            editor.setOption("foldGutter", false);
            setCookie("editor_folding", "0", cookie_expiration);
        }
    });
    $("#theme-selector").change(function() {
        var theme = $(this).find(":selected").val();
        site_theme = theme;
        setCookie("site_theme", theme, "Mon, 1 Jan 2040 08:00:00 UTC");
        location.reload();
    });
    $("#paste-form").submit(function(e) {
        $("#modal-background").show();
        var spinner = $("#modal-spinner");
        spinner.css("margin-top", "-" + spinner.outerHeight()/2 + "px");
        spinner.css("margin-left", "-" + spinner.outerWidth()/2 + "px");
    });
    resizePanels();
    if(window.location.hash) {
        toggleContent(window.location.hash.replace("#", ""));
    }
});

$(window).resize(function() {
    resizePanels();
});

$(window).keypress(function(event) {
    if(ctrl_down && alt_down) {
        var checkbox = false;
        if(event.which == 115 && editor.getValue() != "") {
            $("#paste-form").submit();
        } else if(event.which == 113) {
            checkbox = $("#linenumbers_checkbox");
        } else if(event.which == 119) {
            checkbox = $("#wordwrap_checkbox");
        } else if(event.which == 101) {
            checkbox = $("#smartindent_checkbox");
        } else if(event.which == 97) {
            checkbox = $("#matchbrackets_checkbox");
        } else if(event.which == 122) {
            checkbox = $("#matchtags_checkbox");
        } else if(event.which == 120) {
            checkbox = $("#highlight_active_line_checkbox");
        } else if(event.which == 99) {
            checkbox = $("#highlight_occurrences_checkbox");
        } else if(event.which == 118) {
            checkbox = $("#vertical_ruler_checkbox");
        } else if(event.which == 102) {
            checkbox = $("#folding_checkbox");
        }
        if(checkbox) {
            checkbox.prop("checked", !checkbox.prop("checked"));
            checkbox.trigger("change");
        }
        console.log(event.which)
    }
});

$(document).on("keyup keydown", function(event) {
    ctrl_down = event.ctrlKey
    alt_down = event.altKey
});
