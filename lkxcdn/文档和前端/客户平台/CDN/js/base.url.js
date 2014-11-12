function Show_YKTab(YKTabid_num,YKTabnum){ 
	var tabNum = $('#tabUl > li').size();
	for(var i = 0; i < tabNum; i++ ) { $('#YKTabCon_'+YKTabid_num+i).hide(); }
  document.getElementById("YKTabCon_" + YKTabid_num + YKTabnum ).style.display = "block";
  location.href = location.href.split("#")[0] + "#" + YKTabnum;
}

$(function(){ 
	Show_YKTab( 1, 3 );
	check_login_user_acc( $("#_login_type").val(), 'web', 3, 39 ); 
	var Request = new Object();
	    Request = get_request();
	var     msg = Request['msg'];
	showtxt.innerHTML = '';
	if ( msg == 'undefined' || msg == null ) { return; }
	
	switch(msg){
		case '1' : showtxt.innerHTML = "找不到上传的文件，请重新操作";
				break;
		case '2' : showtxt.innerHTML = "文件上传成功";
				break;
		case '3' : showtxt.innerHTML = "上传的文件不能超过1MB,请重新上传";
				break;
	  case '4' : showtxt.innerHTML = "登陆超时，请重新登陆";
				break;
		  default :  showtxt.innerHTML = ''; 
			   break;
	}
})

function clear_catch() {
	  var    user = get_login_user();
	  var settype = "_clear_catch";
    var  urlArr = $("#urlArr").val();
    if (urlArr == null || urlArr.length == 0) {
        alert("请输入您要清除的缓存地址！");
        return false;
    }
  /*  if (urlArr.indexOf("，") > 0) {
        alert("请切换到英文输入方式的逗号！");
        return false;
    }*/
    if (urlArr.indexOf("\n") > 0) {
        var Arr = urlArr.split('\n');
        if (Arr.length > 10) {
            alert("最多一次清理10条，请核对后再试！");
            return false;
        }
        for (var i = 0; i < Arr.length; i++) {
            if (Arr[i].indexOf("http://") < 0 || Arr[i].indexOf(".") <= 0) {
                alert("【" + Arr[i] + "】是非法的域名地址，请核对后再试！");
                return false;
            }
        }
    }
 /*
    if (urlArr.indexOf(",") > 0) {
        var Arr = urlArr.split(',');
        if (Arr.length > 10) {
            alert("最多一次清理10条，请核对后再试！");
            return false;
        }
        for (var i = 0; i < Arr.length; i++) {
            if (Arr[i].indexOf("http://") < 0 || Arr[i].indexOf(".") <= 0) {
                alert("【" + Arr[i] + "】是非法的域名地址，请核对后再试！");
                return false;
            }
        }
	
    }	
	*/
    $.ajax(
    {
        url: '../function/base.url.fun.php',
        data: { "url": urlArr, "user": user, "get_type": settype },
        type: "POST",
        dataType: "text",
        success: function (data) {
	          alert("上传完成");
        },
        error: function (data) {
            alert(data.statusText);
        }
    });
}

function clear_path_catch() {
		var    user = get_login_user();
	  var settype = "_clear_path_catch";
    var  urlArr = $("#url").val();
    if (urlArr == null || urlArr.length == 0) {
        alert("请输入您要清除的缓存地址！");
        return false;
    }
    if (urlArr.indexOf("，") > 0) {
        alert("最多一次清理1条，请核对后再试！");
        return false;
    }
    if (urlArr.indexOf("\n") > 0) {
        var Arr = urlArr.split('\n');
        if (Arr.length > 1) {
            alert("最多一次清理1条，请核对后再试！");
            return false;
        }
        for (var i = 0; i < Arr.length; i++) {
            if (Arr[i].indexOf("http://") < 0 || Arr[i].indexOf(".") <= 0) {
                alert("【" + Arr[i] + "】是非法的域名地址，请核对后再试！");
                return false;
            }
        }
    }
    if (urlArr.indexOf(",") > 0) {
        var Arr = urlArr.split(',');
        if (Arr.length > 1) {
            alert("最多一次清理1条，请核对后再试！");
            return false;
        }
        for (var i = 0; i < Arr.length; i++) {
            if (Arr[i].indexOf("http://") < 0 || Arr[i].indexOf(".") <= 0) {
                alert("【" + Arr[i] + "】是非法的域名地址，请核对后再试！");
                return false;
            }
        }
    }
    $.ajax(
    {
        url: '../function/base.url.fun.php',
        data: { "url": urlArr, "user": user, "get_type": settype },
        type: "POST",
        dataType: "text",
        success: function (data) {
            	alert("上传完成");
        },
        error: function (data) {
            alert(data.statusText);
        }
    });
}

function clear_record() {
	  var     user = get_login_user();
	  var  settype = "_clear_record";
    var retvalue = window.confirm("是否确认清理已经执行完成的日志记录？", "提示");
    if (retvalue) {
        $.ajax({
            url: '../function/base.url.fun.php',
        		data: { "user": user, "get_type": settype },
            type: "POST",
            dataType: "text",
            success: function (data) {
									alert("清理完成");                
            },
            error: function (data) {
                alert(data.statusText);
            }
        });
    }
}

function query(){
		var       user = get_login_user();
	  var    settype = "_query";
	  var query_type = $("#query_type").val();
	  
		$.ajax({
        url: '../function/base.url.fun.php',
    		data: { "user": user, "get_type": settype, "type": query_type },
        type: "POST",
        dataType: "text",
        success: function (data) {
        	arr_data = eval("("+data+")");
        	add_row(arr_data);
        },
        error: function (data) {
            alert(data.statusText);
        }
    });
}

function add_row(_arr){
		$("#_url_table").empty();

		$("#_url_table").append('<tr><th width="6%" style=\"text-align:center;\" class=\"index\">序号</th><th width="54%" style=\"text-align:center;\">Url</th><th width="12%" style=\"text-align:center;\">开始时间</th><th width="12%" style=\"text-align:center;\">结束时间</th><th width="8%" style=\"text-align:center;\">状态</th><th width="8%" style=\"text-align:center;\" class=\"index\">类型</th></tr>');
		for( count = 1;count <= _arr.length;count++ ){
			$("#_url_table").append('<tr><td width="6%" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="54%" style=\"text-align:center;\">'+_arr[count-1]["url"]+'</td><td width="12%" style=\"text-align:center;\">'+_arr[count-1]["start_time"]+'</td><td width="12%" style=\"text-align:center;\">'+_arr[count-1]["finish_time"]+'</td><td width="8%" style=\"text-align:center;\">'+_arr[count-1]["status"]+'</td><td width="8%" style=\"text-align:center;\">'+_arr[count-1]["type"]+'</td></tr>');
		}
}




  
  