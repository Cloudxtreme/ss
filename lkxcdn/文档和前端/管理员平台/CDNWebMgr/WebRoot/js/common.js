Util = {
	doAjaxPost : function(targetUrl, options, callback, dataType) {
		if (!dataType) {
			dataType = "text";
		}
		$.blockUI();
		$.ajax( {
			// jquery 1.4
			traditional: true,
			type : "POST",
			url : targetUrl,
			data : options,
			dataType : dataType,
			cache : false,
			success : function(data, textStatus) {
				$.unblockUI();
				callback(data);
			},
			error : function(XMLHttpRequest, textStatus, errorThrown) {
				$.unblockUI();
				alert("Invoke ajax error:" + textStatus + " " + errorThrown);
			}
		});
	},

	StringBuffer : function() {
		this._strings_ = new Array();
		this.append = function(str) {
			this._strings_.push(str);
			return this;
		};
		this.toString = function() {
			return this._strings_.join("");
		};
	},

	getEditorData : function(frameId) {
		var editor_data = window.frames[frameId].CKEDITOR.instances.fcEditor
				.getData();
		return editor_data;
	}
};

/**
 * 输入框，主要用于搜索框取得焦点事件
 * 
 * @param src
 * @param inputDefaultValue
 * @return
 */
function inIuput(src, inputDefaultValue){
	if(inputDefaultValue == $.trim($(src).val())){
		$(src).val("");
		$(src).removeClass("input_blur");
	}
};

/**
 * 输入框，主要用于搜索框失去焦点事件
 * 
 * @param src
 * @param inputDefaultValue
 * @return
 */
function outInput(src, inputDefaultValue){
	if("" == $.trim($(src).val())){
		$(src).val(inputDefaultValue);
		$(src).addClass("input_blur");
	}
};

/**
 * 取得输入框的值，主要用于搜索框
 * 
 * @param id
 * @param inputDefaultValue
 * @return
 */
function getInputValue(id, inputDefaultValue){
	var value = $.trim($("#" + id).val());
	if(inputDefaultValue == value){
		value = "";
	}
	return value;
};

/**
 * 格式化占位符 Each token must be unique, and must increment in the format {0}, {1},
 * etc. Example usage:
 * 
 * <pre><code>
 * var cls = 'my-class', text = 'Some text';
 * var s = formatText('&lt;div class=&quot;{0}&quot;&gt;{1}&lt;/div&gt;', cls, text);
 * // s now contains the string: '&lt;div class=&quot;my-class&quot;&gt;Some text&lt;/div&gt;'
 * </code></pre>
 * 
 * @param {String}
 *            string The tokenized string to be formatted
 * @param {String}
 *            value1 The value to replace token {0}
 * @param {String}
 *            value2 Etc...
 * @return {String} The formatted string
 * @static
 */
function formatText(format){
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/\{(\d+)\}/g, function(m, i){
        return args[i];
    });
};

function formatDate(formatDate, formatString) {   
   if(formatDate instanceof Date) {   
	   var months = new Array("一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月");   
	   var yyyy = formatDate.getFullYear();   
	   var yy = yyyy.toString().substring(2);   
	   var m = formatDate.getMonth()+1;   
	   var mm = m < 10 ? "0" + m : m;   
	   var mmm = months[formatDate.getMonth()];   
	   var d = formatDate.getDate();   
	   var dd = d < 10 ? "0" + d : d;   

	   var h = formatDate.getHours();   
	   var hh = h < 10 ? "0" + h : h;   
	   var n = formatDate.getMinutes();   
	   var nn = n < 10 ? "0" + n : n;   
	   var s = formatDate.getSeconds();   
	   var ss = s < 10 ? "0" + s : s;   
   
       formatString = formatString.replace(/yyyy/i, yyyy);   
       formatString = formatString.replace(/yy/i, yy);   
       formatString = formatString.replace(/mmm/i, mmm);   
       formatString = formatString.replace(/mm/i, mm);   
       formatString = formatString.replace(/m/i, m);   
       formatString = formatString.replace(/dd/i, dd);   
       formatString = formatString.replace(/d/i, d);   
       formatString = formatString.replace(/hh/i, hh);   
       formatString = formatString.replace(/h/i, h);   
       formatString = formatString.replace(/nn/i, nn);   
       formatString = formatString.replace(/n/i, n);   
       formatString = formatString.replace(/ss/i, ss);   
       formatString = formatString.replace(/s/i, s);   

       return formatString;   
   	} else {   
   	   return "";   
   }   
}

/**
 * 设置value的值，用于tab间切换
 * 
 * @param id
 * @param value
 *            通过getInputValue(id, inputDefaultValue)取得的值
 * @param inputDefaultValue
 * @return
 */
function setInputValue(id,value,inputDefaultValue){
	if(value === ""){
		$('#'+id).val(inputDefaultValue);
		$('#'+id).addClass("input_blur");
	}
	else{
		$('#'+id).val(value);
		$('#'+id).removeClass("input_blur");
	}
};

// ==========================================
// 功能: 修改URL的参数值
// 输入参数:
// url: 要修改的URL字符串
// param: 参数名称
// paramValue: 要替换的参数值
// ==========================================
function changeURLParam(url, param, paramValue) {
	var reg = new RegExp("(^|)" + param + "=([^&]*)(|$)");
	var tmp = param + "=" + paramValue;
	if (url.match(reg) != null) {
		return url.replace(eval(reg), tmp);
	} else if (url.match("[\?]")) {
		return url + "&" + tmp;
	} else {
		return url + "?" + tmp;
	}
};
// ==========================================
// 功能：页面左边菜单的展开隐藏
// ==========================================
function flexMenu(id) {
	var $id = document.getElementById(id);
	var titleList = $id.getElementsByTagName('h2');
	var contentList = $id.getElementsByTagName('div');
	var changeDisplay = function(node) {
		var display = node.style.display;
		if (display == '') {
			node.style.display = 'none';
		} else {
			node.style.display = '';
		}
	};
	titleList[titleList.length - 1].style.border = 'none';
	for ( var i = 0; i < titleList.length; i++) {
		titleList[i].onclick = function() {
			var jObj = jQuery(this);
			if(jObj.hasClass('title')){jObj.removeClass('title').addClass('title2');}
			else{jObj.removeClass('title2').addClass('title');};
			// document.getElementsByTagName('h2').className = "title2";
			// for ( var a = 0; a < contentList.length; a++) {
			// contentList[a].style.display = 'none';
			// }
			if (this.nextSibling.nodeType == '3') {
				changeDisplay(this.nextSibling.nextSibling);
			} else {
				changeDisplay(this.nextSibling);
			}
		};
	}
};

function getDateLength(start,end) {
	var startDate = new Date(start.replace(/-/g,"/"));
	var endDate = new Date(end.replace(/-/g,"/"));
	var intDay = (endDate-startDate)/(1000*3600*24);
	return intDay;
};

/**
 * 查询时间为空限制。
 * 
 * @param startDate
 * @param endDate
 * @return
 */
function dateNotNullCheck(startDate, endDate,einfostart,einfoend){
	if (startDate.length == 0){
		alert(einfostart);
		return false;
	}
	if (endDate.length == 0){
		alert(einfoend);
		return false;
	}
	return true;
}



/**
 * 起始时间大于截止时间或者截止时间大于今天限制。
 * 
 * @param startDate
 * @param end
 * @return
 */
function dateValueCheck(start, end,einfobigend,einfobigtoday){
	if(start > end){   
        alert(einfobigend);     
        return false;     
    }   
    var today = new Date();
    var deadline = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59);
    if(deadline.getTime() < end){   
        alert(einfobigtoday);     
        return false;     
    }
    return true;
}

/**
 * 查询时间长度限制。
 * 
 * @param start
 * @param end
 * @param dateLength
 * @return
 */
function dateLengthCheck(start, end, length,einfo){
	var dateLength = (end - start) / (1000 * 3600 * 24);
    if (dateLength > length){
    	alert(formatText(einfo,length));     
        return false;
    }
    return true;
}

/**
 * 查询起始时间限制，不能查询限定时间前的数据。
 * 
 * @param start
 * @param end
 * @param dateLength
 * @return
 */
function startDateCheck(start,span,einfo){
	var today = new Date();
	if(span == 0.5){
		 var deadline = new Date(today.getFullYear(), today.getMonth()-6, today.getDate(), 0, 0, 0);
	}
	else{
		var deadline = new Date(today.getFullYear() - span, today.getMonth(), today.getDate(), 0, 0, 0);
	}
    if(start < deadline.getTime()){  
    	if(span == 0.5){
    		 alert(lang.errmessage.halfWithin);     
    	}
    	else {
    		alert(formatText(einfo,span));    
    	}
        return false;     
    }
    return true;
}


/**
 * 验证查询的日期（JSP页面开始时间控件的id为“startDate”，截止时间控件的id为“endDate”）。
 * 
 * @return
 */
function dateCheck(datelength){
	var length = datelength || 31;
	var startDate = $.trim($('#startDate').val());
	var endDate = $.trim($('#endDate').val());
	if (!dateNotNullCheck(startDate, endDate)){
		return false;
	}
	var start = Date.parse(startDate.replace(/-/g,"/"));
	var end = Date.parse(endDate.replace(/-/g,"/"));
	if (!dateLengthCheck(start, end, length)){
		return false;
	}
	if (!dateValueCheck(start, end)){
		return false;
	}
	if (!startDateCheck(start)){
		return false;
	}
	return true;
}


/**
 * 验证查询的日期（JSP页面开始时间控件的id为“startDate”，截止时间控件的id为“endDate”）。
 * 
 * @param startDate
 *            开始时间字符串；
 * @param endDate
 *            截止时间字符串；
 * @returns {Boolean}
 */
function dateCheckByParam(startDate, endDate){
	if (!dateNotNullCheck(startDate, endDate)){
		return false;
	}
	var start = Date.parse(startDate.replace(/-/g,"/"));
	var end = Date.parse(endDate.replace(/-/g,"/"));
	if (!dateLengthCheck(start, end, 31)){
		return false;
	}
	if (!dateValueCheck(start, end)){
		return false;
	}
	if (!startDateCheck(start)){
		return false;
	}
	return true;
}


/**
 * 验证查询的日期（计费页面使用，JSP页面开始时间控件的id为“startDate”，截止时间控件的id为“endDate”）
 * 
 * @return
 */
function dateCheck4Charge(){
	var startDate = $.trim($('#startDate').val());
	var endDate = $.trim($('#endDate').val());
	if (!dateNotNullCheck(startDate, endDate)){
		return false;
	}
	var start = Date.parse(startDate.replace(/-/g,"/"));
	var end = Date.parse(endDate.replace(/-/g,"/"));
	if (!dateValueCheck(start, end)){
		return false;
	}
	if (!dateLengthCheck(start, end, 366)){
		return false;
	}
	if (!startDateCheck(start)){
		return false;
	}
	return true;
}

/**
 * 改变频道的tip值
 * 
 * @return
 */
function changeTip(){
	var multipleValues = $("#channel").val() || [];
	$('#chtip-data').html("<strong>"+lang.tipmessage.channel+"：</strong><br/>"+multipleValues.join("<br />"));
}

/**
 * ajax获取对应省份的城市信息
 * 
 * @param provinceName
 * @return
 */
function showDetail(provinceName,key,action){
	var trObj=$("#city_"+provinceName);
	var imgObj=$("#img_"+provinceName);
	if(!imgObj.hasClass("loaded")){
		imgObj.addClass("loaded");
		imgObj.attr("src",conf.context+"/images/jfxx_b2.gif");
	}
	else{
		imgObj.removeClass("loaded");
		imgObj.attr("src",conf.context+"/images/jfxx_b1.gif");
	}
	trObj.toggle();
	if(!trObj.hasClass("loaded")){
		$.ajax({
            type: "GET",
            dataType: "html",
            url: action,
            data:{key:key,province:provinceName},
            success: function(json) {
            	
                trObj.children().empty().html(json);
                trObj.addClass("loaded");
            },
          	error: function(json) {
                alert(lang.errmessage.load);
            }
        });
	}
}

/**
 * 基础查询的限制
 * 
 * @param length
 *            查询时间长度限制
 * @param span
 *            查询的时间跨度
 * @return
 */

function baseSearchCheck(length,span){
	
	// 验证所选频道是否为空
	if(jQuery("#channel").val()==null) { 
		 alert(lang.errmessage.channel);
		 return false;
	 }
	if(!baseDateCheck(length,span)){
		return false;
	}
	return true;
}

/**
 * 
 * @param length
 *            查询时间长度限制
 * @param span
 *            查询的时间跨度
 * @return
 */
function baseDateCheck(length,span){
	
	// 判断起始与截止日期是否为空
	var startDate = $.trim($('#startDate').val());
	var endDate = $.trim($('#endDate').val());
	if (!dateNotNullCheck(startDate, endDate,lang.errmessage.startdate,lang.errmessage.enddate)){
		return false;
	}
	var start = Date.parse(startDate.replace(/-/g,"/"));
	var end = Date.parse(endDate.replace(/-/g,"/"));
	// 起始时间大于截止时间或者截止时间大于今天限制。
	if (!dateValueCheck(start, end,lang.errmessage.startBiggerEnd,lang.errmessage.startBiggerToday)){
		return false;
	}
	// 查询时间长度限制。
	if (!dateLengthCheck(start, end, length,lang.errmessage.length)){
		return false;
	}
	// 查询起始时间限制，不能查询限定时间前的数据。
	if (!startDateCheck(start,span,lang.errmessage.within)){
		return false;
	}
	return true;
}

/**
 * 时间对比的限制
 * 
 * @param start
 * @param end
 * @return
 */
function compareDateCheck(startDate,endDate,length,span){
	
	// 判断起始与截止日期是否为空
	if (!dateNotNullCheck(startDate, endDate,lang.errmessage.comstartdate,lang.errmessage.comenddate)){
		return false;
	}
	var start = Date.parse(startDate.replace(/-/g,"/"));
	var end = Date.parse(endDate.replace(/-/g,"/"));
	// 起始时间大于截止时间或者截止时间大于今天限制。
	if (!dateValueCheck(start, end,lang.errmessage.comstartBiggerEnd,lang.errmessage.comstartBiggerToday)){
		return false;
	}
	// 查询时间长度限制。
	if (!dateLengthCheck(start, end, length,lang.errmessage.comlength)){
		return false;
	}
	// 查询起始时间限制，不能查询限定时间前的数据。
	if (!startDateCheck(start,span,lang.errmessage.comwithin)){
		return false;
	}
	return true;
}

/**
 * 控制对比周期的显隐
 * 
 * @return
 */

function checkCompareTj(){
	var obj=document.getElementById("compareTj");
	if(obj.checked){
		$("#compareInput").removeClass("noinput");
	}else{
		$("#compareInput").addClass("noinput");
	}
}

/**
 * 控制ISP列表的显隐
 * 
 * @return
 */
function controlIspList(){
	var val=jQuery("#regionCode").val();
	if(val && val.length==1 && val[0]=="cn") {
		jQuery("#selectedIsps").removeAttr("disabled");
		$("#selectedIsps").removeClass("gray");
		jQuery("select[id='selectedIsps']").multiselect("enable");
	}
	else {
		jQuery("#selectedIsps").attr("disabled","disabled");
		$("#selectedIsps").addClass("gray");
		jQuery("select[id='selectedIsps']").multiselect("disable");
	}
}

/**
 * 获取指定月份的最后一天
 * 
 * @param year
 * @param month
 * @return
 */
function getLastDay(year,month)  
{  
	 var new_year = year;    // 取当前的年份
	 var new_month = month++;// 取下一个月的第一天，方便计算（最后一天不固定）
	 if(month>12)            // 如果当前大于12月，则年份转到下一年
	 {  
	  new_month -=12;        // 月份减
	  new_year++;            // 年份增
	 }  
	 var new_date = new Date(new_year,new_month,1);                // 取当年当月中的第一天
	 return (new Date(new_date.getTime()-1000*60*60*24)).getDate();// 获取当月最后一天日期
};

/**
 * 根据输入值查找记录，以便于Gird的显示。
 * 
 * @param grid
 *            Grid对象。
 * @param paramId
 *            输入控件的Id。
 * 
 */
function doGridFilter(grid, paramId){
	var params={};
	var paramValue = $('#' + paramId).val();
   	params["q_queryString_like_s"]=paramValue;
   	grid.query(params);
}

/**
 * 显示Grid于jQuery BlockUI上。
 * 
 * @param grid
 * @param msgId
 */
function showGridPopup(grid, msgId,callbackFunction){
	$.blockUI({ 
		message: $("#" + msgId),
		 css: {
            top:  ($(window).height() - 345) /2 + 'px', 
            left: ($(window).width() - 600) /2 + 'px', 
            width: '700px'
        }
	}); 
	if(grid) grid.render();
	if(callbackFunction) callbackFunction();
}

function closePopup(){
	$.unblockUI();
}

function getTip(array, property) {
	var names =   new String($.map(array, function (each, i) { return " ".concat(each[property]); }));
	var tip = names.length > 300 ? (names.slice(0, 300) + '...') : names;
	return tip;
}

/**
 * 在insert到Grid的record中，根据property，过滤出还不存在于Grid的record。
 * 
 * @param grid
 * @param insert
 * @param property
 * @returns {Array}
 */
function getNonexistentRecord(grid, insert, property){
	var exist = new Array();
	var result = new Array();
	grid.forEachRow(function(row, record, i, grid) {
		if (record != undefined){
			exist[i] = record[property];
		}
	});
	for (var i=exist.length,j=0; j<grid.getInsertedRecords().length; j++) { // 记录resultGrid中在不同批次中已经插入的记录。
		exist[i++] = grid.getInsertedRecords()[j][property];
	}
	var k=0;
	for (var i=0; i<insert.length; i++) {
		flag = true;
		for (var j=0; j<exist.length; j++) {
			if (exist[j] == insert[i][property]) {
				flag = false;
				break;
			}				
		}
		if (flag) {
			result[k++] = insert[i];
		}
	}	
	return result;
}


function getCurrentDate(){
	var now=new Date();
	var year=now.getFullYear();
	var month=now.getMonth()+1;
	var day=now.getDate();
	return new String(year).concat('-').concat(month >= 10 ? month : ('0' + month)).concat('-').concat(day);
};

function createDropdown(target,option) {
	if(!target) return false;
	var t=$(target);
	var menu=t.data("dpmenu");
	if(!menu){
		var menu=$("<ul></ul>");
		menu.addClass("dpmenu");
		t.after(menu);
		t.data("dpmenu",menu);
		for(var i=0;i<option.length;i++) {
			var item=option[i],li=$("<li></li>"),a=$("<a></a>");
			a.html(item.text);
			if(item.href) a.attr("href",item.href);
			li.append(a);
			if(item.childern){};
			menu.append(li);
		}
	}
	var dpmenu=t.parent().find("ul.dpmenu");
	dpmenu.slideDown(100).show();
	t.parent().hover(function(){
	},function(e){
		dpmenu.slideUp(100);
	});
};

function getRandomString(length) {
	length = length||8;
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var randomstring = '';
	for (var i=0; i<length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
    if(! /\d/.test(randomstring)){
        randomstring=getRandomString(length);
    }
	return randomstring;
};

jQuery.fn.serializeQueryObject=function(){
	var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function() {
    	if(this.value && this.name.indexOf('q_')==0) {
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value);
	        } else {
	            o[this.name] = this.value;
	        }
    	}
    });
    return o;
};

jQuery.fn.bindEnterQuery=function(fn){
	var a=this.find('input:text');
	jQuery.each(a,function(){
		jQuery(this).keypress(function(e){
			if(e.keyCode == 13){
				fn();
			}
		});
	});
};

/**
 * 弹出对话框
 * @param id 对话框div属性对应的Id
 * @param width
 * @param height
 */
function showDialog(id, width, height) {
	var div = $('#' + id);
	if (height) {
		div.css('height', height);
	}
	var css = {};
	if (width) {
		css['width'] = width + "px";
		css['left'] = ($(window).width() - width)/2 + "px";
	}
	else {
		
	}
	if (height) {
		css['top'] = ($(window).height() - height)/2 + "px"
	}
	else {
		css['top'] = '20%';
	}
	$.blockUI({ 
		message: div,
		css : css
	}); 
}

/**
 * 设置导航栏样式
 * 
 * @param divid,linkid
 * @return
 */
 function menu_style_set( divid, linkid ) {
		var  div_id = "memu_class_" + divid;
		var link_id = "memu_class_" + linkid;
		
		$("#" + div_id).css('display','block'); 
		$("#" + link_id).addClass('active'); 
}

/**
 * 获取session 登陆账号信息
 * 
 * @param 
 * @return
 */
 function get_login_user() {
	  var get_text = _session.innerHTML;
	  var index1=get_text.indexOf(", <input");
	  if(index1<0)
	  {
		index1=get_text.indexOf(", <INPUT");
	  }
	  var sub_string = get_text.substring(0,index1);
	  var arr = sub_string.split(' ');
	  var a=arr.length;
	  var user = arr[a-1];
	  return user;
}

/**
 * 账号权限判断，并设置导航样式
 * 
 * @param 账号类型，判断类型，导航id，链接id
 * @return
 */
 function check_login_user_acc( user_type, check_type, div_id, link_id ) {
 		var  set_user_type = user_type;
 		var set_check_type = check_type;
 		var     set_div_id = div_id;
 		var    set_link_id = link_id;
		switch( set_check_type ){
			  case "web" :   
			  		if ( set_user_type == 0 || set_user_type == 2 || set_user_type == 4 || set_user_type == 6 ) {  
			  				menu_style_set( set_div_id, set_link_id );
			  				return true;
			  		}
			  		else{ window.location.href = '/cdn/inc/error.inc.php?div=' + div_id + '&link=' +link_id; }
			  		break;
			  case "file" :   
			  		if ( set_user_type == 1 || set_user_type == 2 || set_user_type == 5 || set_user_type == 6 ) {  
			  				menu_style_set( set_div_id, set_link_id );
			  				return true;
			  		}
			  		else{ window.location.href = '/cdn/inc/error.inc.php?div=' + div_id + '&link=' +link_id; }
			  		break;
		  	default : 
		  			window.location.href = '/cdn/inc/error.inc.php?div=' + div_id + '&link=' +link_id;	
		}
}

function get_request() {

	   var url = location.search; //获取url中"?"符后的字串
	   var theRequest = new Object();
	   if ( url.indexOf("?") != -1 ) {
	      var str = url.substr(1);
	      strs = str.split("&");
	      for( var i = 0; i < strs.length; i ++ ) {
	         theRequest[strs[i].split("=")[0]] = unescape( strs[i].split("=")[1] );
	      }
	   }
	   return theRequest;
}

/**
 * 日期格式化
 * 
 * @param 
 * @return
 */
Date.prototype.format = function(format)
{
    var o =
    {
        "M+" : this.getMonth()+1, //month
        "d+" : this.getDate(),    //day
        "h+" : this.getHours(),   //hour
        "m+" : this.getMinutes(), //minute
        "s+" : this.getSeconds(), //second
        "q+" : Math.floor((this.getMonth()+3)/3),  //quarter
        "S" : this.getMilliseconds() //millisecond
    }
    if(/(y+)/.test(format))
    format=format.replace(RegExp.$1,(this.getFullYear()+"").substr(4 - RegExp.$1.length));
    for(var k in o)
    if(new RegExp("("+ k +")").test(format))
    format = format.replace(RegExp.$1,RegExp.$1.length==1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));
    return format;
}