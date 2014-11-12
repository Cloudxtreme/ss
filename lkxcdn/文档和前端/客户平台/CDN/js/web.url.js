			$(function(){
				 check_login_user_acc( $("#_login_type").val(),'web', 3, 35 );
			   var    user = get_login_user();
			   var settype = "_init";
			   
			   var date = new Date();
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			   
			   $.post("../function/web.url.fun.php",{ "user":user,"get_type":settype },function(data){
			   			var arr = data.split("***");
			   		  var zone = eval("("+arr[1]+")");
			   		  var channel = eval("("+arr[0]+")");
			   		  
			   		  for( count = 0; count < zone.length; count++ ){
			   		  	$("#regionCode").append('<option value="'+zone[count]+'">'+zone[count]+'</option>');
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
			
							$("select[id='regionCode']").multiselect(
			   		 		{ minWidth:127,
			   		 		  noneSelectedText:lang.multiselect.region.noneSelectedText,
			   		 		  selectedText:lang.multiselect.region.selectedText,
			   		 		  optionWidth:270}
			   		 	).multiselectfilter();
			   		 	
					   	$("select[id='urlType']").val(["all"]);
					   	$("select[id='urlType']").multiselect({
					   		multiple:false,
					   		selectedList:1,
					   		header:lang.multiselect.urlType.header,
					   		checkAllText:lang.multiselect.urlType.checkAllText,
					   		uncheckAllText:lang.multiselect.urlType.uncheckAllText,
					   		noneSelectedText:lang.multiselect.urlType.noneSelectedText,
					   		selectedText:lang.multiselect.urlType.selectedText,
					   		close:function(){setTimeout("controlCustomPattern()",50);},
					   		click:function(){$(this).multiselect("close");},
					   		minWidth:110,optionWidth:110});
					   		
					   	$("select[id='urlPattern']").multiselect({
					   		multiple:false,
					   		selectedList:1,
					   		header:lang.multiselect.urlPattern.header,
					   		checkAllText:lang.multiselect.urlPattern.checkAllText,
					   		uncheckAllText:lang.multiselect.urlPattern.uncheckAllText,
					   		noneSelectedText:lang.multiselect.urlPattern.noneSelectedText,
					   		selectedText:lang.multiselect.urlPattern.selectedText,
					   		click:function(){$(this).multiselect("close");},minWidth:120,optionWidth:120});

			   		 	  controlCustomPattern();
			   })
			
			})
			
			function controlCustomPattern(){
				var val = jQuery("#urlType").val();
				if( val == "5" ){ $(".custom").show(); }
				else{ $(".custom").hide(); }
			}
			
			function query(){
				 var        user = get_login_user();
			   var     settype = "_url_flow";
				 var    time_str = "";
				 var    zone_str = "[";
				 var channel_str = "[";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
				 var    url_type = "";
				 var     url_str = "";
				 var  custom_key = "[";
				 var   startDate = new Date(start);
				 var     endDate = new Date(end);
				 
				 var mmSec = (endDate.getTime() - startDate.getTime());  
      	 var getOffDays =  mmSec / 3600000 / 24; 
			   if( getOffDays > 31 ){ alert('查询时间段不能超过31天'); return; } 
				 
				 var loading  = new ol.loading({id:"_flow_table"});
				 var loading1  = new ol.loading({id:"_count_table"});
				 loading.show();
				 loading1.show();
				
				 time_str = "[\"" + start + "\",\"" + end +"\"]";
				 
				 zone = document.getElementById("regionCode");  
			   for( i = 0; i < zone.length; i++ ){     
			        if( zone.options[i].selected ){  
			            zone_str += "\"" + zone.options[i].value + "\",";  
			        }  
			    }
			    zone_str = zone_str.substr(0,zone_str.length-1) + "]";   
				 
			   channel = document.getElementById("channel");  
			   for( i = 0; i < channel.length; i++ ){     
			        if(channel.options[i].selected){  
			            channel_str += "\"" + channel.options[i].value + "\",";  
			        }  
			   }
			    channel_str = channel_str.substr(0,channel_str.length-1) + "]";
			    
			   url_type = $("#urlType").val(); 
			   if ( url_type == '5' ){
			   		url_type = 'custom';
			   		if( $("#patternValue").val().length <= 0 ){  alert("请先填写关键字");  return;  }
			   		custom_key += "\"" + $("#urlPattern").val() + "\",\"" + $("#patternValue").val() + "\"";
			   }
			   custom_key += "]";
			   
			   if( zone_str.length <= 2 || channel_str.length <= 2 ){ 
			   	  alert('请先选择完整的查询条件'); 
			   	  loading.hide(); 
			   	  loading1.hide(); 
			   	  return; 
			   }
			    
			    $.post("../function/web.url.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str,"url_type":url_type,"keyword":custom_key},function(data){
			    		_request_flow = eval("("+data+")");
			    		add_flow_row(_request_flow);
			    		loading.hide();
			    })
			    
			    settype = '_url_cnt';
			    $.post("../function/web.url.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str,"url_type":url_type,"keyword":custom_key},function(data){
			 	 			_request_count = eval("("+data+")");
			    		add_count_row(_request_count);
			    		loading1.hide();
			    })
			}
			
			function add_flow_row(_arr){
					$("#_flow_table").empty();

					$("#_flow_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">URL</th><th width="150px" style=\"text-align:center;\">总流量 (MB)</th><th width="150px" style=\"text-align:center;\">总请求数</th></tr>');
					for( count = 1;count <= _arr.length && count <= 10;count++ ){
						$("#_flow_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:left;\"><a href="'+_arr[count-1]["url"]+'" target="_blank">'+str_substring(_arr[count-1]["url"])+'</a></td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["flow"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td></tr>');
					}
			}
			
			function add_count_row(_arr){
					$("#_count_table").empty();

					$("#_count_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">URL</th><th width="150px" style=\"text-align:center;\">总请求数</th><th width="150px" style=\"text-align:center;\">总流量 (MB)</th></tr>');
					for( count = 1;count <= 10 && count <= _arr.length;count++ ){
						$("#_count_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:left;\"><a href="'+_arr[count-1]["url"]+'" target="_blank">'+str_substring(_arr[count-1]["url"])+'</a></td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["flow"]+'</td></tr>');
					}
			}
			
			function str_substring( str ){
					var _str = "";
					
					str.length <= 62 ? _str = str : _str = str.substring(0,62) + '...';
					
					return _str;
			}
