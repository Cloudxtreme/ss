$(function(){
	 check_login_user_acc( $("#_login_type").val(), 'web', 3, 38 );
	 var user = get_login_user();
           
     
         $("select[id='nettype']").multiselect(
	{ minWidth:120,
	noneSelectedText:lang.multiselect.nettype.noneSelectedText,
	selectedText:lang.multiselect.nettype.selectedText,
	optionWidth:200}
	).multiselectfilter();
   var settype = "_init";
   $.post("../function/base.dns.fun.php",{"user":user,"get_type":settype},function(data){
   	    var arr = data.split("***");
   	    var domainname = eval("("+arr[0]+")");
   		  var arr_dns = eval("("+arr[1]+")");
   		  add_row(arr_dns);
   		  
 		    for( count = 0; count < domainname.length; count++ ){
   		  	 $("#domainname").append('<option value="'+domainname[count]['tablename']+'">'+domainname[count]['domainname']+'</option>');
   		  }
   })
})

function sel()
{   
     var user = get_login_user();
     var settype = "_select";
     var str1="";
      var str2="";
      
    var   nettyped= document.getElementById("nettype");  
     
     var  nettype= "[";  
      for( i = 0; i <  nettyped.length; i++ ){     
      if( nettyped.options[i].selected){  
      nettype += "\"" +  nettyped.options[i].value + "\",";  
    }  
   }
   
  nettype =  nettype.substr(0, nettype.length-1) + "]";
	if(($("#domainname").val()=="") && ( nettype==""))
     {
		 return false;
	 }
	 else
	 {   
	    str1=$("#domainname").val();
	    str2=nettype;
		$.post("../function/base.dns.fun.php",{"user":user,"get_type":settype,"domainname":str1,"nettype":str2},function(data){
   	    var arr = data.split("***");
   	    var domainname = eval("("+arr[0]+")");
   		var arr_dns = eval("("+arr[1]+")");
		//deltablerow();

   		add_row(arr_dns);  
	  
        })
	
		return true;
	 }
	 
}

function deltablerow()
{
   var length=$("#dns_table tr").length; 
   if(length<=1){ 
   return true;
    }else{ 
     $("#dns_table tr:last").remove(); 
    } 
}

function add(){
	 var        user = get_login_user();
         var     settype = "_dns_add";
	 var   tablename = $("#domainname").val();
	 var   domainname = $("#domainname").find("option:selected").text();
	 var   subdomain = $("#domain").val();
	// var     nettype = $("#nettype").val();
	 var          ip = $("#ip").val();
	 var         ttl = $("#ttl").val();
         
         
         var   nettyped= document.getElementById("nettype");  
     
         var  nettype= "[";  
         for( i = 0; i <  nettyped.length; i++ ){     
          if( nettyped.options[i].selected){  
              nettype += "\"" +  nettyped.options[i].value + "\",";  
            }  
          }
         nettype =  nettype.substr(0, nettype.length-1) + "]";
         
    if (tablename == null || tablename.length == 0 || subdomain == null || subdomain.length == 0 || ttl == null || ip == null || ip.length == 0||nettype.length<=2) {
        alert("资料填写不完整！");
        return false;
    }
	if(subdomain.indexOf(" ")>=0)
	{
	  alert("填写域名错误，不能包含空格！");
         return false;	
	}
         if(subdomain!=".")
        {
	if(IsURL(subdomain)==false)
	{
	  alert("填写域名错误，不能包含特殊字符或者格式不正确！");
          return false;	
	}
       if (subdomain.indexOf(domainname) < 0) {
        alert("填写域名错误，格式为：***." + domainname);
        return false;
        }
        }
    
    $.post("../function/base.dns.fun.php",{"user":user,"get_type":settype,"tablename":tablename,"domain":subdomain,"ttl":ttl,"ip":ip,"type":nettype},function(data){
    		if(data.length == 7){ alert("操作成功！");};
    		if(data.length == 8){ alert("操作失败，请重新操作或联系管理员！");};
    		window.location.href = "dns.php"; 
    })
}


function upd(domainid,tablename,nettype,subdomain,domainname,ttl,ip) {
	
    $("#upddomain").val(domainname);
    $("#updsubdomain").val(subdomain);
    $("#updnettype").val(nettype);
    $("#updip").val(ip);
    $("#updttl").val(ttl);
    $("#updid").val(domainid);
    $("#updtablename").val(tablename);

    document.getElementById('updWin').style.display = "block";
}

function save(){
    var    settype = "_dns_upd";
    var domainname = $("#upddomain").val();
    var   domainid = $("#updid").val();
    var  subdomain = $("#updsubdomain").val();
    var    nettype = $("#updnettype").val();
    var         ip = $("#updip").val();
    var        ttl = $("#updttl").val();
    var  tablename = $("#updtablename").val();
    
    if (tablename == null || tablename.length == 0 || subdomain == null || subdomain.length == 0 || ttl == null || ip == null || ip.length == 0) {
        alert("资料填写不完整！");
        return false;
    }
	if(subdomain.indexOf(" ")>=0)
	{
	  alert("填写域名错误，不能包含空格！");
          return false;	
	}
        
    
        if(subdomain!=".")
        {
	  if(IsURL(subdomain)==false)
	 {
	  alert("填写域名错误，不能包含特殊字符或者格式不正确！");
          return false;	
	 }
          if (subdomain.indexOf(domainname) < 0) {
           alert("填写域名错误，格式为：***." + domainname);
           return false;
         }
        }

    $.post("../function/base.dns.fun.php",{"get_type":settype,"tablename":tablename,"id":domainid,"domain":subdomain,"ttl":ttl,"ip":ip,"type":nettype},function(data){
		    if(data.length == 7){ alert("操作成功！");};
    		if(data.length == 8){ alert("操作失败，请重新操作或联系管理员！");};
    		window.location.href = "dns.php"; 
	  })

}

function del(domainid,tablename,nettype) {

    var settype = "_dns_del";
    var    info = "确定要删除吗？";
     
    if(window.confirm(info)){
	      $.post("../function/base.dns.fun.php",{"get_type":settype,"tablename":tablename,"id":domainid,"type":nettype},function(data){
		        if(data.length == 7){ alert("操作成功！");};
    		    if(data.length == 8){ alert("操作失败，请重新操作或联系管理员！");};
    		    window.location.href = "dns.php"; 
	      })
    }
    
}

function add_row(_arr){
	
		$("#dns_table").empty();

		$("#dns_table").append('<tr><th width="60px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">主域名</th><th width="150px" style=\"text-align:center;\">子域名</th><th width="150px" style=\"text-align:center;\">ttl</th><th width="100px" style=\"text-align:center;\">ip</th><th width="100px" style=\"text-align:center;\">网络类型</th><th width="100px" style=\"text-align:center;\">操作</th></tr>');

		for( count = 1;count <= _arr.length;count++ ){
			
			//$("#dns_table").append('<tr><td width="60px" style=\"text-align:center;\" class=\"index\">'+_arr[count-1]['id']+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]['domainname']+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]['domain']+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]['ttl']+'</td><td width="100px" style=\"text-align:center;\">'+_arr[count-1]['ip']+'</td><td width="100px" style=\"text-align:center;\">'+_arr[count-1]['desc']+'</td><td width="100px" style=\"text-align:center;\"><a href="#" onclick="upd('+_arr[count-1]['id']+')">修改</a>  <a href="#" onclick="del('+_arr[count-1]['id']+',\''+_arr[count-1]['tablename']+'\',\''+_arr[count-1]['type']+'\')">删除</a></td></tr>');
		  $("#dns_table").append('<tr><td width="60px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]['domainname']+'</td>\
		       <td width="150px" style=\"text-align:center;\">'+_arr[count-1]['domain']+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]['ttl']+'</td><td width="100px" style=\"text-align:center;\">'+_arr[count-1]['ip']+'</td>\
		       <td width="100px" style=\"text-align:center;\">'+_arr[count-1]['desc']+'</td><td width="100px" style=\"text-align:center;\"><a href="#" \
		       onclick="upd('+_arr[count-1]['id']+',\''+_arr[count-1]['tablename']+'\',\''+_arr[count-1]['type']+'\',\''+_arr[count-1]['domain']+'\',\''+_arr[count-1]['domainname']+'\',\''+_arr[count-1]['ttl']+'\',\''+_arr[count-1]['ip']+'\')">修改</a>\
		       <a href="#" onclick="del('+_arr[count-1]['id']+',\''+_arr[count-1]['tablename']+'\',\''+_arr[count-1]['type']+'\')">删除</a></td></tr>');
		}
}

function closeLoadWin() {
 	document.getElementById('updWin').style.display = "none";
}

function IsURL(str_url){
        var strRegex = "^((https|http|ftp|rtsp|mms|)?://)"
        + "?(([0-9a-z_!~*'().&=+$%-]+: )?[0-9a-z_!~*'().&=+$%-]+@)?" //ftp的user@
        + "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184
        + "|" // 允许IP和DOMAIN（域名）
        + "([0-9a-z_!~*'()-]+\.)*" // 域名- www.
        + "\." 
        + "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // 二级域名
        + "[a-z]{2,6})" // first level domain- .com or .museum
        + "(:[0-9]{1,4})?" // 端口- :80
        + "((/?)|" // a slash isn't required if there is no file name
        + "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$";
        var re=new RegExp(strRegex);
		var gg = /[`?#~$%^&()_+=|\{\};\[\];\"',<>]/g;
        if (re.test(str_url)&&(gg.test(str_url)==false)){
            return (true);
        }else{
            return (false);
        }
}
