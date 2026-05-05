var i = require("./879.js");
var r = require("./438.js");
module.exports = function (e, t, n) {
  if (!e && !t && !n) {
    throw new Error("Missing required arguments");
  }
  if (!i.string(t)) {
    throw new TypeError("Second argument must be a String");
  }
  if (!i.fn(n)) {
    throw new TypeError("Third argument must be a Function");
  }
  if (i.node(e)) {
    return function (e, t, n) {
      e.addEventListener(t, n);
      return {
        destroy: function () {
          e.removeEventListener(t, n);
        }
      };
    }(e, t, n);
  }
  if (i.nodeList(e)) {
    return function (e, t, n) {
      Array.prototype.forEach.call(e, function (e) {
        e.addEventListener(t, n);
      });
      return {
        destroy: function () {
          Array.prototype.forEach.call(e, function (e) {
            e.removeEventListener(t, n);
          });
        }
      };
    }(e, t, n);
  }
  if (i.string(e)) {
    return function (e, t, n) {
      return r(document.body, e, t, n);
    }(e, t, n);
  }
  throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList");
};