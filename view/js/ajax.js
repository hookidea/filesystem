
/**
 * @Name: 4.php
 * @Role:   Ajax 对象的构造函数
 * @Author:   拓少
 * @Date:   2015-11-04 14:40:07
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-04 15:09:07
 */

/*
	使用示例：

		ajax.get('5.php?id=333&name=hehe', function(data){
			oDiv.innerHTML = data;
		});
		alert(oTxt.value);
		
		var obj = new Object();
		obj[oTxt.name] = oTxt.value;
		var arr = [];
		arr[oTxt.name] = oTxt.value; 
		ajax.post('5.php', arr, function(data){
			oDiv.innerHTML = data;
		});

*/

/**
 * Ajax 对象的构造函数
 * @param string recvType 指定数据返回类型：XML/HTML(默认)
 * @return object         Ajax 对象
 */
function Ajax(recvType){
	var aj = new Object();
	aj.targetUrl = '';        //请求的地址
	aj.sendString = '';       //要发送的数据
	aj.recvType = recvType ? recvType.toUpperCase() : 'HTML';  //服务器返回的数据类型：XML/HTML(默认)
	aj.resultHandle = null;   //状态为4时的回调函数

	aj.createXMLHttpRequest = function(){//这是一个可以创建兼容的XMLTttpRequest对象的函数
		if(window.XMLHttpRequest){//非IE
			return new XMLHttpRequest();
		}else if(window.ActiveXObject){//IE6
			return new ActiveXObject('Microsoft.XMLHTTP');
		}
	}

	aj.XMLHttpRequest = aj.createXMLHttpRequest();//创建XMLTttpRequest对象

	aj.processHandle = function(){//处理状态为4时的回调函数
		if(aj.XMLHttpRequest.readyState === 4){
			if(aj.XMLHttpRequest.status == 200 || aj.XMLHttpRequest.status == 304){
				if(aj.recvType=="HTML")
					aj.resultHandle(aj.XMLHttpRequest.responseText);
				else if(aj.recvType=="XML")
					aj.resultHandle(aj.XMLHttpRequest.responseXML);
			}
		}
	}

	//GET方式请求
	aj.get = function(targetUrl, resultHandle){
		aj.targetUrl = targetUrl;

		if(typeof(resultHandle) == 'function'){//如果有传递了回调函数
			aj.resultHandle = resultHandle;
			aj.XMLHttpRequest.onreadystatechange = aj.processHandle; //绑定状态为4时的回调函数
		}

		if(window.XMLHttpRequest){//非IE
			aj.XMLHttpRequest.open('GET', aj.targetUrl);
			aj.XMLHttpRequest.send(null);
		}else{//IE
			aj.XMLHttpRequest.open('GET', aj.targetUrl, true);//true为是否开启异步
			aj.XMLHttpRequest.send();
		}

	}

	//POST方式请求
	aj.post = function(targetUrl, sendString, resultHandle){
		aj.targetUrl = targetUrl;

		if(typeof(resultHandle) == 'function'){//如果有传递了回调函数
			aj.resultHandle = resultHandle;
			aj.XMLHttpRequest.onreadystatechange = aj.processHandle; //绑定状态为4时的回调函数
		}

		if(typeof(sendString) == 'object'){//如果数据是通过 对象/数组 传递的
			var str = '';
			for(var pro in sendString){
				 str += pro + '=' + sendString[pro] + '&';
			}
			aj.sendString = str.substr(0, str.length-1);
		}else{//如果数据是通过 字符串 传递的
			aj.sendString = sendString;
		}

		aj.XMLHttpRequest.open("POST", targetUrl);
		aj.XMLHttpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		aj.XMLHttpRequest.send(aj.sendString);
	}

	return aj;
}


