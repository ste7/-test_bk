var Jaggaer = function() {
    this.member = this.getCookie('memberHash');
    this.template = { 
                                'historyHead': "'<tr><th>Job code</th><th>Job title</th><th>Job date</th><th>Job status</th><th>Job message</th></tr>'" ,
                                'historyItem': "<tr class='%%jobStatus%%'><td>%%jobCode%%</td><td>%%jobTitle%%</td><td>%%jobDate%%</td><td>%%jobState%%</td><td><div><xmp>%%jobMessage%%</xmp></div></td></tr>" 
                             }
    this.init();
}

Jaggaer.prototype.init = function() {
    this.checkIfLogedIn();
    this.attachEvents();
}

Jaggaer.prototype.setCookie = function(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";

}

Jaggaer.prototype.getCookie = function(cname) {
    var name = cname + "=",
        decodedCookie = decodeURIComponent(document.cookie),
        ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

Jaggaer.prototype.callApi = function(data,url,successCallback, errorCallback, type) {
    var _this=this;
    if(typeof(type) === 'undefined'){
        type = 'POST';
    }
    $.ajax({
    url: url,
    type: type,
    data:  data,
    dataType: 'json',
    success: function (response) {
        if(typeof(successCallback) != 'undefined'){
            successCallback(response);
        }
    },
    error: function () {
        if(typeof(errorCallback) != 'undefined'){
            errorCallback(errorCallback);
        }else{
            _this.notify('There was an unknown error, please contact support','error');
        }
    }
    });

}
Jaggaer.prototype.logInMember = function (key) {
    
    var _this = this,
        username = $('#username').val(),
        password = $('#password').val();
        
    if (username.length !== 0 && password.length !== 0) {
        _this.callApi({'username': username,'password':password},'/api/login',function(result){
            if(typeof(result) == 'undefined' || typeof(result.data) == 'undefined' || typeof(result.data.status) == 'undefined' || result.data.status != "User found"){
                _this.notify('Invalid login credentials','error');
                return;
            }
            _this.member = result.data.member;
            _this.setCookie('memberHash',_this.member, 10);
            _this.notify('Loged in successfully','succes');
            $('section.login').hide();
            $('section.pages').show();
            $('header').show();
            _this.openDashboard();
        });
    } else {
        _this.notify('Username and password invalid','error');
    }
}

Jaggaer.prototype.logOut = function (key) {
    
    var _this = this;
    
    _this.setCookie('memberHash',_this.member, -1);
    $('section.login').show();
    $('section.pages').hide();
    $('header').hide();
}


Jaggaer.prototype.openDashboard = function (key) {

    var _this = this;
    
    _this.callApi({},'/api/history',function(result){
        
        if(typeof(result) == 'undefined' || typeof(result.data) == 'undefined' ||  result.data.length == 0){
            _this.notify('No data','error');
            return;
        }
        
        var inputCounter = 0,
            outputCounter = 0,
            prepairArray = [];
            
        for(var i = 0; i < result.data.length; i++) {
            var currentJob = result.data[i];
                prepairArray.push({
                        'jobCode': currentJob.jobCode,
                        'jobTitle': currentJob.jobIdentifier,
                        'date': currentJob.jobDate,
                        'jobStatus':currentJob.status,
                        'jobMessage': typeof(currentJob.data) === 'undefined' ? '' : currentJob.data
                });
                if(currentJob.jobGroup === 'input'){
                    inputCounter++;
                }
                
                if(currentJob.jobGroup === 'output'){
                    outputCounter++;
                }
        }
        
        prepairArray.sort(function(a,b) {return (a.date < b.date) ? 1 : ((b.date < a.date) ? -1 : 0);} ); 
        var historyBuild = _this.template.historyHead;
        
        for(var i = 0; i < prepairArray.length; i++) {
            historyBuild += _this.template.historyItem.replace("%%jobCode%%", prepairArray[i].jobCode)
                                    .replace("%%jobTitle%%", prepairArray[i].jobTitle)
                                    .replace("%%jobDate%%", prepairArray[i].date)
                                    .replace("%%jobStatus%%", prepairArray[i].jobStatus)
                                    .replace("%%jobState%%", prepairArray[i].jobStatus)
                                    .replace("%%jobMessage%%", prepairArray[i].jobMessage);
        }
        
        $('#valueOfInputJobs').html(inputCounter);
        $('#valueOfOutputJobs').html(outputCounter);
        $("#history-holder").html(historyBuild);
        
    },function(){},"GET");
}

Jaggaer.prototype.attachEvents = function(){
    var _this = this;
    
    $('#logOut').click(function(){
        _this.logOut();
    });
    $('.openDashboard').click(function(){
        _this.openDashboard();
    });
    $('section.login #loginForm').submit(function(e){
       e.preventDefault();
        _this.logInMember(this);
        return false;
    });
    $('#nav-icon').click(function () {
        $(this).toggleClass('open');
    });
    $('.menu li a').click(function () {
        $('#nav-icon').trigger('click');
    });
}
Jaggaer.prototype.checkIfLogedIn = function(){
    
    var _this = this;
    
    if(typeof(this.member) === 'undefined' || this.member==''){
        $('section.login').show();
        $('section.pages').hide();
        $('header').hide();
        return false;
    }
    _this.openDashboard();
    
    
    return true;

}
Jaggaer.prototype.notify = function (message,type) {
    $.notify({
        message: message
    },{
        type: type
    });
}


var Jaggaer = new Jaggaer;
