//获取选中的radio组件的value值
function get_radio_value(obj){
	for( var i=0;i<obj.length;i++ ){
		if(obj[i].checked){
			return obj[i].value;
		}
	}
	return "";
}

//获取鼠标指针坐标
function mousePosition(ev) {
	if(ev.pageX || ev.pageY) {
		return {x:ev.pageX, y:ev.pageY};
	};
	return {
		x:ev.clientX + document.body.clientLeft,y:ev.clientY + Math.max(document.body.scrollTop,document.documentElement.scrollTop)
	}; 
};

/**
MAC 地址格式化
标准格式 xx:xx:xx:xx:xx:xx
*/
function mac_format(mac){
	if(mac.split(":").length == 6){
		mac = mac.toUpperCase();
	}
	else if(mac.split("-").length == 6){
		mac = mac.replace(/-/g,":");
		mac = mac.toUpperCase();
	}
	else{
		mac = mac.substr(0, 2).toUpperCase() + ":" + mac.substr(2, 2).toUpperCase() + ":" + mac.substr(4, 2).toUpperCase() + ":" + mac.substr(6, 2).toUpperCase() + ":" + mac.substr(8, 2).toUpperCase() + ":" + mac.substr(10, 2).toUpperCase();
	}
	return mac;
}

/**
数组去重复
用法:
alert([1, 1, 2, 3, 4, 5, 4, 3, 4, 4, 5, 5, 6, 7].del()); 
*/
Array.prototype.del = function() { 
	var a = {}, c = [], l = this.length; 
		for (var i = 0; i < l; i++) { 
		var b = this[i]; 
		var d = (typeof b) + b; 
		if (a[d] === undefined) { 
			c.push(b); 
			a[d] = 1; 
		} 
	} 
	return c; 
} 

/**
获取地址栏参数
用法:
var strHref = window.location.href;
var v = strHref.getQuery("v");
*/
String.prototype.getQuery = function (name) {
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
	var r = window.location.search.substr(1).match(reg);
	if (r != null) return unescape(r[2]); return null;
}

//判断是否是URL
function IsURL(str_url){
    var strRegex = "^((https|http|ftp|rtsp|mms)?://)"
    + "?(([0-9a-z_!~*'().&=+$%-]+: )?[0-9a-z_!~*'().&=+$%-]+@)?" //ftp的user@
    + "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184
    + "|" // 允许IP和DOMAIN（域名）
    + "([0-9a-z_!~*'()-]+\.)*" // 域名- www.
    + "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // 二级域名
    + "[a-z]{2,6})" // first level domain- .com or .museum
    + "(:[0-9]{1,4})?" // 端口- :80
    + "((/?)|" // a slash isn't required if there is no file name
    + "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$";
    var re=new RegExp(strRegex);
	var gg = /[`?#~$%^&*()_+=|\{\};\[\];\"',<>]/g;
    if (re.test(str_url)&&(gg.test(str_url)==false)){
        return (true);
    }else{
        return (false);
    }
}
/*
 * 倒计时
 * nMax - 秒数
 * nInterval - 间隔多少秒
 * */
function Counter(nMax,nInterval)
{
	this.maxTime=nMax;
	this.interval=nInterval;
	this.objId="timer";
	this.obj=null;
	this.num=this.maxTime;
	this.timer=null;
	this.start=function()
	{ 
		this.obj=document.getElementById(this.objId);
		if(this.num>0) setTimeout(this.run,this.interval*1000);
	};
		this.run=function()
	{
	if(myCounter.num>0) 
	{
		myCounter.num--;
		myCounter.obj.innerHTML=myCounter.num;
		myCounter.timer=setTimeout(myCounter.run,myCounter.interval*1000);
	}
	else clearTimeout(myCounter.timer);
	};
	this.show=function()
	{
		document.write("<span id="+this.objId+">"+this.num+"</span>");
		this.obj=document.getElementById(this.objId);
		//alert(this.obj.innerHTML);
	}
}

//判断是否属于IP地址
function CheckIp(addr){
    var reg = /^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])(\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])){3}$/;
    if(addr.match(reg)){
        return true;
    }else{
        return false;
    }
}

//判断是每个字符都是数字
function isNumeric(str){
    if (str.length == 0){
        return false;
    }
    for(var i=0; i<str.length; i++){
        /*字符串之间也可以比较大小，大小按首字符的编码值决定，如果首字符相等，则比较第二个字符，依次往后逐个比较。*/
        if (str.charAt(i)<"0" || str.charAt(i)>"9"){
            return false;
        }
    }
    return true;
}

/*
 * 检查是否有中文字符 (过于 a)
 * nMax - 秒数
 * nInterval - 间隔多少秒
 */
function HaveChineseStr(input){
	var have = false;
	var one;
	input =  input.toString()
	for(i=0;i<input.length;i++){
		one = input.substr(i,1);
		//ascii 码
		if (one.charCodeAt(0)>127){
			have = true;
		}
	}
	return have;
}

function HTMLEncode (input) { 
	var converter = document.createElement("DIV"); 
	converter.innerText = input; 
	var output = converter.innerHTML; 
	converter = null; 
	return output; 
}

function ipv6_hex2mask(sixmask){
	var maskval = 0;
	//var sixmask = "FFFF:FFFF:FFFF:FFFF:0:0:0:0";
	var masks = sixmask.split(":");
	if(masks.length==1){
		return sixmask;
	}
	for ( var i=0; i<masks.length; i++){
		var str = masks[i];
		for(var j=0;j<str.length;j++){
			var mask6num = (eval("0x"+str.charAt(j))).toString(2);
			for(var k=0;k<mask6num.length;k++){
				if(mask6num.charAt(k)=='1'){
					maskval++;
				}
			}
		}
	}
	return maskval;
}

function UrlDecode(zipStr){  
    var uzipStr="";  
    for(var i=0;i<zipStr.length;i++){  
        var chr = zipStr.charAt(i);  
        if(chr == "+"){  
            uzipStr+=" ";  
        }else if(chr=="%"){  
            var asc = zipStr.substring(i+1,i+3);  
            if(parseInt("0x"+asc)>0x7f){  
                uzipStr+=decodeURI("%"+asc.toString()+zipStr.substring(i+3,i+9).toString());  
                i+=8;  
            }else{  
                uzipStr+=AsciiToString(parseInt("0x"+asc));  
                i+=2;  
            }  
        }else{  
            uzipStr+= chr;  
        }  
    }  
  
    return uzipStr;  
}

function StringToAscii(str){  
    return str.charCodeAt(0).toString(16);  
}

function AsciiToString(asccode){  
    return String.fromCharCode(asccode);  
}

/**
/通过加载图片判断对方服务器是否正常，然后相应进行不同策略的事件触发
/url 目标地址的图片文件
/回调funciton对象，成功访问success，错误访问error，超时后的处理timeout
/timeout 超时时间单位秒，计算时间按5秒递进
列子：
	autoLink("http://192.168.199.1/turbo-static/turbo/logo_130726.png",{
		"success":function(){
			alert(111)
		},"error":function(){
			alert(222)
		},"timeout":function(){
			alert(333)
		}},20);
*/
function autoLink(url,callback,timeout){
	var time = 5000;
	var timecounter = 0;
	var interval = window.setInterval(function() {
		var img = new Image();
		img.onload = function() {
			window.clearInterval(interval);
			//top.location.href = 'http://'+hostip+'/';
			if(callback.success){
				callback.success();
			}
		};
		img.onerror = function(e){
			timecounter+=5000;
			if(timecounter/1000>timeout){
				//退出
				if(callback.timeout){
					window.clearInterval(interval);
					callback.timeout();
					return;
				}
			}
			if(callback.error){
				callback.error();
			}
		};
		img.src = url + '?' + Math.random();
	}, time);
}