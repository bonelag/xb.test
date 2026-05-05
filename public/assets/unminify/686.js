require.d(exports, {
  default: function () {
    return w;
  }
});
var i = require("./279.js");
var r = i;
var s = require("./370.js");
var o = s;
var a = require("./817.js");
var l = a;
function c(e) {
  try {
    return document.execCommand(e);
  } catch (t) {
    return false;
  }
}
function d(e) {
  var t = l(e);
  c("cut");
  return t;
}
function u(e, t) {
  var n = function (e) {
    var t = document.documentElement.getAttribute("dir") === "rtl";
    var n = document.createElement("textarea");
    n.style.fontSize = "12pt";
    n.style.border = "0";
    n.style.padding = "0";
    n.style.margin = "0";
    n.style.position = "absolute";
    n.style[t ? "right" : "left"] = "-9999px";
    var i = window.pageYOffset || document.documentElement.scrollTop;
    n.style.top = `${i}px`;
    n.setAttribute("readonly", "");
    n.value = e;
    return n;
  }(e);
  t.container.appendChild(n);
  var i = l(n);
  c("copy");
  n.remove();
  return i;
}
function h(e, t = {
  container: document.body
}) {
  var n = "";
  if (typeof e == "string") {
    n = u(e, t);
  } else if (e instanceof HTMLInputElement && !["text", "search", "url", "tel", "password"].includes(e == null ? undefined : e.type)) {
    n = u(e.value, t);
  } else {
    n = l(e);
    c("copy");
  }
  return n;
}
function g(e) {
  g = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function (e) {
    return typeof e;
  } : function (e) {
    if (e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype) {
      return "symbol";
    } else {
      return typeof e;
    }
  };
  return g(e);
}
function p(e = {}) {
  var t = e.action;
  var n = t === undefined ? "copy" : t;
  var i = e.container;
  var r = e.target;
  var s = e.text;
  if (n !== "copy" && n !== "cut") {
    throw new Error("Invalid \"action\" value, use either \"copy\" or \"cut\"");
  }
  if (r !== undefined) {
    if (!r || g(r) !== "object" || r.nodeType !== 1) {
      throw new Error("Invalid \"target\" value, use a valid Element");
    }
    if (n === "copy" && r.hasAttribute("disabled")) {
      throw new Error("Invalid \"target\" attribute. Please use \"readonly\" instead of \"disabled\" attribute");
    }
    if (n === "cut" && (r.hasAttribute("readonly") || r.hasAttribute("disabled"))) {
      throw new Error("Invalid \"target\" attribute. You can't cut text from elements with \"readonly\" or \"disabled\" attributes");
    }
  }
  if (s) {
    return h(s, {
      container: i
    });
  } else if (r) {
    if (n === "cut") {
      return d(r);
    } else {
      return h(r, {
        container: i
      });
    }
  } else {
    return undefined;
  }
}
function m(e) {
  m = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function (e) {
    return typeof e;
  } : function (e) {
    if (e && typeof Symbol == "function" && e.constructor === Symbol && e !== Symbol.prototype) {
      return "symbol";
    } else {
      return typeof e;
    }
  };
  return m(e);
}
function f(e, t) {
  for (var n = 0; n < t.length; n++) {
    var i = t[n];
    i.enumerable = i.enumerable || false;
    i.configurable = true;
    if ("value" in i) {
      i.writable = true;
    }
    Object.defineProperty(e, i.key, i);
  }
}
function _(e, t) {
  _ = Object.setPrototypeOf || function (e, t) {
    e.__proto__ = t;
    return e;
  };
  return _(e, t);
}
function v(e) {
  var t = function () {
    if (typeof Reflect == "undefined" || !Reflect.construct) {
      return false;
    }
    if (Reflect.construct.sham) {
      return false;
    }
    if (typeof Proxy == "function") {
      return true;
    }
    try {
      Date.prototype.toString.call(Reflect.construct(Date, [], function () {}));
      return true;
    } catch (zpt) {
      return false;
    }
  }();
  return function () {
    var n;
    var i = b(e);
    if (t) {
      var r = b(this).constructor;
      n = Reflect.construct(i, arguments, r);
    } else {
      n = i.apply(this, arguments);
    }
    return function (e, t) {
      if (!t || m(t) !== "object" && typeof t != "function") {
        return function (e) {
          if (e === undefined) {
            throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
          }
          return e;
        }(e);
      } else {
        return t;
      }
    }(this, n);
  };
}
function b(e) {
  b = Object.setPrototypeOf ? Object.getPrototypeOf : function (e) {
    return e.__proto__ || Object.getPrototypeOf(e);
  };
  return b(e);
}
function y(e, t) {
  var n = `data-clipboard-${e}`;
  if (t.hasAttribute(n)) {
    return t.getAttribute(n);
  }
}
var x = function (e) {
  (function (e, t) {
    if (typeof t != "function" && t !== null) {
      throw new TypeError("Super expression must either be null or a function");
    }
    e.prototype = Object.create(t && t.prototype, {
      constructor: {
        value: e,
        writable: true,
        configurable: true
      }
    });
    if (t) {
      _(e, t);
    }
  })(s, e);
  var t;
  var n;
  var i;
  var r = v(s);
  function s(e, t) {
    var n;
    (function (e, t) {
      if (!(e instanceof t)) {
        throw new TypeError("Cannot call a class as a function");
      }
    })(this, s);
    (n = r.call(this)).resolveOptions(t);
    n.listenClick(e);
    return n;
  }
  t = s;
  n = [{
    key: "resolveOptions",
    value: function (e = {}) {
      this.action = typeof e.action == "function" ? e.action : this.defaultAction;
      this.target = typeof e.target == "function" ? e.target : this.defaultTarget;
      this.text = typeof e.text == "function" ? e.text : this.defaultText;
      this.container = m(e.container) === "object" ? e.container : document.body;
    }
  }, {
    key: "listenClick",
    value: function (e) {
      var t = this;
      this.listener = o(e, "click", function (e) {
        return t.onClick(e);
      });
    }
  }, {
    key: "onClick",
    value: function (e) {
      var t = e.delegateTarget || e.currentTarget;
      var n = this.action(t) || "copy";
      var i = p({
        action: n,
        container: this.container,
        target: this.target(t),
        text: this.text(t)
      });
      this.emit(i ? "success" : "error", {
        action: n,
        text: i,
        trigger: t,
        clearSelection: function () {
          if (t) {
            t.focus();
          }
          window.getSelection().removeAllRanges();
        }
      });
    }
  }, {
    key: "defaultAction",
    value: function (e) {
      return y("action", e);
    }
  }, {
    key: "defaultTarget",
    value: function (e) {
      var t = y("target", e);
      if (t) {
        return document.querySelector(t);
      }
    }
  }, {
    key: "defaultText",
    value: function (e) {
      return y("text", e);
    }
  }, {
    key: "destroy",
    value: function () {
      this.listener.destroy();
    }
  }];
  i = [{
    key: "copy",
    value: function (e, t = {
      container: document.body
    }) {
      return h(e, t);
    }
  }, {
    key: "cut",
    value: function (e) {
      return d(e);
    }
  }, {
    key: "isSupported",
    value: function (e = ["copy", "cut"]) {
      var t = typeof e == "string" ? [e] : e;
      var n = !!document.queryCommandSupported;
      t.forEach(function (e) {
        n = n && !!document.queryCommandSupported(e);
      });
      return n;
    }
  }];
  if (n) {
    f(t.prototype, n);
  }
  if (i) {
    f(t, i);
  }
  return s;
}(r);
var w = x;