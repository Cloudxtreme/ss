$(function(){
	 check_login_user_acc(  $("#_login_type").val(), 'file',  4, 48  );
	 
   var date  = new  Date();
	 $("#startDate").val(date.format("yyyy-MM-dd"));
	 $("#endDate").val(date.format("yyyy-MM-dd"));
})


function query() {
    var       user = get_login_user();
    var    settype = "_file";
    var start_date = $("#startDate").val();
    var   end_date = $("#endDate").val();
    var   filename = $("#filename").val();
     
    $.post("../function/base.file.fun.php",{"get_type":settype,"user": user,"startDate":start_date,"endDate":end_date,"filename":filename},function(data){
        arr_data = eval("("+data+")");
	      add_row(arr_data);
    })

    
}

function add_row(_arr){
		$("#file_table").empty();

		$("#file_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="110px" style=\"text-align:center;\">文件名</th><th width="80px" style=\"text-align:center;\">文件大小(字节)</th><th width="110px" style=\"text-align:center;\">MD5</th><th width="60px" style=\"text-align:center;\">操作时间</th><th width="60px" style=\"text-align:center;\">处理状态</th><th width="60px" style=\"text-align:center;\">进度</th><th width="60px" style=\"text-align:center;\">类型</th></tr>');
		for( count = 1;count <= _arr.length;count++ ){
		  $("#file_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="110px" style=\"text-align:left;\">'+_arr[count-1]['filename']+'</td>\
		       <td width="80px" style=\"text-align:center;\">'+convert(_arr[count-1]['filesize']) +'</td><td width="110px" style=\"text-align:center;\">'+_arr[count-1]['md5']+'</td><td width="60px" style=\"text-align:center;\">'+_arr[count-1]['lastmodify']+'</td><td width="60px" style=\"text-align:center;\">'+_arr[count-1]['status']+'</td>\
		       <td width="60px" style=\"text-align:center;\">'+_arr[count-1]['percent']+'</td><td width="60px" style=\"text-align:center;\">'+_arr[count-1]['type']+'</td></tr>');
		}
}

function convert(str) 
{ 
 str.trim();
 var len=str.length;
 var nums=0;
 if(len<=3)
 {
	 return str;
 }
 else
 {
	 if(len%3==0)
	 {
		 nums=len/3-1;
	 }
	 else
	 {
		 nums=parseInt(len/3);
	 }
 }
 for(var i=1;i<=nums;i++)
 {
	tmpstr1=","+str.substring((len-3*i)-(i-1),len);
	tmpstr2=str.substring(0,(len-3*i)-(i-1));
	str=tmpstr2+tmpstr1;
	len=len+1;
 }
 return str;
}
String.prototype.trim = function () {
 return this .replace(/^\s\s*/, '' ).replace(/\s\s*$/, '' );
}