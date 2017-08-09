'use strict';

var evtDR = document.createEvent("Event");
evtDR.initEvent("deviceRegistered",true,true);
var evtER = document.createEvent("Event");
evtER.initEvent("noSupportPush",true,true);

var devicePush = {
	register: function(obj){
		console.log(obj);
		if(obj.idUser && obj.idApplication){			
			if ('serviceWorker' in navigator) {
			  console.log('Service Worker is supported');   
			  navigator.serviceWorker.register(sw.file + '?idUser=' + obj.idUser + '&idApplication=' + obj.idApplication).then(function(reg) {
			  	navigator.serviceWorker.getRegistration(sw.file + '?idUser=' + obj.idUser + '&idApplication=' + obj.idApplication).then(function(reg) {
				    console.log('Service Worker is ready :^)', reg);
				    setTimeout(function(){
					    reg.pushManager.subscribe({userVisibleOnly: true}).then(function(sub) {
					            	
					        var nav = '';
					      	var str = sub.endpoint;
					      	if(navigator.userAgent.indexOf("OPR") != -1){
					      		nav = 'Opera';
								str = str.replace("https://android.googleapis.com/gcm/send/", "");
							}else if(navigator.userAgent.indexOf("Chrome") != -1){
					      		nav = 'Chrome';
								str = str.replace("https://android.googleapis.com/gcm/send/", "");
							}else if(navigator.userAgent.indexOf("Firefox") != -1){
								nav = 'Firefox';
								str = str.replace("https://updates.push.services.mozilla.com/wpush/", "");
							}
													
							var xmlhttpReg = new XMLHttpRequest();
					        xmlhttpReg.open("POST", "https://apiweb.devicepush.com:8081/mobile/" + obj.idApplication + '/', true);
					        xmlhttpReg.setRequestHeader("token", obj.idUser);
					        xmlhttpReg.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
					        xmlhttpReg.onreadystatechange = function(){
					            if (xmlhttpReg.readyState == 4 && xmlhttpReg.status == 200){
					                console.log('readyStatePOST');
					                evtDR.devicePushId = JSON.parse(xmlhttpReg.responseText)._id;
					                //dispatchEvent register
				                    document.dispatchEvent(evtDR);
					            }else if(xmlhttpReg.readyState == 4 && xmlhttpReg.status != 200){
					            	console.log("Error service Device Push");  
					            	//dispatchEvent error
						            document.dispatchEvent(evtER);
					            }
					        }
					        xmlhttpReg.send(JSON.stringify({
								token: str,
								device: nav,
								additionaldata: {
									"cms_type": obj.additionalData.cms,
									"cms_version": obj.additionalData.version,
									"cms_language": obj.additionalData.language,
									"cms_name": obj.additionalData.name,
									"cms_url": obj.additionalData.url,
									"cms_user_id": obj.additionalData.userid,
									"cms_user_language": obj.additionalData.userlanguage
								}
							}));
					    });
				    }, 500);
			    });
			  }).catch(function(error) { //CATCH REGISTER
			    console.log('Service Worker error :^(', error);
			    //dispatchEvent error
			    document.dispatchEvent(evtER);
			  });
			} else {  //IF INIT
			    console.log("Your browser does not support push notifications");  
			    //dispatchEvent error
			    document.dispatchEvent(evtER);
			} 
		}
	}
}