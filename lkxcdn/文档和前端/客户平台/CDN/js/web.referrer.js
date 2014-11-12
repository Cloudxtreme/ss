			$(function(){
				 check_login_user_acc( $("#_login_type").val(),'web', 3, 36 );
			   var    user = get_login_user();
			   var settype = "_init";
			   
			   var date = new Date();
			   date.setDate(date.getDate());
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			   $.post("../function/web.referrer.fun.php",{ "user":user,"get_type":settype },function(data){
			   			var arr = data.split("***");
			   		  var zone = eval("("+arr[1]+")");
			   		  var channel = eval("("+arr[0]+")");
			   		  
			   		  for( count = 0; count < zone.length; count++ ){
			   		  	$("#regionSimpleCode").append('<option value="'+zone[count]+'">'+zone[count]+'</option>');
			   		  }
			   		  
			   		  for( count = 0; count < channel.length; count++ ){
			   		  	$("#channel").append('<option value="'+channel[count]+'" selected=selected>'+channel[count]+'</option>');
			   		  }
			   		  
			   		  $("select[id='channel']").multiselect(
			   		 		{ minWidth:197,
			   		 		  noneSelectedText:lang.multiselect.channel.noneSelectedText,
			   		 		  selectedText:lang.multiselect.channel.selectedText,
			   		 		  close:function(){changeTip();},
			   		 		  optionWidth:370}
			   		 	).multiselectfilter();
			
							$("select[id='regionSimpleCode']").multiselect(
			   		 		{ minWidth:127,
			   		 		  noneSelectedText:lang.multiselect.region.noneSelectedText,
			   		 		  selectedText:lang.multiselect.region.selectedText,
			   		 		  optionWidth:270}
			   		 	).multiselectfilter();
			   		 	   		 	
			   })
					
			})
			
			function query(){
				 var        user = get_login_user();
			   var     settype = "_referrer";
				 var    time_str = "";
				 var    zone_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
				 var   startDate = new Date(start);
				 var     endDate = new Date(end);
				 
				 var mmSec = (endDate.getTime() - startDate.getTime());  
      	 var getOffDays =  mmSec / 3600000 / 24; 
			   if( getOffDays > 31 ){ alert('查询时间段不能超过31天'); return; } 
				
				 time_str = "[\"" + start + "\",\"" + end +"\"]";
				 
				 zone = document.getElementById("regionSimpleCode");  
			   var zone_str = "[";  
			   for( i = 0; i < zone.length; i++ ){     
			        if( zone.options[i].selected ){  
			            zone_str += "\"" + zone.options[i].value + "\",";  
			        }  
			    }
			    zone_str = zone_str.substr(0,zone_str.length-1) + "]";   
				 
			   channel = document.getElementById("channel");  
			   var channel_str = "[";  
			   for( i = 0; i < channel.length; i++ ){     
			        if(channel.options[i].selected){  
			            channel_str += "\"" + channel.options[i].value + "\",";  
			        }  
			    }
			    channel_str = channel_str.substr(0,channel_str.length-1) + "]";
			    
			    if( zone_str.length <= 2 || channel_str.length <= 2 ){ alert('请先选择完整的查询条件'); return; }
			    
			    $.post("../function/web.referrer.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str},function(data){
			    		   var arr = data.split("***");
			    	_web_request = eval("("+arr[0]+")");
		  	 _search_request = eval("("+arr[1]+")");
			      _key_request = eval("("+arr[2]+")");
			    		
			    		   add_web_row(   _web_request  );
			    		add_search_row( _search_request );
			    		   add_key_row(   _key_request  );

			    })
			}
			
			function add_web_row(_arr){
					$("#web_request_table").empty();

					$("#web_request_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">来源网站</th><th width="150px" style=\"text-align:center;\">流量(MB)</th><th width="150px" style=\"text-align:center;\">请求数</th></tr>');
					for( count = 1; count <= 10 && count <= _arr.length;count++ ){
						$("#web_request_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["web"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["send"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td></tr>');
					}
			}
			
			function add_search_row(_arr){
					$("#search_request_table").empty();

					$("#search_request_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">搜索引擎</th><th width="150px" style=\"text-align:center;\">流量(MB)</th><th width="150px" style=\"text-align:center;\">请求数</th></tr>');
					for( count = 1; count <= 10 && count <= _arr.length;count++ ){
						$("#search_request_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["search"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["send"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td></tr>');
					}
			}
			
			function add_key_row(_arr){
					$("#key_request_table").empty();

					$("#key_request_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">搜索关键字</th><th width="150px" style=\"text-align:center;\">流量(MB)</th><th width="150px" style=\"text-align:center;\">请求数</th></tr>');
					for( count = 1; count <= 10 && count <= _arr.length;count++ ){
						$("#key_request_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["key"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["send"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td></tr>');
					}
			}
			
			
