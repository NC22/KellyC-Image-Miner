var baseUrl = '';
// todo произвольная работа с параметрами
// todo таймаут на старые работы
function writeLog(key, text) 
{
	var textarea = getById(key + '-parse-log');
    var lines = textarea.innerHTML.split("\n");
    if (lines.length > 500) textarea.innerHTML = '';
    
    textarea.innerHTML += '[' + _getTime() + '] ' + text + "\n";
	textarea.scrollTop = textarea.scrollHeight;
}

var jobList = [];
    jobList['page'] = false;
    jobList['image'] = false;
    jobList['hash'] = false;
    jobList['custom'] = false;
    
var timers = [];

var jobInfo = [];

var jobParseCount = 1;

function getErrorTimeOut() 
{
    var time = 40000;
    var timeout = getById('timeout-time');
    if (timeout) {
        if (parseInt(timeout.value)) {
            time = parseInt(timeout.value) * 1000;
        }
    }
    
    return time;
}

function getCommonFieldsSendData(key)
{
    send = 'ajaxQuery=1';
    
    var session = getById(key + '-session');
    if (session) {
        var sessionId = parseInt(session.options[session.selectedIndex].value);
        if (sessionId) {
            send += '&sessionId=' + sessionId;
        }
    }
    
    var count = parseInt(getValById(key + '-parse-count'));
    if (!count) {
        writeLog(key, 'Некорректно заданы параметры количества страниц');
        return false;
    } else {
        send += '&count=' + count;
    }
    
    return send;
}

function updateJobButton(key) // rename to onUpdateState
{
    console.log('[updateJobButton] begin job : ' + key);  
    if (jobList[key] == true) {
        
        var button = getById(key + '-parse-button');
       
            button.innerHTML = 'Завершить';
            button.onclick = function() {
                stopJobParse(key);
                return false;
            };
            
            getById(key + '-parse-state').innerHTML = 'Запущена';
    } else {
        getById(key + '-parse-state').innerHTML = 'Остановлена';
        
        var button = getById(key + '-parse-button');
            button.innerHTML = 'Начать';
            button.disabled = false;  
            
        if (key == 'page') {
            button.onclick = function (e) {
                startJobPageParse();
                return false;
            }
        } else if (key == 'image') {
            button.onclick = function (e) {
                startJobImageParse();
                return false;
            }            
        } else if (key == 'hash') {
            button.onclick = function (e) {
                startJobRehash();
                return false;
            }            
        } else if (key == 'custom') {
            button.onclick = function (e) {
                startJobCustom();
                return false;
            }            
        } 
    }
    
    if (jobInfo[key] && jobInfo[key].left) 
        getById(key + '-parse-state').innerHTML += '(Осталось : ' + jobInfo[key].left + ')';
}

function stopJobParse(key) 
{
    jobList[key] = false;
	
	if (timers[key + '_hung']) {
		clearTimeout(timers[key + '_hung']);
		timers[key + '_hung'] = false;
	}     
	
    if (timers[key]) {
        clearTimeout(timers[key]);
        timers[key] = false;
        updateJobButton(key);
        return false;
    }   
    
    getById(key + '-parse-button').disabled = true;
    getById(key + '-parse-state').innerHTML = 'Выполняется остановка...';
    
    return false;
}

function startJobPageParse() 
{    
    jobList['page'] = true;
    timers['page'] = false;
    
    updateJobButton('page');
        
    var text = 'Начало выполнения операции №' + jobParseCount;
    
    var failPage = 0;
    if (getById('fail-page').checked) {
        text += ' [только страницы загруженные с ошибкой]';
        failPage = 1;
    }
    
    var token = getById('page-parse-token').value;
    
    writeLog('page', text);
    jobParseCount++;

    var event = function(response) {
        var timeout = 0;
        var continueJob = function(){ startJobPageParse(); }        
        
        if (response['token']) {
            getById('page-parse-token').value = response['token'];
        }
         
        if (response['code'] == 0) {
            writeLog('page', 'Успешно | обработанные страницы : ' + response['pages']);
            timeout = 3000;
        } else if (response['code'] !== -1) {
            writeLog('page', 'Ошибка ' + response['code'] + ' | Описание : ' + response['message'] + ' Таймаут ' + (getErrorTimeOut() / 1000) + 'сек');
            if (response['pages'])  writeLog('page', 'обработанные страницы : ' + response['pages']);
            timeout = getErrorTimeOut();
        } else if (response['pages']) {
            writeLog('page', 'Работа завершена | обработанные страницы : ' + response['pages']);
        }
        
        if (response['nojob']) {
            writeLog('page', 'Задач нет');
            jobList['page'] = false;
        }
        
        if (jobList['page']) {
            timers['page'] = setTimeout(continueJob, timeout);
        } else {
            getById('page-parse-state').innerHTML = 'Не запущена';
        }
        
        updateJobButton('page'); 
    }
    
    //encodeURIComponent(text) 
    var send = getCommonFieldsSendData('page');
    if (send === false) return false;
        send += '&fail=' + failPage; 
        
    sendByXmlHttp('joy/downloadmaterial', send, event, token);
    
    return false;
}

function startJobImageParse() 
{    
    jobList['image'] = true;
    timers['image'] = false;
    updateJobButton('image');
        
    var text = 'Начало выполнения операции №' + jobParseCount;
    
    var failImage = 0;
    if (getById('fail-image').checked) {
        text += ' [только изображения загруженные с ошибкой]';
        failImage = 1;
    }
  
    var checkHash = 0;
    if (getById('image-check-hash').checked) {
        checkHash = 1;
    } else {
        text += ' [только загрузка изображений в буфер, для дальнейшей работы]';
    }
    
    var token = getById('image-parse-token').value;
    
    writeLog('image', text);
    jobParseCount++;

    var event = function(response) {
        
        var timeout = 3000;
        var continueJob = function(){ startJobImageParse(); }
        
        if (response['token']) {
            getById('image-parse-token').value = response['token'];
        }
         
        if (response['code'] == 0) {
            writeLog('image', 'Успешно | обработанные изображения : ' + response['images']);
        } else if (response['code'] !== -1) {
            writeLog('image', 'Ошибка ' + response['code'] + ' | Описание : ' + response['message'] + ' Таймаут ' + (getErrorTimeOut() / 1000) + 'сек');
            if (response['images'])  writeLog('image', 'обработанные страницы : ' + response['images']);
            timeout = getErrorTimeOut();
        } else if (response['images']) {
            writeLog('image', 'Работа завершена | обработанные страницы : ' + response['images']);
        }
        
        if (response['nojob']) {
            writeLog('image', 'Задач нет');
            jobList['image'] = false;
        }
        
        if (jobList['image']) {
            timers['image'] = setTimeout(continueJob, timeout);
        } else {
            getById('image-parse-state').innerHTML = 'Не запущена';
        }
        
        updateJobButton('image');
    }
    
    //encodeURIComponent(text) 
    var send = getCommonFieldsSendData('image');
    if (send === false) return false;
        send += '&fail=' + failImage + '&checkHash=' + checkHash; 
        
    sendByXmlHttp('joy/downloadimg', send, event, token);
    
    return false;
}

function startJobRehash() 
{    
    if (!jobList['hash']) {
        jobInfo['hash'] = {left : 0};
    }
    
    jobList['hash'] = true;
    timers['hash'] = false;
    updateJobButton('hash');
    
    var text = 'Начало выполнения операции №' + jobParseCount;
    
    var count = parseInt(getValById('hash-parse-count'));
    if (!count) {
        writeLog('hash', 'Некорректно заданы параметры количества страниц');
        return;
    }
    
    var token = getById('hash-parse-token').value;
    
    writeLog('hash', text);
    jobParseCount++;

    var event = function(response) {
        
        var timeout = 300;
        var continueJob = function(){ startJobRehash(); }
        
        if (response['token']) {
            getById('hash-parse-token').value = response['token'];
        }
        
        if (response['left'])
            jobInfo['hash'].left = response['left'];
        else if (jobInfo['hash'].left > 0)
            jobInfo['hash'].left -= parseInt(getValById('hash-parse-count'));
         
        if (response['code'] === 0) {
            writeLog('hash', 'Успешно | обработанные изображения : ' + response['images']);
        } else if (response['code'] !== -1) {
            writeLog('hash', 'Ошибка ' + response['code'] + ' | Описание : ' + response['message'] + ' Таймаут 1сек | Обработано : ' + response['images']);
            timeout = 1000;
        } 
        
        if (response['nojob']) {
            writeLog('hash', 'Задач нет');
            jobInfo['hash'].left = 0;
            jobList['hash'] = false;
        }
        
        if (jobList['hash']) {
            timers['hash'] = setTimeout(continueJob, timeout);
        } else {
            getById('hash-parse-state').innerHTML = 'Не запущена';
        }
        
        updateJobButton('hash');
    }
    
    //encodeURIComponent(text) 
    var send = 'ajaxQuery=1' + '&count=' + count; 
    if (jobInfo['hash'].left == 0) send += '&getLeft=1';
    
    sendByXmlHttp('joy/rehash', send, event, token);
    
    return false;
}


function startJobCustom() 
{    
    jobInfo['custom'] = {from : parseInt(getValById('custom-from'))};
    
    jobList['custom'] = true;
    timers['custom'] = false;
	timers['custom_hung'] = false;
    updateJobButton('custom');
    
    var text = 'Начало выполнения операции №' + jobParseCount;
    
    var link = getValById('custom-parse-link');
    var count = parseInt(getValById('custom-parse-count'));
    if (!count) {
        writeLog('custom', 'Некорректно заданы параметры количества записей');
        return;
    }
    
    var token = getById('custom-parse-token').value;
    
    writeLog('custom', text);
    jobParseCount++;

    var event = function(response) {
	
		if (timers['custom_hung']) {
			clearTimeout(timers['custom_hung']);
		}
        
		timers['custom_hung'] = false;
		
        var timeout = 300; // time to start job again
        var continueJob = function(){ startJobCustom(); }
                
        if (response['custom']) {
            getById('custom-parse-token').value = response['token'];
        }
        
        if (response['code'] === 0) {
            writeLog('custom', 'Успешно | ответ сервера : ' + response['short']);
            
            if (jobInfo['custom'].from) getById('custom-from').value = jobInfo['custom'].from + parseInt(getValById('custom-parse-count'));
            
        } else if (response['code'] !== -1) {
            writeLog('custom', 'Ошибка ' + response['code'] + ' | Описание : ' + response['message'] + ' Таймаут ' + (getErrorTimeOut() / 1000) + 'сек ' );
            timeout = getErrorTimeOut();
        } 
        
        if (response['nojob']) {
            writeLog('custom', 'Задач нет');
            jobList['custom'] = false;
        }
        
        if (jobList['custom']) {
            timers['custom'] = setTimeout(continueJob, timeout);
        } else {
            getById('custom-parse-state').innerHTML = 'Не запущена';
        }
        
        if (response['items']) {
            writeLog('custom', 'Затронуты записи ' + response['items'] );
        } 
        
        updateJobButton('custom');
    }
    
    //encodeURIComponent(text) 
    var send = 'ajaxQuery=1' + '&count=' + count; 
    if (jobInfo['custom'].from)
        send += '&from=' + jobInfo['custom'].from;
    
    var request = sendByXmlHttp(link, send, event, token);
    var cancelHung = function() {
		request.abort();
		var continueJob = function(){ startJobCustom(); }
		timers['custom_hung'] = false;
		
		if (timers['custom']) {
			clearTimeout(timers['custom']);			
		}
		
		timers['custom'] = setTimeout(continueJob, 300);
		writeLog('custom', 'Сброс зависшей работы');		
		updateJobButton('custom');
	}
	
	timers['custom_hung'] = setTimeout(cancelHung, 12 * 60 * 1000);
    return false;
}