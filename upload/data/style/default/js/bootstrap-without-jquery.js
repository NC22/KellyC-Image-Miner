/*!
 * BootstrapNoJQuery v1.0 (c) NC22
 * tested on Bootstrap version 2-3
 */


function BootstrapNoJQuery() {
	
	var dropdowns, tabs, accords, alerts;
	
	var handler = this;
	var domReady = false;
	
	bindReady(this, function() {
        domReady = true;
		handler.setEvents();
    });
	
    // Get the "hidden" height of a collapsed element
    function getHiddenHeight(el) {
        var children = el.children;
        var height = 0;
        for (var i = 0, len = children.length, child; i < len; i++) {
            child = children[i];
            height += Math.max(child['clientHeight'], child['offsetHeight'], child['scrollHeight']);
        }
        return height;
    }

    
	function getById(id) {
        return document.getElementById(id);
    };
	
    function getElementByMix(str) {
    
        if (!str) return false;
        
        var type = '.';
        if (str.indexOf('#') > -1) var type = '#';
        
        var input = str.split(type)[1];
        if (!input) return false;
        
        var elements;
        
        if (type == '.') { 
        
            elements = document.getElementsByClassName(input);
            if (elements.length < 1) return false;
            else return elements[0];
            
        } else if (type == '#') return getById(input);
        else return false;    
    }
	
	function getTargetAccordion(item) {
		
		var element = getElementByMix(item.href);
        if (!element) {
            var element = getElementByMix(item.getAttribute('data-target')) // for single accordions prob.
			
            if (!element) return false;			
        } 
		
		return element;
	}
	
	function constructor(){
		
		// querySelectorAll support for older IE
		// Source: http://weblogs.asp.net/bleroy/archive/2009/08/31/queryselectorall-on-old-ie-versions-something-that-doesn-t-work.aspx
		if (!document.querySelectorAll) {
			document.querySelectorAll = function(selector) {
				var style = document.styleSheets[0] || document.createStyleSheet();
				style.addRule(selector, "foo:bar");
				var all = document.all, resultSet = [];
				for (var i = 0, l = all.length; i < l; i++) {
					if (all[i].currentStyle.foo === "bar") {
						resultSet[resultSet.length] = all[i];
					}
				}
				style.removeRule(0);
				return resultSet;
			};
		}	


	}
	
    function setBlockVisible(item, state, style) {
        
        if (!style) {
            style = 'block'; // inline
        }
        
        if (typeof item !== 'object') {
            item = this.getById(item);
        }
        
        if (!item) return false;
        
        if (state === null || typeof state === 'undefined') {

            if (item.style.display === style)
                item.style.display = 'none';
            else
                item.style.display = style;

            return true;
        }

        var styleText = style;

        if (state === false)
            styleText = 'none';

        item.style.display = styleText;
        return true;
    }
	
    function getClientW()
    {
        return document.compatMode === 'CSS1Compat' && 
               document.documentElement.clientWidth;
    }
   
    function getClientH()
    {
        return document.compatMode === 'CSS1Compat' && 
               document.documentElement.clientHeight;
    }
	
	function closeAccordion(target) { // tested only on nav menu
		var element = getTargetAccordion(target);
		if (!element) return false;
        
        if (element.className.indexOf('in') > -1) {
            element.className = element.className.replace('in', '');
            element.setAttribute("style", "height: 0px");
        } 
		
		return true;
	}
	
    function getScrollTop(){
        return (document.documentElement && 
                document.documentElement.scrollTop) || 
                document.body.scrollTop;
    };
	
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
     
    this.setDOMReady = function(state){
        domReady = state;
    };
	
    this.isDOMReady = function(){
        if (domReady) return true;
        else return false;
    };
	
    // Show a dropdown menu
    // Source: https://github.com/tagawa/bootstrap-without-jquery (deprecated rep)
    this.doDropdown = function(event) {
        event = event || window.event;
        var evTarget = event.currentTarget || event.srcElement;
        var target = evTarget.parentElement;
        var className = (' ' + target.className + ' ');
        
        if (className.indexOf(' ' + 'open' + ' ') > -1) {
            // Hide the menu
            className = className.replace(' open ', ' ');
            target.className = className;
        } else {
            // Show the menu
            target.className += ' open ';
        }
        return false;
    }
    
    this.toogleAccordion = function(event) {
        event = event || window.event;
        var target = event.currentTarget || event.srcElement;
        
		var element = getTargetAccordion(target);
		if (!element) return false;
        
        if (element.className.indexOf('in') > -1) {
            element.className = element.className.replace('in', '');
            element.setAttribute("style", "height: 0px");
        } else {
            element.className += ' in';
            element.setAttribute("style", "height: auto");
        }
        
        var accordionGroup = target.getAttribute('data-parent')        
        if (!accordionGroup) return false; // so, this is single accord
            
        accordionGroup = accordionGroup.split("#")[1]

    	var accordions = document.getElementsByClassName('accordion-group')

        for (var i=0; i<=accordions.length-1; ++i){ 
        
            var aBody = accordions[i].getElementsByClassName('accordion-body')
            var aGroup = accordions[i].getElementsByClassName('accordion-toggle')
            
            if (!aBody.length || aBody[0].id !== element.id ) continue;
            if (!aGroup.length || aGroup[0].getAttribute('data-parent') != accordionGroup ) continue;
            
            aBody[0].className = aBody[0].className.replace('in', '');
            aBody[0].setAttribute("style", "height: 0px");
        }	    
        return false;
    }
    
    this.toogleTab = function(event) {

        event = event || window.event;
        var target = event.currentTarget || event.srcElement;
        var tabButton = target.parentElement
        var buttons = tabButton.parentElement.getElementsByTagName('li')
        var element = getElementByMix(target.href);
        
        if (tabButton.className.indexOf('active') > -1) {
            return false; 
        }
        
        if (element.className.indexOf('active') > -1) {
            element.className = element.className.replace('active', '');
        } else {
            tabButton.className += 'active';
            element.className += ' active';
        }
        
        for (var i=0; i<=buttons.length-1; ++i){ 
            if (buttons[i] != tabButton) {
                buttons[i].className = buttons[i].className.replace('active', '');
            }
        } 
        
        var tabGroup = element.parentElement        
        if (!tabGroup) return false;

    	var divList   = document.getElementsByTagName('DIV')

        for (var i=0; i<=divList.length-1; ++i){ 
            if (divList[i].className.indexOf('tab-pane')  > -1 && divList[i].id !== element.id) {
                divList[i].className = divList[i].className.replace('active', '');
            }
        }	    
        return false;
    }
    
    // Close a dropdown menu
    this.closeDropdown = function(event) {

        event = event || window.event;
        var evTarget = event.currentTarget || event.srcElement;
        var target = evTarget.parentElement;
        
        setTimeout(function(){
            target.className = (' ' + target.className + ' ').replace(' open ', ' ')
        }, 300);

        return false;
    }

    // Close an alert box by removing it from the DOM
    // Source: https://github.com/tagawa/bootstrap-without-jquery (deprecated rep)
    this.closeAlert = function(event) {
        event = event || window.event;
        var evTarget = event.currentTarget || event.srcElement;
        var alertBox = evTarget.parentElement;
        
        alertBox.parentElement.removeChild(alertBox);
        return false;
    }
	
	this.setEvents = function() {

		// Set event listeners for dropdown menus
		dropdowns = document.querySelectorAll('[data-toggle=dropdown]');
		for (var i = 0, dropdown, len = dropdowns.length; i < len; i++) {
			dropdown = dropdowns[i];
			dropdown.setAttribute('tabindex', '0'); // Fix to make onblur work in Chrome
			dropdown.onclick = handler.doDropdown;
			dropdown.onblur = handler.closeDropdown;
		}

		// Set event listeners for alert tabs
		tabs = document.querySelectorAll('[data-toggle=tab]');
		for (var i = 0, len = tabs.length; i < len; i++) {
			tabs[i].onclick = handler.toogleTab;
		}

		// Set event listeners for alert collapse
		accords = document.querySelectorAll('[data-toggle=collapse]');
		for (var i = 0, len = accords.length; i < len; i++) {
			
			accords[i].onclick = handler.toogleAccordion;
		}

		// Set event listeners for alert boxes
		alerts = document.querySelectorAll('[data-dismiss=alert]');
		for (var i = 0, len = alerts.length; i < len; i++) {
			alerts[i].onclick = handler.closeAlert;
		}	
		
		if (!window.addEventListener) {
				window.attachEvent('onresize', function (e) {
				handler.closeAccords();
			});
		} else {
				window.addEventListener('resize', function (e) {
				handler.closeAccords();
			});
		}
	}
	
	this.closeAccords = function() {
		for (var i = 0, len = accords.length; i < len; i++) {
			closeAccordion(accords[i]);
		}		
	}
	
    this.closeModal = function(object) 
    {
        if (typeof object !== 'object') {
            object = getById(object);
        }
        
        if (!object || object.className.indexOf('in') == -1) return; 
        
        object.className = object.className.replace('in', '');

        setTimeout(function() {
            setBlockVisible(object, false);
            // object.parentNode.removeChild(object);
        }, 150);        
    }
    
    this.showModal = function(object) 
    {
        if (typeof object !== 'object') {
            object = getById(object);
        }
        
        if (!object || object.className.indexOf('in') > -1) return;
                
        setBlockVisible(object, true);
        object.className += ' in ';

        var height = window.getComputedStyle(object).height;
        if (height == 'auto') height = window.innerHeight;
        
        var margin = Math.round(getScrollTop() + (getClientH() / 2) - (height / 2));
        var elements = object.getElementsByTagName("button"); 
        
        var close = function() { handler.closeModal(object); };
        
        for (var i = 0; i < elements.length; i++) {
            if (elements[i].getAttribute('data-dismiss') === 'modal') {
                elements[i].onclick = close;
                break;
            };
        }
        
        object.style.top = margin + 'px';        
    }
	
	constructor();
}


var bootStrapNoJQuery = new BootstrapNoJQuery();