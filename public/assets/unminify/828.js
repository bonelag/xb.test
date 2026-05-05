if (typeof Element != "undefined" && !Element.prototype.matches) {
  var t = Element.prototype;
  t.matches = t.matchesSelector || t.mozMatchesSelector || t.msMatchesSelector || t.oMatchesSelector || t.webkitMatchesSelector;
}
module.exports = function (e, t) {
  while (e && e.nodeType !== 9) {
    if (typeof e.matches == "function" && e.matches(t)) {
      return e;
    }
    e = e.parentNode;
  }
};