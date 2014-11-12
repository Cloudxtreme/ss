			$(function(){
				 check_login_user_acc( $("#_login_type").val(),'web', 3, 33 );
			   var    user = get_login_user();
			   var settype = "_init";
			   
			   var date = new Date();
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			   
			   $.post("../function/web.hit.fun.php",{ "user":user,"get_type":settype },function(data){
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
			   var     settype = "_hit";
				 var    time_str = "";
				 var    zone_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
				 var   chartData = "";
				 var   startDate = new Date(start);
				 var     endDate = new Date(end);
				 
				 var mmSec = (endDate.getTime() - startDate.getTime());  
      	 var getOffDays =  mmSec / 3600000 / 24; 
			   if( getOffDays > 31 ){ alert('查询时间段不能超过31天'); return; } 
				 
				 var loading  = new ol.loading({id:"_hit_piechart_container"});
				 var loading1 = new ol.loading({id:"_container"});
				 loading_show( loading, loading1 );
				
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
			    
			    if( zone_str.length <= 2 || channel_str.length <= 2 ){ alert('请先选择完整的查询条件'); loading_hide( loading, loading1 ); return; }
			    
			    $.post("../function/web.hit.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str},function(data){
			    		   var arr = data.split("***");
			     _request_time = eval("("+arr[0]+")");
			    _province_flow = eval("("+arr[1]+")");
    _request_distributed = eval("("+arr[3]+")");
	             if(start==end)
				 {
                  for(a in _request_time)
				  {
					_request_time[a].date= _request_time[a].date+":00"; 
				  }
				 }
			    		add_row( _province_flow );
           //   calc_reduce_count( _request_distributed );
			    		createPieChart( _request_distributed, '', '_hit_piechart_container' );
			    		createSerialChart( _request_time, '', '_container');
			    		
			    		loading_hide( loading, loading1 );
			    })
			}
			
			function calc_reduce_count( _arr ){
					reduce_count.innerHTML = "";
					if( _arr.length >= 2 ){
						  var count =	(parseInt( _arr[1]["value"] ) - parseInt( _arr[0]["value"] ))/parseInt( _arr[1]["value"] ) * 100;
							reduce_count.innerHTML = count.toFixed(2);
					}
			}
			
			function add_row(_arr){
					$("#_province_table").empty();

					$("#_province_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">省份名称</th><th width="150px" style=\"text-align:center;\">请求数值</th><th width="150px" style=\"text-align:center;\">请求数比例</th><th width="100px" style=\"text-align:center;\"></th></tr>');
					var sum=0;
					for( count = 1; count <= _arr.length; count++ ){
						sum=sum+parseInt(_arr[count-1]["value"]);
						$("#_province_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["province"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["value"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["per"]+'</td><td width="100px" style=\"text-align:center;\"></td></tr>');
					}
					$("#_province_table").append('<tr><td colspan="3"  width="30px" style=\"text-align:center;\" class=\"index\">TOP10总和:'+sum+'</td></tr>');
			}

			function createPieChart(chartData,chartTitle,chartDiv) {
					// PIE CHART
			    chart = new AmCharts.AmPieChart();
			
			    // title of the chart
			    chart.addTitle(chartTitle, 16);
			
			    chart.dataProvider = chartData;
			    chart.titleField = "type";
			    chart.valueField = "value";
			    chart.sequencedAnimation = true;
			    chart.startEffect = "elastic";
			    chart.innerRadius = "30%";
			    chart.startDuration = 2;
			    chart.labelRadius = 15;
			
			    // the following two lines makes the chart 3D
			    chart.depth3D = 10;
			    chart.angle = 15;
			
			    // WRITE                                 
			    //chart.write("chartdiv");
			
			    // WRITE                                 
			    chart.write(chartDiv);
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
				var  str="日期：[[category]]"+" "+"请求数：[[value]]";
				if( start==end)
				{
			    str="时间：[[category]]"+" "+"请求数：[[value]]";
				}
				graph.balloonText = str;
			    graph.type = "column";
			    graph.lineAlpha = 0;
			    graph.fillAlphas = 1;
			    chart.addGraph(graph);
			
			    // WRITE
			    chart.write(chartDiv);
			}
			
			function loading_show( loading, loading1 ){
				loading.show();
				loading1.show();
			}
			
			function loading_hide( loading, loading1 ){
				loading.hide();
				loading1.hide();
			}