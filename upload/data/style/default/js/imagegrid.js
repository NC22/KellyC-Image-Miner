function KellyImageGrid(cfg) {

	var handler = this;
    var env = envObj;
    
    var blockName = 'search-image-block';
    
    var blocks = false;
    var blocksInfo = new Array();
    
    var row = new Array();
    var container = false;
    
    var landscape = 0;
    var portrait = 0;
    var rendered = false;
    
    var height = 250; //start height (min height)
    var width = 800; // max width if not full sceen
    var widthReq = false; // width according to 'width', 'fullscreen' variables and max screen size 
    
    var fullScreen = false;
    
    var resizeTimeout = false;
    
	function constructor(cfg) {
        if (cfg.blockName) {
            blockName = cfg.blockName;
        }
        
        if (cfg.fullScreen) {
            fullScreen = cfg.fullScreen;
        }
        
        if (cfg.width)
            width = cfg.width;
        
        if (cfg.height)
            height = cfg.height;
            
        if (env.isDOMReady()) handler.updateGrid();
        else {
            env.addOnDOMReady(function() { handler.updateGrid(); });
        }
        
        env.addEventListner(window, "resize", function (e) {
            handler.resizeProcess();
        }, 'grid_resize_');
    }
    
    /* proportional resize by width or height */
    
	function getResizedInfo(resizeTo, info, resizeBy) 
	{		 
        var k;
        
		if (resizeBy == 'width') {
			k = info[resizeBy] / resizeTo;
			info.height = Math.ceil(info.height / k);
		} else {
			k = info[resizeBy] / resizeTo;
			info.width = Math.ceil(info.width / k);
		}
		
		info[resizeBy] = resizeTo;
		return info;
	}	
    
    function showRow() {
        if (!row.length) return;
		
		var width = 0; // counter		
        var rowDiv = document.createElement("DIV");  
        
        // count total width of row, and resize by required row height
        for (var i=0; i <= row.length-1; ++i){ 
        
            row[i] = getResizedInfo(height, row[i], 'height');
			width += row[i].width; 
            
        }
		
		// get required row width by resizing common bounds width \ height
		// lose required height, if some proportions not fit
		
        var requiredWidth = Math.floor(widthReq);
		var required = getResizedInfo(widthReq, {width : width, 'height' : height}, 'width');
		
        // finally resize image by required recalced height according to width
        
        currentRowWidth = 0;
        for (var i=0; i <= row.length-1; ++i){ 
            row[i] = getResizedInfo(required.height, row[i], 'height');
            currentRowWidth += row[i].width;
            if (currentRowWidth > requiredWidth) row[i].width = row[i].width - (currentRowWidth - requiredWidth); // correct after float operations
            
            var resizedBlock = row[i].block.cloneNode(true);
            
            resizedBlock.style.width = row[i].width + 'px';
            resizedBlock.style.height = row[i].height + 'px'; 

            var img = resizedBlock.getElementsByTagName('img')[0];    
                img.width = row[i].width;
                img.height = row[i].height;  
                
            rowDiv.appendChild(resizedBlock);
        }

        container.appendChild(rowDiv);
		
        portrait = 0;
        landscape = 0;
        row = new Array();
    }
    
    this.isFullscreen = function() {
        return fullScreen;
    }
    
    this.setFullscreen = function(full) {
        if (full) fullScreen = true;
        else fullScreen = false;       
    }
    
    this.resizeProcess = function() {
        if (resizeTimeout !== false) {
            clearTimeout(resizeTimeout);            
        }
        
        resizeTimeout = setTimeout(function(){ handler.updateGrid(); }, 500);
    }
    
    this.updateGrid = function() {
        
        if (resizeTimeout !== false) {
            clearTimeout(resizeTimeout);            
        }
        
        landscape = 0;
        portrait = 0;
        row = new Array();        
        
        var scaledNode = document.getElementById('search-results-scaled');
        
        var body = document.getElementsByTagName('body')[0];
            body.style.overflowY = 'scroll'; // include scrollbar, to count it width also
            
        var screenSize = scaledNode.getBoundingClientRect().width; 
        
        if (fullScreen) {
            widthReq =  Math.floor(screenSize);    
        } else {
            widthReq = width;
        }
        
        if (screenSize < widthReq) widthReq = screenSize;
        
        if (!container) {
            container = document.createElement("DIV");
        } else {
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
        }     
    
        while (scaledNode.firstChild) {
            scaledNode.removeChild(scaledNode.firstChild);
        }
        
        blocks = document.getElementsByClassName(blockName);
        console.log('founded : ' + blocks.length);
        for (var i=0; i <= blocks.length-1; i++){ 
            //console.log('founded : ' + blocks.length);
            blocksInfo[i] = { 
                width : parseInt(blocks[i].style.width),
                height : parseInt(blocks[i].style.height),
                portrait : false,
                block : blocks[i],
            };   
            
            if (blocksInfo[i].width < blocksInfo[i].height) blocksInfo[i].portrait = true;   
           
            if (blocksInfo[i].portrait) portrait++;
			else landscape++;
            
            row.push(blocksInfo[i]);
			
            if (i + 2 >= blocks.length) continue; // dont keep last one alone
            
			if (landscape == 1 && portrait >= 2) {
				showRow();
			} else if (landscape == 2) {
				showRow();
			} else if (portrait == 4) {
				showRow();
			}
            console.log('image ' + blocksInfo[i].width + ' | ' + blocksInfo[i].height + ' - ' + blocksInfo[i].portrait) ;
        }
        
        showRow();
  
            scaledNode.innerHTML = container.innerHTML;
            container.innerHTML = '';
       // scaledNode.parentNode.replaceChild(container, scaledNode);
        
        rendered = true;
    }
    
    constructor(cfg);
}

var envObj = new KellyLightEnviroment();