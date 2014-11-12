function login() {
    var user = $('#UserCode').val();
    var  pwd = $('#UserPwd').val();
    var code = $('#randcode').val();
    
    if ( user == "" ) {
        document.getElementById('msg').innerHTML = "请输入用户名！";
        document.all('UserCode').focus();
        return false;
    }
 
    if ( pwd == "" ) {
        document.getElementById('msg').innerHTML = "请输入密码！";
        document.all('UserPwd').focus();
        return false;
    }

    if ( code == "" ) {
        document.getElementById('msg').innerHTML = "请填写验证码！";
        document.all('randcode').focus();
        return false;
    }
    $("#msg").html("正在验证中，请稍候...");
    
    
    $.ajax(
    {
        url    : 'function/login.fun.php',
        data   : { "user":user, "pwd": pwd, "randcode":code },
        type   : "POST",
        success: function (data) {
            if ( data == null || data.length == 0 || data == 0 ) {
                alert("验证失败！");
                document.getElementById('msg').innerHTML = "用户名不存在或密码错";
                return false;
            } else {
                if ( data == "ok" || data.length == 5 ) { window.location = "index.php"; }
                else if ( data == 2 ) { document.getElementById('msg').innerHTML = '验证码错';  return; }
                else { document.getElementById('msg').innerHTML = '用户名不存在或密码错'; }
            }
        },
        error : function ( data ) { alert( data.statusText ); }
    });
}