function t() {}
t.prototype = {
  on: function (e, t, n) {
    var i = this.e ||= {};
    (i[e] ||= []).push({
      fn: t,
      ctx: n
    });
    return this;
  },
  once: function (e, t, n) {
    var i = this;
    function r() {
      i.off(e, r);
      t.apply(n, arguments);
    }
    r._ = t;
    return this.on(e, r, n);
  },
  emit: function (e) {
    var t = [].slice.call(arguments, 1);
    var n = ((this.e ||= {})[e] || []).slice();
    for (var i = 0, r = n.length; i < r; i++) {
      n[i].fn.apply(n[i].ctx, t);
    }
    return this;
  },
  off: function (e, t) {
    var n = this.e ||= {};
    var i = n[e];
    var r = [];
    if (i && t) {
      for (var s = 0, o = i.length; s < o; s++) {
        if (i[s].fn !== t && i[s].fn._ !== t) {
          r.push(i[s]);
        }
      }
    }
    if (r.length) {
      n[e] = r;
    } else {
      delete n[e];
    }
    return this;
  }
};
module.exports = t;
module.exports.TinyEmitter = t;