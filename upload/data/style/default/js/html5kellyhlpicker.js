/**
 * @category  html5 widgets
 * @package   Kelly
 * @author    Rubchuk Vladimir <torrenttvi@gmail.com>
 * @copyright 2016 Rubchuk Vladimir
 * @license   GPLv3
 * @version   0.71
 *
 * Usage example :
 *
 *   new KellyHlPicker({place : 'color-picker-hsl'});
 *
 **/

/**
 * Create color picker
 * @param {Array} cfg
 * @returns {KellyHlPicker}
 */

function KellyHlPicker(cfg) {
    var PI = Math.PI;

    var handler = this;
    var debug = false; // show some debug information

    var canvas = false;
    var canvasPadding = 4;

    var ctx = false;
    var ctxBuffer = false;

    var input = false;

    var chunks = 12;  // num uf chunks in one row    
    var size = 250;   // size of palette     
    var grid = false; // draw grid for separate chunks

    var chunkPadding = 2;
    var s = 1;

    var cursor = {x: -1, y: -1};

    var selectedMax = 3;
    var selected = []; // list of selected dots

    var cursorSize = 105; // percentage

    var events = new Array();
    var userEvents = new Array();

    var cursorAnimReady = true;

    var outline = false;
    var style = 'quad';

    function constructor(cfg) {
        var criticalError = '', placeName = '';

        if (cfg.place && typeof cfg.place !== 'object') {
            placeName = cfg.place;
            cfg.place = document.getElementById(cfg.place);
        }
        if (cfg.place) {
            place = cfg.place;
        } else
            criticalError += '| "place" (' + placeName + ') not not found';

        if (!initCanvas()) {
            criticalError += ' | cant init canvas context';
        }

        if (criticalError) {
            if (typeof console !== 'undefined')
                console.log('KellyColorPickerHsl : ' + criticalError);
            return;
        }

        if (cfg.size)
            size = cfg.size;

        if (cfg.style && (cfg.style == 'arc' || cfg.style == 'quad'))
            style = cfg.style;

        if (cfg.chunks)
            chunks = parseInt(cfg.chunks);
        if (cfg.cursorSize)
            cursorSize = parseInt(cfg.cursorSize);
        if (cfg.chunkPadding)
            chunkPadding = parseInt(cfg.chunkPadding);

        if (cfg.outline)
            outline = true;
        else
            outline = false;

        if (cfg.grid)
            grid = true;
        else
            grid = false;

        if (cfg.input && typeof cfg.input === 'string') {

            input = [];
            addInput(cfg.input);

        } else if (cfg.input) {

            input = [];
            for (var i = 0; i < cfg.input.length; i++) {
                addInput(cfg.input[i]);
            }

        } else { // work without input, max selection by selectedMax
            for (var i = 0; i < selectedMax; i++) {
                selected[i] = false;
            }
        }

        if (cfg.userEvents)
            userEvents = cfg.userEvents;

        enableEvents();

        updateSize();
        updateView();
        updateInput(false);
    }

    function addInput(inputMix) {

        var sinput = false;

        if (typeof inputMix === 'object') {
            sinput = inputMix;

        } else {
            sinput = document.getElementById(inputMix);
        }

        if (!sinput)
            console.log('input field not found by id :: ' + inputMix);
        else {

            var inputEdit = function (e) {
                var e = e || window.event;
                if (!e.target) {
                    e.target = e.srcElement;
                }

                handler.setColorByInput(e.target, this);
            };

            addEventListner(sinput, "click", inputEdit, 'input_edit_');
            addEventListner(sinput, "change", inputEdit, 'input_edit_');
            addEventListner(sinput, "keyup", inputEdit, 'input_edit_');

            var key = input.length;

            selected[key] = false;
            input[key] = sinput;

            selectedMax = input.length;
            handler.setColorByInput(sinput);
        }
    }

    function updateSize() {
        ctxBuffer = false;
        // size = Math.floor(size / chunks) * chunks; // fix size according to chunks size

        if (place.tagName != 'CANVAS') {
            place.style.width = (canvasPadding * 2) + size + 'px';
            place.style.height = (canvasPadding * 2) + size + 'px';
        }

        canvas.width = size + canvasPadding * 2;
        canvas.height = size + canvasPadding * 2;
    }

    function initCanvas() {
        if (!place)
            return false;
        if (place.tagName != 'CANVAS') {
            canvas = document.createElement('CANVAS');
            place.appendChild(canvas);
        } else {
            canvas = place;
        }

        // code for IE browsers
        if (typeof window.G_vmlCanvasManager != 'undefined') {
            canvas = window.G_vmlCanvasManager.initElement(canvas);
            canvasHelper = window.G_vmlCanvasManager.initElement(canvasHelper);
        }

        if (!!(canvas.getContext && canvas.getContext('2d'))) {
            ctx = canvas.getContext("2d");
            return true;
        } else
            return false;
    }

    // local grid coordinates without out paddings    
    function dotToHl(dot) {
        return {
            h: 1 - Math.abs(size - dot.x) / size,
            l: Math.abs(size - dot.y) / size
        };
    }

    // local grid coordinates without out paddings    
    function hlToDot(hl) {
        if (hl.h > 1)
            hl.h = 1;
        if (hl.l > 1)
            hl.l = 1;
        if (hl.h < 0)
            hl.h = 0;
        if (hl.l < 0)
            hl.l = 0;
                
        var dot = {
            x: size - (size - (hl.h * size)),
            y: size - (hl.l * size),
        };

        var step = size / chunks;

        dot.x = Math.round(dot.x / step) * step;
        dot.y = Math.round(dot.y / step) * step;

        if (dot.x < 0)
            dot.x = 0;
        if (dot.y < 0)
            dot.y = 0;

        if (dot.x / step >= chunks)
            dot.x = (chunks - 1) * step;
        if (dot.y / step >= chunks)
            dot.y = (chunks - 1) * step;
        
        if (debug) {
            console.log('hl to dot :: ');
            console.log(hl);
            console.log(dot);
        }
        return dot;
    }

    function roundRect(x, y, rsize, r) {
        var LineH = rsize - r;

        ctx.moveTo(x + r, y);
        ctx.lineTo(x + LineH, y);
        ctx.quadraticCurveTo(x + LineH + r, y, x + LineH + r, y + r);
        ctx.lineTo(x + LineH + r, y + LineH);
        ctx.quadraticCurveTo(x + LineH + r, y + LineH + r, x + LineH, y + LineH + r);
        ctx.lineTo(x + r, y + LineH + r);
        ctx.quadraticCurveTo(x, y + LineH + r, x, y + LineH);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);

    }

    function getContrastColor(hsl) {

        var color = '#000'; // any dark color
        if (hsl.h > 0.50 && hsl.h < 0.80)
            color = '#FFF';

        if (hsl.l < 0.35) {
            color = '#FFF'; // any bright color
        }

        if (hsl.l > 0.75) {
            color = '#000';
        }

        return color;
    }

    function updateView() {
        if (!ctx)
            return false;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (ctxBuffer) {
            ctx.putImageData(ctxBuffer, 0, 0);
        } else {

            var step = size / chunks;
            for (var y = 0; y < chunks; y++) {
                if (grid) {
                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(0, 0, 0, 1)';
                    ctx.moveTo(0, y * step);
                    ctx.lineTo(size, y * step);
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    ctx.closePath();
                }
                for (var x = 0; x < chunks; x++) {

                    var dot = {x: x * step, y: y * step};


                    var hl = dotToHl(dot);

                    dot.x += canvasPadding;
                    dot.y += canvasPadding;

                    var targetRgb = hslToRgb(hl.h, s, hl.l);
                    //console.log(hl.h + ' | ' + hl.l + dot.x + ' | ' + dot.y + ' | ' + targetRgb.r + ' | ' + targetRgb.g + ' | ' + targetRgb.b);
                    ctx.beginPath();

                    if (style !== 'quad') {
                        var arcH = step / 2;
                        ctx.arc(dot.x + arcH, dot.y + arcH, arcH - chunkPadding, 0, 2 * PI);
                    } else {
                        dot.x += chunkPadding;
                        dot.y += chunkPadding;

                        if (chunkPadding > 1) {
                            roundRect(dot.x, dot.y, step - chunkPadding * 2, 4);
                        } else {
                            ctx.rect(dot.x, dot.y, step - chunkPadding * 2, step - chunkPadding * 2);
                        }
                        //ctx.rect(dot.x, dot.y, step - chunkPadding * 2, step - chunkPadding * 2);

                    }
                    // if (hl.l > 0.5) {

                    // var strokeRgb = hslToRgb(hl.h, 0.5, 0.5); -- contrast colors

                    if (outline) {
                        var strokeRgb = hslToRgb(1, 1, 0);

                        ctx.strokeStyle = 'rgba(' + strokeRgb.r + ',' + strokeRgb.g + ',' + strokeRgb.b + ', 1)';
                        ctx.lineWidth = 2;
                        ctx.stroke();
                    }

                    ctx.fillStyle = 'rgba(' + targetRgb.r + ',' + targetRgb.g + ',' + targetRgb.b + ', 1)';
                    ctx.fill();


                    ctx.closePath();
                }
            }

            if (grid) {
                for (var x = 1; x < chunks; x++) {

                    ctx.beginPath();
                    ctx.strokeStyle = 'rgba(0, 0, 0, 1)';
                    ctx.moveTo(canvasPadding + x * step, 0);
                    ctx.lineTo(canvasPadding + x * step, size);
                    ctx.stroke();
                    ctx.closePath();
                }
            }

            ctxBuffer = ctx.getImageData(0, 0, size + canvasPadding, size + canvasPadding);
        }

        if (selected.length) {
            for (var i = 0; i < selected.length; i++) {
                if (!selected[i])
                    continue;

                var step = size / chunks;

                var selectP = 60;
                ctx.strokeStyle = getContrastColor(selected[i]);

                var selector = (step / 100) * selectP;
                var sSt = (step - selector) / 2;
                ctx.beginPath();

                if (style !== 'quad') {
                    var arcH = step / 2;
                    ctx.arc(canvasPadding + selected[i].x + arcH, canvasPadding + selected[i].y + arcH, (selector / 2) - 1, 0, 2 * PI);
                } else {
                    roundRect(canvasPadding + selected[i].x + sSt, canvasPadding + selected[i].y + sSt, selector, 4);
                    // ctx.rect(selected[i].x + sSt, selected[i].y + sSt, selector, selector);
                }

                //ctx.rect(selected[i].x + sSt, selected[i].y + sSt, selector, selector);
                ctx.lineWidth = 1;
                ctx.stroke();

                //console.log('select' + newDot.x + ' | ' + newDot.y);   
                ctx.closePath();
            }
        }

        if (cursor.x != -1 && cursor.y != -1) {

            var step = size / chunks;
            var selector = (step / 100) * cursorSize;
            var sSt = (step - selector) / 2;

            ctx.beginPath();
            var cX = Math.floor((cursor.x - canvasPadding) / step) * step;
            var cY = Math.floor((cursor.y - canvasPadding) / step) * step;
            
            if (cX < 0)
                cX = 0;
            if (cY < 0)
                cY = 0;
            if (cX / step >= chunks)
                cX = (chunks - 1) * step;
            if (cY / step >= chunks)
                cY = (chunks - 1) * step;

            if (style !== 'quad') {
                var arcH = step / 2;
                ctx.arc(canvasPadding + cX + arcH, canvasPadding + cY + arcH, (selector / 2), 0, 2 * PI);
                ctx.lineWidth = 2;
            } else {
                roundRect(canvasPadding + cX + sSt, canvasPadding + cY + sSt, selector, 4);
                // ctx.rect(cX + sSt, cY + sSt, selector, selector);
                ctx.lineWidth = 1;
            }

            //ctx.rect(cX + sSt, cY + sSt, selector, selector);

            if (dotToHl({x: cX, y: cY}).l < 0.3 && cursorSize < 100)
                ctx.strokeStyle = 'rgba(255, 255, 255, 1)';
            else
                ctx.strokeStyle = 'rgba(0, 0, 0, 1)';
            ctx.stroke();

            ctx.closePath();
        }
    }

    // check is palete dot already selected

    function isSelected(dot) {
        for (var i = 0; i < selected.length; i++) {
            if (selected[i] && selected[i].x == dot.x && selected[i].y == dot.y)
                return i;
        }

        return false;
    }

    function getFreeSelectIndex(dot) {
        for (var i = 0; i < selected.length; i++) {
            if (selected[i] == false)
                return i;
        }

        return false;
    }

    function getInputIndex(inputObj) {
        for (var i = 0; i < input.length; i++) {
            if (input[i] == inputObj)
                return i;
        }

        return false;
    }

    /* called when new color was setted (hl variable was changed) */
    // manualEnter - selected input;

    function updateInput(manualEnter) {
        if (!input)
            return;

        for (var i = 0; i < input.length; i++) {
            if (userEvents["updateinput"]) {
                var callback = userEvents["updateinput"];
                if (callback(handler, input[i], manualEnter) === false) // prevent native event if false
                    continue;
            }

            if (!selected[i]) {
                input[i].value = '';
                input[i].style.background = '#fff';
                continue;
            }

            var rgb = hslToRgb(selected[i].h, s, selected[i].l);
            var rgbStyle = 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')';

            if (manualEnter === false)
                input[i].value = rgbToHex(rgb);

            // var hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);

            input[i].style.color = getContrastColor(selected[i]);
            input[i].style.background = rgbStyle;
        }
    }

    function selectColorByDot(newDot, inputObj) {
        var step = size / chunks;

        newDot.x = Math.floor(newDot.x / step) * step;
        newDot.y = Math.floor(newDot.y / step) * step;
            
        if (newDot.x < 0)
            newDot.x = 0;
        if (newDot.y < 0)
            newDot.y = 0;
        if (newDot.x / step >= chunks)
            newDot.x = (chunks - 1) * step;
        if (newDot.y / step >= chunks)
            newDot.y = (chunks - 1) * step;
            
        //newDot.x -= canvasPadding;
        //newDot.y -= canvasPadding;

        var selectedIndex = isSelected(newDot);
        if (selectedIndex !== false) {

            //selected.splice(selectedIndex, 1);
            selected[selectedIndex] = false;
        } else {

            // if (selected.length + 1 > selectedMax) return;

            if (inputObj) {
                var key = getInputIndex(inputObj);
            } else {
                var key = getFreeSelectIndex();
            }

            if (key === false)
                return;

            selected[key] = newDot;
            var hl = dotToHl(newDot);
            selected[key].h = hl.h;
            selected[key].l = hl.l;

            if (debug)
                console.log(selected[key]);
        }

        updateView();
        updateInput(inputObj);
    }

    // temp events until wait mouse click or touch
    function enableEvents() {
        addEventListner(canvas, "mousedown", function (e) {
            handler.mouseDownEvent(e);
        }, 'wait_action_');
        addEventListner(canvas, "touchstart", function (e) {
            handler.mouseDownEvent(e);
        }, 'wait_action_');
        addEventListner(canvas, "mouseout", function (e) {
            handler.mouseOutEvent(e);
        }, 'wait_action_');
        //addEventListner(window, "touchmove", function (e) {
        //   handler.touchMoveEvent(e);
        //}, 'wait_action_');
        addEventListner(canvas, "mousemove", function (e) {
            handler.mouseMoveRest(e);
        }, 'wait_action_');
    }

    function disableEvents() {
        removeEventListener(canvas, "mousedown", 'wait_action_');
        removeEventListener(canvas, "touchstart", 'wait_action_');
        removeEventListener(canvas, "mouseout", 'wait_action_');
        // removeEventListener(window, "touchmove", 'wait_action_');
        removeEventListener(canvas, "mousemove", 'wait_action_');
    }

    // prefix - for multiple event functions for one object
    function addEventListner(object, event, callback, prefix) {
        if (typeof object !== 'object') {
            object = document.getElementById(object);
        }

        if (!object)
            return false;
        if (!prefix)
            prefix = '';

        events[prefix + event] = callback;

        if (!object.addEventListener) {
            object.attachEvent('on' + event, events[prefix + event]);
        } else {
            object.addEventListener(event, events[prefix + event]);
        }

        return true;
    }

    function removeEventListener(object, event, prefix) {
        if (typeof object !== 'object') {
            object = document.getElementById(object);
        }

        // console.log('remove :  : ' + Object.keys(events).length);
        if (!object)
            return false;
        if (!prefix)
            prefix = '';

        if (!events[prefix + event])
            return false;

        if (!object.removeEventListener) {
            object.detachEvent('on' + event, events[prefix + event]);
        } else {
            object.removeEventListener(event, events[prefix + event]);
        }

        events[prefix + event] = null;
        return true;
    }

    function getEventDot(e) {
        e = e || window.event;
        var x, y;
        var scrollX = document.body.scrollLeft + document.documentElement.scrollLeft;
        var scrollY = document.body.scrollTop + document.documentElement.scrollTop;

        if (e.touches) {
            x = e.touches[0].clientX + scrollX;
            y = e.touches[0].clientY + scrollY;
        } else {
            // e.pageX e.pageY e.x e.y bad for cross-browser
            x = e.clientX + scrollX;
            y = e.clientY + scrollY;
        }

        var rect = canvas.getBoundingClientRect();

        x -= rect.left + scrollX;
        y -= rect.top + scrollY;

        // x += canvasPadding;
        // y += canvasPadding;

        return {x: x, y: y};
    }

    // [converters]
    // hsl converters described here : http://axonflux.com/handy-rgb-to-hsl-and-rgb-to-hsv-color-model-c

    function hslToRgb(h, s, l) {
        var r, g, b;

        if (s == 0) {
            r = g = b = l; // achromatic
        } else {
            var hue2rgb = function hue2rgb(p, q, t) {
                if (t < 0)
                    t += 1;
                if (t > 1)
                    t -= 1;
                if (t < 1 / 6)
                    return p + (q - p) * 6 * t;
                if (t < 1 / 2)
                    return q;
                if (t < 2 / 3)
                    return p + (q - p) * (2 / 3 - t) * 6;
                return p;
            }

            var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            var p = 2 * l - q;
            r = hue2rgb(p, q, h + 1 / 3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1 / 3);
        }

        return {r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255)};
    }

    function rgbToHsl(r, g, b) {
        r /= 255, g /= 255, b /= 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var h, s, l = (max + min) / 2;

        if (max == min) {
            h = s = 0; // achromatic
        } else {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r:
                    h = (g - b) / d + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4;
                    break;
            }
            h /= 6;
        }

        return {h: h, s: s, l: l};
    }

    function rgbToHsv(r, g, b) {
        if (r && g === undefined && b === undefined) {
            g = r.g, b = r.b, r = r.r;
        }

        r = r / 255, g = g / 255, b = b / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b);
        var h, s, v = max;

        var d = max - min;
        s = max == 0 ? 0 : d / max;

        if (max == min) {
            h = 0; // achromatic
        } else {
            switch (max) {
                case r:
                    h = (g - b) / d + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4;
                    break;
            }
            h /= 6;
        }

        return {h: h, s: s, v: v};
    }

    function hexToRgb(hex) {
        var dec = parseInt(hex.charAt(0) == '#' ? hex.slice(1) : hex, 16);
        return {r: dec >> 16, g: dec >> 8 & 255, b: dec & 255};
    }

    function rgbToHex(color) {
        var componentToHex = function (c) {
            var hex = c.toString(16);
            return hex.length === 1 ? "0" + hex : hex;
        };

        return "#" + componentToHex(color.r) + componentToHex(color.g) + componentToHex(color.b);
    }

    // todo eval have issues with min.js scripts, find another way

    function writeOption(key, value) {
        if (typeof value == 'string')
            value = '"' + value + '"';
        eval(key + " = " + value);
    }

    function readOption(key) {
        eval("var getValue = " + key);
        return getValue;
    }

    function readColorData(cString, falseOnFail) {
        var alpha = 1;
        var h = false;

        cString = cString.trim(cString);
        if (cString.length <= 7) { // hex color
            if (cString.charAt(0) == '#')
                cString = cString.slice(1);

            if (cString.length == 3)
                h = cString + cString;
            else if (cString.length == 6)
                h = cString;

            //if (h && !h.match(/^#([0-9A-F]){3}$|^#([0-9A-F]){6}$/img)) h = false;			

        } else if (cString.substring(0, 3) == 'rgb') {
            var rgba = cString.split(",");

            if (rgba.length >= 3 && rgba.length <= 4) {
                rgba[0] = rgba[0].replace("rgba(", "");
                rgba[0] = rgba[0].replace("rgb(", "");

                var rgb = {r: parseInt(rgba[0]), g: parseInt(rgba[1]), b: parseInt(rgba[2])};

                if (rgb.r <= 255 && rgb.g <= 255 && rgb.b <= 255) {

                    h = rgbToHex(rgb);

                    if (rgba.length == 4) {
                        alpha = parseFloat(rgba[3]);
                        if (!alpha || alpha < 0)
                            alpha = 0;
                        if (alpha > 1)
                            alpha = 1;
                    }
                }
            }
        }

        if (h === false && falseOnFail)
            return false;
        if (h === false)
            h = '000000';

        if (h.charAt(0) != '#')
            h = '#' + h;
        return {h: h, a: alpha};
    }

    this.addUserEvent = function (event, callback) {
        userEvents[event] = callback;
        return true;
    };

    this.removeUserEvent = function (event) {
        if (!userEvents[event])
            return false;
        userEvents[event] = null;
        return true;
    };

    this.getSelected = function (index) {
        if (index === false)
            return selected;

        // todo check select by input
        if (typeof index === 'object' && input) {
            for (var i = 0; i < input.length; i++) {
                var sinput = false;
                if (index === input[i]) {
                    return selected[i];
                }
            }

            return false;
        }

        return selected[index];
    };

    this.getSelectedHex = function (index) {
        var selected = handler.getSelected(index);
        if (!selected)
            return false;

        return rgbToHex(hslToRgb(selected.h, s, selected.l));
    };

    this.getSelectedHsv = function () {
        var selected = handler.getSelected(index);
        if (!selected)
            return false;

        var rgb = hslToRgb(selected.h, s, selected.l);
        return rgbToHsv(rgb.r, rgb.g, rgb.b);
    };

    this.setS = function (newS, add) {
        if (add)
            s += newS;
        else
            s = newS;

        if (s > 1)
            s = 1;
        if (s < 0)
            s = 0;

        ctxBuffer = false;

        updateView();
        updateInput(false);
    };

    this.getS = function (asPrecentage) {
        if (asPrecentage)
            return Math.round(s * 100);
        return s;
    }

    this.setColorByInput = function (inputObject) {
        if (!inputObject)
            return false;

        var inputHex = inputObject.value;

        if (inputHex !== false) {

            if (!inputHex || !inputHex.length)
                return;

            var colorData = readColorData(inputHex, true);

            if (!colorData)
                return;

            var rgb = hexToRgb(colorData.h);
            var hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);

            //console.log(hsl);
            if (hsl.s != s)
                hsl.s = s; // constant S
            var newDot = hlToDot(hsl);
            //console.log(newDot);

            var selectedIndex = isSelected(newDot);
            if (selectedIndex !== false) {
                selected[selectedIndex] = false;
            }

            selectColorByDot(newDot, inputObject);
        }

        return false;
    };

    this.mouseMoveRest = function (e) {
        if (!cursorAnimReady) {
            return;
        }

        cursorAnimReady = false;
        cursor = getEventDot(e);

        updateView();
        requestAnimationFrame(function () {
            cursorAnimReady = true;
        });
    };

    this.mouseDownEvent = function (event) {
        event.preventDefault();

        var newDot = getEventDot(event);
        newDot.x -= canvasPadding;
        newDot.y -= canvasPadding;

        selectColorByDot(newDot, false);
    };

    this.mouseOutEvent = function (event) {
        event.preventDefault();
        cursor = {x: -1, y: -1};

        updateView();
    };

    // set object option by key
    //
    // if in future will be need more deep option set, we can use subobjects or solutions like
    // http://stackoverflow.com/questions/13719593/how-to-set-object-property-of-object-property-of-given-its-string-name-in-ja
    // 
    // todo set input \ selectedMax (need addition actions to recreate input \ selected arrays)

    this.setOption = function (key, value, redraw) {
        if (!key)
            return false;

        if (debug)
            console.log('set key ' + key + ' new value ' + value);

        if (key == 'chunkPadding') {
            if (value < -1)
                value = -1; // -1 to prevent whitespaces between chunks 
        }

        if (key == 'chunks' || key == 'size') {

            for (var i = 0; i < selectedMax; i++) {
                selected[i] = false;
            }

            if (value < 6)
                value = 6;
            if (key == 'size' && value < 20)
                value = 20;
        }

        writeOption(key, value);
        updateSize();

        if (redraw) {
            ctxBuffer = false;

            updateView();
            updateInput(false);
        }

        return true;
    }

    this.getOption = function (key) {
        return readOption(key);
    }

    constructor(cfg);
}

