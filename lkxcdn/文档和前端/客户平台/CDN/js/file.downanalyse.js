			$(function(){
				 check_login_user_acc( $("#_login_type").val(), 'file', 4, 46 );
				 var user = get_login_user();
			   var settype = "_init";
			 	 
			 	 var date = new Date();
			 	 date.setDate(date.getDate()-1);
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			 		
			   $.post("../function/file.download.fun.php",{"user":user,"get_type":settype},function(data){
			   			var arr = data.split("***");	   		 
			   		  var channel = eval("("+arr[0]+")");		   		  
			   		  for( count = 0; count < channel.length; count++ ){
			   		  	$("#channel").append('<option value="'+channel[count]+'" selected=selected>'+channel[count]+'</option>');
			   		  }
			   		  
			   		  $("select[id='channel']").multiselect(
			   		 		{ minWidth:187,
			   		 		  noneSelectedText:lang.multiselect.channel.noneSelectedText,
			   		 		  selectedText:lang.multiselect.channel.selectedText,
			   		 		  close:function(){changeTip();},
			   		 		  optionWidth:370}
			   		 	).multiselectfilter();
			
							$("select[id='regionCode']").multiselect(
			   		 		{ minWidth:127,
			   		 		  noneSelectedText:lang.multiselect.region.noneSelectedText,
			   		 		  selectedText:lang.multiselect.region.selectedText,
			   		 		  optionWidth:270}
			   		 	).multiselectfilter();
			   		 	   		 	
			   })
			})
			
			function query(){
				 var        user = get_login_user();
			   var     settype = "_download";
				 var    time_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
				 var   startDate = new Date(start);
				 var     endDate = new Date(end);
				 $("#_startDate").val(start);
				 $("#_endDate").val(end);
				 
				 var mmSec = (endDate.getTime() - startDate.getTime());  
      	         var getOffDays =  mmSec / 3600000 / 24; 
			   if( getOffDays > 31 ){ alert('查询时间段不能超过31天'); return; }
			   
				 var loading  = new ol.loading({id:"htbale"});
				 loading.show();
				
				 time_str = "[\"" + start + "\",\"" + end +"\"]";
				 
				
	          var  channel = document.getElementById("channel");  
			   var channel_str = "[";  
			   for( i = 0; i < channel.length; i++ ){     
			        if(channel.options[i].selected){  
			            channel_str += "\"" + channel.options[i].value + "\",";  
			        }  
			    }
			    channel_str = channel_str.substr(0,channel_str.length-1) + "]";
			    
			    if( channel_str.length <= 2 ){ alert('请先选择完整的查询条件'); loading.hide(); return; }
			    
			    $.post("../function/file.download.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str},function(data){
					var da = eval("("+data+")"); 
			        add_row(da); 	 	
			    	loading.hide();
			    })
			}
			
			function add_row(_arr){
					$("#_downfilelist").empty();
                 
					$("#_downfilelist").append('<tr><th width="50%" style=\"text-align:left;\" class=\"index\">文件名</th><th width="20%" style=\"text-align:center;\">完整传输次数</th><th width="25%" style=\"text-align:center;\">不完整传输次数</th></tr>');
					for( var s in _arr ){
						$("#_downfilelist").append('<tr><td width="50%" style=\"text-align:left;\" class=\"index\">'+s+'</td><td width="20%" style=\"text-align:center;\">'+_arr[s].succ+'</td><td width="25%" style=\"text-align:center;\">'+_arr[s].fail+'</td></tr>');
					}
			}
			
			
			
			
			
			

			
      function parseDate(str_date) {
		               arr = str_date.split(' ');
					    date_str = arr[0];
					    time_str = arr[1];
					    date_arr = date_str.split('-');
					    time_arr = time_str.split(':');
			     date_result = new Date( date_arr[0], date_arr[1] - 1, date_arr[2], time_arr[0], time_arr[1], time_arr[2] );
	     
			     return date_result;
      }
      
     