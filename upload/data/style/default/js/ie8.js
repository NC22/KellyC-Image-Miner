if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}

if(!document.getElementsByClassName) {      
    document.getElementsByClassName = function (class_name) {
 
        var elements = document.body.getElementsByTagName("*"),
            length   = elements.length,
            out = [], i;
 
        for (i = 0; i < length; i += 1) {
            if (elements[i].className.indexOf(class_name) !== -1) {
                out.push(elements[i]);
            }       
        }        
        return out;
    };
    Element.prototype.getElementsByClassName = document.getElementsByClassName;
}

if( !window.getComputedStyle) {
    window.getComputedStyle = function(e) {
        return e.currentStyle;
    };
}

(function (window, document) {

  var html = document.documentElement;
  var body = document.body;

  var define = function (object, property, getter) {
    if (typeof object[property] === 'undefined') {
      Object.defineProperty(object, property, { get: getter });
    }
  };

  define(window, 'innerWidth', function () { return html.clientWidth });
  define(window, 'innerHeight', function () { return html.clientHeight });

  define(window, 'scrollY', function () { return window.pageYOffset || html.scrollTop });
  define(window, 'scrollX', function () { return window.pageXOffset || html.scrollLeft });

  define(document, 'width', function () { return Math.max(html.clientWidth, html.scrollWidth, body.scrollWidth) });
  define(document, 'height', function () { return Math.max(html.clientHeight, html.scrollHeight, body.scrollHeight) });

}(window, document));