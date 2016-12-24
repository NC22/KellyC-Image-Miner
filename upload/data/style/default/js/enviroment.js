function KellyLightEnviroment() {

      if ( arguments.callee._singletonInstance )
        return arguments.callee._singletonInstance;
      arguments.callee._singletonInstance = this;
      
	var handler = this;
	var domReady = false;
    
    var onDOMLoadEvents = new Array();
    var events = new Array();
    
	bindReady(handler, function() {
        domReady = true;
        for (var i = 0; i < onDOMLoadEvents.length; i++) {
            console.log('exec');
            onDOMLoadEvents[i]();
        }
    });
	
    function bindReady(self, functionOnReady) 
    {
        function ready() { 
            if (self.isDOMReady())
                return;
            self.setDOMReady(true);
            functionOnReady();
        }

        if (document.addEventListener) { 
            
            document.addEventListener("DOMContentLoaded", function() {
                ready();
            }, false);
            
        } else if (document.attachEvent) {  

            if (document.documentElement.doScroll && window === window.top) {
                function tryScroll() {
                    if (called)
                        return;
                    if (!document.body)
                        return;
                    try {
                        document.documentElement.doScroll("left");
                        ready();
                    } catch (e) {
                        setTimeout(tryScroll, 0);
                    }
                }
                tryScroll();
            }
            
            document.attachEvent("onreadystatechange", function() {

                if (document.readyState === "complete") {
                    ready();
                }
            });
        }
        
        if (window.addEventListener)
            window.addEventListener('load', ready, false);
        else if (window.attachEvent)
            window.attachEvent('onload', ready);
    }   
    
    this.addEventListner = function(object, event, callback, prefix) {
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
    };
    
    this.setDOMReady = function(state){
        domReady = state;
    };
	
    this.isDOMReady = function(){
        if (domReady) return true;
        else return false;
    };
    
    this.addOnDOMReady = function(handler) {
        onDOMLoadEvents.push(handler);
    };   
}

var envObj = new KellyLightEnviroment();

/* todo move this methods to enviroment */

function getTagParentByChild(child, tag) {
	if (child.tagName == tag) return child;
	while ((child = child.parentElement) && child.tagName != tag) {
	}

	if (!child || child.tagName != tag) return false;

	return child;
}

function toogleVisible(item) {
    if (typeof item == "string") { 
        item = getById(item);
    }
    
    if (!item) return false;
    
    if (item.style.visibility !== 'visible') {
        item.style.visibility = 'visible';
    } else {
        item.style.visibility = 'hidden';
    }
}

 function toogleActiveBlock(id) {
	var element = document.getElementById(id);
	if (!element) return;
	
	if (element.className.indexOf('block-active') !== -1) element.className = element.className.replace('block-active', '');
	else {
		element.className += ' block-active';
		element.className = element.className.trim();
	}	
 }
 
function toogleDisplay(item) {
    if (typeof item == "string") { 
        item = getById(item);
    }
    
    if (!item) return false;
    
    if (item.style.display !== 'block') {
        item.style.display = 'block';
    } else {
        item.style.display = 'none';
    }
}

/* Math */

function rand(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

/* Ajax */

function sendByXmlHttp(script, postData, onload, token) {

    var req = getXmlHttp();

    req.onreadystatechange = function() {

    if (req.readyState != 4 || 
        (req.status != 200 && req.status != 0) || 
        (req.status == 0 && req.responseText.length == 0)) return false

            var response = getJSvalue(req.responseText);
            onload(response);
    }
    
    var tokenPost = '';
    if (token) {
        tokenPost = '&token_data=' + token;
    }
    
    req.open('POST', baseUrl + script, true)  
    req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    req.send(postData + tokenPost);

    return req;
}

function getJSvalue(value) {

    var result = false
    if (typeof value != "string") { 
        console.log('[getJSvalue] Value is not string : ' + value);
        return result;
    }

    try {

    result = window.JSON && window.JSON.parse ? JSON.parse(value) : eval('(' + value + ')')

    } catch (E) {
        console.log('[getJSvalue] Incorect server response : ' + value);
    }
	
    return result;
}

function getXmlHttp() {

    var xmlhttp;

    if (!xmlhttp && typeof XMLHttpRequest != 'undefined')
        xmlhttp = new XMLHttpRequest();

    else {

        try {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {

            try {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (E) {
                xmlhttp = false;
            }

        }

    }
    return xmlhttp;
}

/* DOM Helpers */

function getById(elem) {
    return document.getElementById(elem)
}

function getValById(elem) {
    var element = getById(elem);
    if (!element) return false;
    
    return element.value;
}

function getBody() {
    return document.getElementsByTagName('body')[0]
}

function getScrollTop() {
    return (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop
}

function addHiddenInput(name, value, to) {
    var element = document.createElement('input')

    element.type = 'hidden'
    element.name = name
    element.value = value

    to.appendChild(element)
}

function getParent(elem, type){ 
    var parent = elem.parentNode

    if (parent && parent.tagName != type) parent = GetParentForm(parent)

    return parent;
}

function getByClass(className, tag) {
    var LinkList   = document.getElementsByTagName(tag)
    var foundList = []

    for (var i=0; i<=LinkList.length-1; ++i) 
            if (LinkList[i].className == className) foundList[foundList.length] = LinkList[i]

    return foundList
}

function _getTime() {
	var currentTime = new Date();
	var hours = currentTime.getHours();
	var minutes = currentTime.getMinutes();
	
	if (minutes < 10){
		minutes = "0" + minutes;
	}
	return hours + ":" + minutes;
}
