$(function(){
	 check_login_user_acc( $("#_login_type").val(), 'file', 4, 49 );
	 //menu_style_set( 2, 25 ); 
	 var user = get_login_user();
   var settype = "_init";
   
   $.post("../function/base.domain.fun.php",{"user":user,"get_type":settype},function(data){
   	    var arr = eval("("+data+")");
   		  add_row( arr );
   })
})

function add(){
	 var        user = get_login_user();
   var     settype = "_domain_add";
	 var domain = $("#domain").val();
	 
	 if (domain == null || domain.length == 0) {
	     alert("请填写域名！");
	     return false;
	 }
    
    $.post("../function/base.domain.fun.php",{"user":user,"get_type":settype,"domain":domain},function(data){
    		if(data.length == 7){ alert("操作成功！");};
    		if(data.length == 8){ alert("操作失败，请重新操作或联系管理员！");};
    		window.location.href = "domain.php"; 
    })
}

function del(domainid,tablename,nettype) {

    var settype = "_domain_del";
    var    info = "确定要删除吗？";
     
    if(window.confirm(info)){
	      $.post("../function/base.domain.fun.php",{"get_type":settype,"tablename":tablename,"id":domainid,"type":nettype},function(data){
		        if(data.length == 7){ alert("操作成功！");};
    		    if(data.length == 8){ alert("操作失败，请重新操作或联系管理员！");};
    		    window.location.href = "domain.php"; 
	      })
    }
    
}

function add_row(_arr){
		$("#domain_table").empty();

		$("#domain_table").append('<tr><th width="10%" style=\"text-align:center;\" class=\"index\">序号</th><th width="70%" style=\"text-align:center;\">域名</th><th width="20%" style=\"text-align:center;\">操作</th></tr>');
		for( count = 1;count <= _arr.length;count++ ){
			var tmpstr='';
			if(_arr[count-1]['type']=='80')
			{
			tmpstr='<tr><td width="10%" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="70%" style=\"text-align:center;\">'+_arr[count-1]['hostname']+'</td><td width="20%" style=\"text-align:center;\"></td></tr>';
			}
			else
			{
				tmpstr='<tr><td width="10%" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="70%" style=\"text-align:center;\">'+_arr[count-1]['hostname']+'</td><td width="20%" style=\"text-align:center;\"><a href="#" onclick="del('+_arr[count-1]['id']+',\''+_arr[count-1]['domainname']+'\')">删除</a></td></tr>';
			}
			
			$("#domain_table").append(tmpstr);
		}
}