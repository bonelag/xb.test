var i = require("./828.js");
function r(e, t, n, i, r) {
  var o = s.apply(this, arguments);
  e.addEventListener(n, o, r);
  return {
    destroy: function () {
      e.removeEventListener(n, o, r);
    }
  };
}
function s(e, t, n, r) {
  return function (n) {
    n.delegateTarget = i(n.target, t);
    if (n.delegateTarget) {
      r.call(e, n);
    }
  };
}
module.exports = function (e, t, n, i, s) {
  if (typeof e.addEventListener == "function") {
    return r.apply(null, arguments);
  } else if (typeof n == "function") {
    return r.bind(null, document).apply(null, arguments);
  } else {
    if (typeof e == "string") {
      e = document.querySelectorAll(e);
    }
    return Array.prototype.map.call(e, function (e) {
      return r(e, t, n, i, s);
    });
  }
};