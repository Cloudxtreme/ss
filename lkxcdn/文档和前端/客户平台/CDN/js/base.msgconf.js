function Show_YKTab(YKTabid_num,YKTabnum){ 
	var tabNum = $('#tabUl > li').size();
	for(var i = 0; i < tabNum; i++ ) { $('#YKTabCon_'+YKTabid_num+i).hide(); }
  document.getElementById("YKTabCon_" + YKTabid_num + YKTabnum ).style.display = "block";
  location.href = location.href.split("#")[0] + "#" + YKTabnum;
}

$(function(){ 
	var        user = get_login_user();
	var     settype = "_init";
	
	Show_YKTab( 1, 0 );
	menu_style_set( 0, 01 ); 
	
	$.post("../function/base.msgconf.fun.php",{ "user":user,"get_type":settype },function(data){
				var res = eval("("+data+")");
				$("#user").val(res[0]["user"]);
				$("#name").val(res[0]["name"]);
				$("#email").val(res[0]["email"]);
				$("#tel").val(res[0]["tel"]);
				$("#conn").val(res[0]["conn"]);
				$("#desc").val(res[0]["desc"]);
	})
	
})

function msg_upd(){
	var        user = get_login_user();
	var     settype = "_msg_upd";
	var       _name = $("#name").val();
	var      _email =	$("#email").val();
	var        _tel =	$("#tel").val();
	var      _conn =	$("#conn").val();
	var       _desc =	$("#desc").val();
	
//	if( _name.length <= 0 ){ alert('请先填写公司'); return; }
	
	$.post("../function/base.msgconf.fun.php",{ "user":user,"get_type":settype,"name":_name,"email":_email,"tel":_tel,"conn":_conn,"desc":_desc },function(data){
				alert("修改成功");
	})
}

$(function(){
		  $("#oldPwd").blur(function(){
			$("#oldInfo").html('');
			$("#confirmInfo").html('');
					var    user = get_login_user();
					var settype = "_msg_check_pwd";
					var  oldPwd = $("#oldPwd").val();
			
					$.ajax({
						url:"../function/base.msgconf.fun.php",
						type:"post",
						data:{ "user":user,"get_type":settype,"pwd":oldPwd },
						success:function(data){
							if( data.indexOf('false')>=0){
								$("#oldInfo").html('输入的密码，与原密码不相符');
								return false;
							}
						}
					});
		});
	$("#confirmPwd").blur(function(){
			$("#confirmInfo").html('');
			var newPwd =  $("#newPwd").val();
			var confirmPwd =  $("#confirmPwd").val();	
				
			if( newPwd != confirmPwd ){
				$("#confirmInfo").html('两次密码输入不一致');
				return false;
			}
	});
});
		
function msg_pwd_upd(){
	if( $("#oldInfo").html() != '' || $("#oldInfo").html() != '' ) { return; }
	$("#oldInfo").html('');
	$("#confirmInfo").html('');
	$("#updInfo").html('');
	$("#newInfo").html('');
	var    user = get_login_user();
    var settype = "_msg_pwd_upd";
    var  oldPwd = $("#oldPwd").val();
	var  newpwd =  $("#newPwd").val();
	var confirmPwd =  $("#confirmPwd").val();	
		if(oldPwd=='')
	{
		$("#oldInfo").html('原密码不能为空');
		return false;
	}
	
	if(newpwd=='')
	{
		$("#updInfo").html('新密码不能为空');
		return false;
	}
	
	if(confirmPwd=='')
	{
		$("#confirmInfo").html('确认密码不能为空');
		return false;
	}
	
	if( newpwd != confirmPwd ){
				$("#confirmInfo").html('两次密码输入不一致');
				return false;
			}
	
		$.ajax({
			 url:"../function/base.msgconf.fun.php",
			type:"post",
			data:{ "user":user,"get_type":settype,"pwd":oldPwd,"newpwd":newpwd },
			success:function(data){
				$("#updInfo").html('修改密码成功!!');
				//if( data.length == 8 ){ $("#updInfo").html('修改密码成功!!'); }
				//else{ $("#updInfo").html('修改过程中，出现错误，请重新操作'); }
			}
		});
}