editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
    tabMode: "indent",
    theme: "{{@site_theme.editor_theme}}",
    mode: {{@editor_mode | raw}},
    matchBrackets: {{@editor_match_brackets}},
    lineNumbers: {{@editor_line_numbers}},
    lineWrapping: {{@editor_line_wrapping}},
    smartIndent: {{@editor_smart_indent}},
    tabSize: {{@editor_tab_size}},
    cursorBlinkRate: {{@editor_cursor_blinkrate}},
    readOnly: {{@editor_readonly}},
    styleActiveLine: {{@editor_highlight_active_line}},
    indentUnit: 4,
    autofocus: true,
<check if="{{ @editor_match_tags == 'true' }}">
    <true>
    matchTags: {bothTags: true},
    </true>
    <false>
    matchTags: false,
    </false>
</check>
<check if="{{ @editor_highlight_occurrences == 'true' }}">
    <true>
    highlightSelectionMatches: {showToken: /\w/},
    </true>
    <false>
    highlightSelectionMatches: false,
    </false>
</check>
<check if="{{ @editor_vertical_ruler == 'true' }}">
    <true>
    rulers: [{column: {{@editor_vbarpos}}, className: "editor-ruler"}],
    </true>
    <false>
    rulers: false,
    </false>
</check>
    foldGutter: {{@editor_folding}},
    gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
});
$(".CodeMirror").css("font-size", "{{@editor_font_size}}px");
editor.refresh();
