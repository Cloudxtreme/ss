		$(function(){ 
			 var date = new Date();
			 $("#startDate").val(date.format("yyyy-MM-dd")); 
			 $("#endDate").val(date.format("yyyy-MM-dd")); 
			 menu_style_set( 2, 21 ); 
			 
			})
		
		function query(){
			 var        user = get_login_user();
		   var     settype = "_order";
			 var    time_str = "";
			 var       start = $("#startDate").val();
			 var         end = $("#endDate").val();
			 var      _statu = $("#billStateSelect").val();
			 $("#_startDate").val(start);
			 $("#_endDate").val(end);
			
			 time_str = "[\"" + start + "\",\"" + end +"\"]";
		    
	    $.post("../function/base.order.fun.php",{"user":user,"get_type":settype,"time":time_str,"statues":_statu},function(data){
	    
	    		arr_data = eval("("+data+")");
	    		add_row(arr_data);
	    })
		}
		
		function add_row(_arr){
					$("#order_table").empty();

					$("#order_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="105px" style=\"text-align:center;\">订单类型</th><th width="165px" style=\"text-align:center;\">日期</th><th width="105px" style=\"text-align:center;\">计费方式</th><th width="105px" style=\"text-align:center;\">加速类型</th><th width="65px" style=\"text-align:center;\">状态</th></tr>');
					for( count = 1;count <= _arr.length;count++ ){
						$("#order_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="105px" style=\"text-align:center;\">'+_arr[count-1]["orderType"]+'</td><td width="165px" style=\"text-align:center;\">'+_arr[count-1]["date"]+'</td><td width="105px" style=\"text-align:center;\">'+_arr[count-1]["paidType"]+'</td><td width="100px" style=\"text-align:center;\">'+_arr[count-1]["type"]+'</td><td width="65px" style=\"text-align:center;\">'+_arr[count-1]["statues"]+'</td></tr>');
					}
			}