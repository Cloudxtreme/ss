//强制切换 界面类型 "mobile" "web" - wangchao
//注意 : 此 js 需要先引用 jquery.cookie 和 jquery 库
function switch_interface_type(type,auth){
	setInterForceType(type);
	if(auth){
		window.location.href=auth;	
	}else{
		window.location.href='/?'+Date.parse(new Date());
	}
}

//判断设备系统
function checkserAgent(){
	var flag=false;
   var userAgentInfo=navigator.userAgent;
		var userAgentKeywords=new Array("Android", "iPhone" ,"SymbianOS", "Windows Phone", "iPod", "MQQBrowser");
		 //排除windows系统 苹果系统

		var i = 0;
		for (i = 0; i < userAgentKeywords.length; i++)
		{
		if(userAgentInfo.match(userAgentKeywords[i])) {
			flag=true;
			break;
		}
 		}
		
   return flag;
}

//设置是否是移动设别
function format_is_mobile(){
	if($.cookie('is_mobile') == null){
		if(checkserAgent()){
			$.cookie('is_mobile',1,{path: '/'});
		}else{
			$.cookie('is_mobile',0,{path: '/'});
		}
	}
}

//强制设置类型 web mobile
function setInterForceType(type){
	$.cookie('interface_type',type,{path: '/'});
}

//获取强制类型
function getForceType(){
	return ($.cookie('interface_type'));
}

//是否应该跳转到 mobile
function needJumpMobile(){
	if(getForceType() == "mobile"){
		return true;
	} else if (getForceType() == "web") {
		return false;
	} else {
		if($.cookie('is_mobile') == 1){
			return true;
		} else {
			return false;
		}
	}
}