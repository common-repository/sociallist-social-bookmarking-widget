// http://www.quirksmode.org/js/selected.html
function sociallist_get_selection() {
    if (window.getSelection)
        return "" + window.getSelection();
    else if (document.getSelection)
        return "" + document.getSelection();
    else if (document.selection)
        return "" + document.selection.createRange().text;
}

function sociallist_description_link(link, attribute) {
    if (typeof(link.original_link) == "undefined")
        link.original_link = link.href;
    link.href = link.original_link + "&" + attribute + "=" + sociallist_get_selection();
    return false;
}
