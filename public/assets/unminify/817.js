module.exports = function (e) {
  var t;
  if (e.nodeName === "SELECT") {
    e.focus();
    t = e.value;
  } else if (e.nodeName === "INPUT" || e.nodeName === "TEXTAREA") {
    var n = e.hasAttribute("readonly");
    if (!n) {
      e.setAttribute("readonly", "");
    }
    e.select();
    e.setSelectionRange(0, e.value.length);
    if (!n) {
      e.removeAttribute("readonly");
    }
    t = e.value;
  } else {
    if (e.hasAttribute("contenteditable")) {
      e.focus();
    }
    var i = window.getSelection();
    var r = document.createRange();
    r.selectNodeContents(e);
    i.removeAllRanges();
    i.addRange(r);
    t = i.toString();
  }
  return t;
};