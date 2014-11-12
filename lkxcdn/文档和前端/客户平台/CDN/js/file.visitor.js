			$(function(){
				 check_login_user_acc( $("#_login_type").val(), 'file', 4, 44 );
			   var user = get_login_user();
			   var settype = "_init";
			   
			   var date = new Date();
			   date.setDate(date.getDate());
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			   
			   $.post("../function/file.visitor.fun.php",{ "user":user,"get_type":settype },function(data){
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
			   var     settype = "_visitor";
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
				 
				 var loading  = new ol.loading({id:"_ip_columnchart_container"});
				 loading.show();
				
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
			    
			    if( zone_str.length <= 2 || channel_str.length <= 2 ){ alert('请先选择完整的查询条件'); loading.hide(); return; }
			    
			    $.post("../function/file.visitor.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str},function(data){
			    		   var arr = data.split("***");
			    	 _visitor_ip = eval("("+arr[0]+")");
					 
			       _request_ip = eval("("+arr[1]+")");
			    		 if(start==end)
				   {
                     for(a in _visitor_ip)
				    {
					   _visitor_ip[a].date=_visitor_ip[a].date+":00"; 
				     }
				   }   
			    		add_row(_request_ip);

			    		createSerialChart(_visitor_ip,'','_ip_columnchart_container');
			    		loading.hide();
			    })
			}
			
			function add_row(_arr){
					$("#_ip_table").empty();

					$("#_ip_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">IP</th><th width="230px" style=\"text-align:center;\">归属地</th><th width="150px" style=\"text-align:center;\">请求数(次)</th><th width="150px" style=\"text-align:center;\">流量(KB)</th></tr>');
					var sum=0;
					var sumflow=0;
					for( count = 1; count <= 10 && count <= _arr.length;count++ ){
						  sum=sum+parseInt(_arr[count-1]["cnt"]);
						    sumflow=sumflow+_arr[count-1]["flow"];
							$("#_ip_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["ip"]+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["ipinfo"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["cnt"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["flow"]+'</td></tr>');
					}
					if(sumflow>0){
					sumflow=sumflow.toFixed(2);
					}
					$("#_ip_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\"></td><td width="230px" style=\"text-align:center;\"></td><td width="230px" style=\"text-align:center;\">总计</td><td width="150px" style=\"text-align:center;\">'+sum+'</td><td width="150px" style=\"text-align:center;\">'+sumflow+'</td></tr>');
			}

			function createSerialChart(chartData,chartTitle,chartDiv) {
		    // SERIAL CHART
		    chart = new AmCharts.AmSerialChart();
		    chart.autoMarginOffset = 0;
		    chart.dataProvider = chartData;
		    chart.categoryField = "date";
		    chart.startDuration = 1;
		
		    // AXES
		    // category
		    var categoryAxis = chart.categoryAxis;
		    categoryAxis.labelRotation = 45; // this line makes category values to be rotated
		    categoryAxis.gridAlpha = 0;
		    categoryAxis.fillAlpha = 1;
		    categoryAxis.fillColor = "#FAFAFA";
		    categoryAxis.gridPosition = "start";
		
		    // value
		    var valueAxis = new AmCharts.ValueAxis();
		    valueAxis.dashLength = 5;
		    valueAxis.title = chartTitle;
		    valueAxis.axisAlpha = 0;
		    chart.addValueAxis(valueAxis);
		
		    // GRAPH
		    var graph = new AmCharts.AmGraph();
		    graph.valueField = "value";
		    graph.colorField = "color";
		    var       start = $("#startDate").val();
			var         end = $("#endDate").val();
			var  str="日期：[[category]]"+" "+"请求：[[value]]个";
			if( start==end)
			{
			   str="时间：[[category]]"+" "+"请求：[[value]]个";
			}
			graph.balloonText = str;
		    graph.type = "column";
		    graph.lineAlpha = 0;
		    graph.fillAlphas = 1;
		    chart.addGraph(graph);
		
		    // WRITE
		    chart.write(chartDiv);
		}
		
		