localStorage['serviceURL'] = "http://localhost/phonegap-start/services/";
var serviceURL = localStorage['serviceURL'];


function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
		}
			return vars;
}

var action = getUrlVars()["action"];

var app = {
    // Application Constructor
    initialize: function() {
        this.bindEvents();
    },
    // Bind Event Listeners
    //
    // Bind any events that are required on startup. Common events are:
    // 'load', 'deviceready', 'offline', and 'online'.
    bindEvents: function() {
        document.addEventListener('deviceready', this.onDeviceReady, false);
    },
    // deviceready Event Handler
    //
    // The scope of 'this' is the event. In order to call the 'receivedEvent'
    // function, we must explicity call 'app.receivedEvent(...);'
    onDeviceReady: function() {
        app.receivedEvent('deviceready');
    },
    // Update DOM on a Received Event
    receivedEvent: function(id) {
		document.location.href = "login.html"; 
    }		
};

function submitLoginForm(){	
	$.post(serviceURL + 'ExposeWS.php?action=login', $("#loginForm").serialize(),  function(data) {		
		var obj = jQuery.parseJSON(data);
		if(obj.login=="successful"){
			document.location.href = "listArticles.html"; 
		} else if(obj.login =="failed") {
			$("#LoginErrorDiv").css("display", "block");
			$("#LoginErrorDiv").text("Datele de autentificare sunt incorecte. Incercati din nou.");
		} else {
		alert("Service failed..");
		}		
	});
}

function signup(){
	if($("#password").val()==$("#passwordAgain").val()){
	$.post(serviceURL + 'ExposeWS.php?action=signup', $("#signUpForm").serialize(), function(data) {
		var obj = jQuery.parseJSON(data);
		if(obj.signup=="successful"){
			alert
			document.location.href = "listArticles.html"; 
		} else if(obj.signup =="failed") {			
			$("#SignUpError").css("display", "block");
			$("#SignUpError").text("Inregistrare esuata. Incercati din nou.");
		} else {
		alert("Service failed..");
		}		
	})
	.error(function() { 
   alert("error"); 
	});
	} else {
			$("#SignUpError").css("display", "block");
			$("#SignUpError").text("Parolele nu coincid");
	}
}