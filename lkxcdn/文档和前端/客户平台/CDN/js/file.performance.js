			$(function(){
				 check_login_user_acc( $("#_login_type").val(), 'file', 4, 46 );
				 var user = get_login_user();
			   var settype = "_init";
			 	 
			 	 var date = new Date();
			 	 date.setDate(date.getDate());
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			 		
			   $.post("../function/file.performance.fun.php",{"user":user,"get_type":settype},function(data){
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
			   var     settype = "_performance";
				 var    time_str = "";
				 var    zone_str = "";
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
			   
				 var loading  = new ol.loading({id:"chartdiv"});
				 loading.show();
				
				 time_str = "[\"" + start + "\",\"" + end +"\"]";
				 
				 zone = document.getElementById("regionCode");  
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
			    
			    $.post("../function/file.performance.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str},function(data){
			    		var chartData = [];
			    		var _fieldmappings = [];
			    		var _value = "";
			    		var arr = data.split("***");
			    		arr_channel = eval("("+arr[0]+")");
			    		arr_time = eval("("+arr[1]+")");
			    		arr_data = eval("("+arr[2]+")");
			    		arr_top = eval("("+arr[3]+")");
			    		arr_request = eval("("+arr[4]+")");
			    		var arr_length = arr_channel.length;
			    //		alert(arr_data[0][0]);return;
			    		add_row(arr_top);
			    		add_request_count_row(arr_request);
			    		createStockChart( arr_data, arr_channel, arr_time );
			    		loading.hide();
			    })
			}
			
			function add_row(_arr){
					$("#_channel_rate_table").empty();

					$("#_channel_rate_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">频道</th><th width="150px" style=\"text-align:center;\">平均下载速度(KBps)</th><th width="150px" style=\"text-align:center;\">地区详细</th></tr>');
					for( count = 1; count <= _arr.length; count++ ){
						$("#_channel_rate_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["channel"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["rate"]+'</td><td width="150px" style=\"text-align:center;\"><a href="#" onclick=\'get_province(\"'+_arr[count-1]["channel"]+'\")\'>地区统计</a></td></tr>');
					}
			}
			
			function add_provice_row(_arr){
					$("#_provice").empty();

					$("#_provice").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">省份</th><th width="150px" style=\"text-align:center;\">平均下载速度(KBps)</th></tr>');
					for( count = 1; count <= _arr.length; count++ ){
						$("#_provice").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["province"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["rate"]+'</td></tr>');
					}
			}
			
			function add_request_count_row(_arr){
					$("#_channel_request_count").empty();

					$("#_channel_request_count").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">频道名称</th><th width="150px" style=\"text-align:center;\">0-50</th><th width="150px" style=\"text-align:center;\">50-100</th><th width="150px" style=\"text-align:center;\">100-200</th><th width="150px" style=\"text-align:center;\">200-500</th><th width="150px" style=\"text-align:center;\">500-1024</th><th width="150px" style=\"text-align:center;\">1024以上</th></tr>');
					for( count = 1; count <= _arr.length; count++ ){
						 $("#_channel_request_count").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1]["channel"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["fir"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["sed"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["thr"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["for"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["five"]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1]["six"]+'</td></tr>');
					}
			}
			
			function get_province(channel){
					var        user = get_login_user();
			  	var     settype = "_get_province";
			  	var    time_str = "";
				  var       start = $("#_startDate").val();
				  var         end = $("#_endDate").val();
				
				  time_str = "[\"" + start + "\",\"" + end +"\"]";
				  span_channel.innerHTML = channel;
				 
					$.post("../function/file.performance.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel},function(data){
							 var _provice = eval("("+data+")");
							 add_provice_row( _provice );
					})
			}

			function createStockChart( arr_data, arr_channel, arr_time ) {
					var chartData1 = [];
					var chartData2 = [];
					var chartData3 = [];
					var chartData4 = [];
					
					//for( count = 0; count < 4; count++ ){
					for( count = 0; count < arr_time.length; count++ ){
							var mydate = parseDate( arr_time[count] );
				
	   					chartData1.push({
		   						date : mydate,
		  						value: arr_data[0][count]
							});
							
							chartData2.push({
		   						date : mydate,
		  						value: arr_data[1][count]
							});
							
							chartData3.push({
		   						date : mydate,
		  						value: arr_data[2][count]
							});
							
							chartData4.push({
		   						date : mydate,
		  						value: arr_data[3][count]
							});
			    }

			    var chart = new AmCharts.AmStockChart();
			    chart.pathToImages = "http://www.amcharts.com/lib/images/";
			    
			    var categoryAxesSettings = new AmCharts.CategoryAxesSettings();
	        //定义显示的最小时间段，前提是X轴是Date对象
	        categoryAxesSettings.parseDates = true;
	        categoryAxesSettings.minPeriod = "hh";
	        chart.categoryAxesSettings = categoryAxesSettings;
				
			    // DATASETS //////////////////////////////////////////
			    // create data sets first
			    var dataSet1 = new AmCharts.DataSet();
			    dataSet1.title = arr_channel[0];
			    dataSet1.fieldMappings = [{
			        fromField: "value",
			        toField: "value"}];
			    dataSet1.dataProvider = chartData1;
			    dataSet1.categoryField = "date";
			
			    var dataSet2 = new AmCharts.DataSet();
			    dataSet2.title = arr_channel[1];
			    dataSet2.fieldMappings = [{
			        fromField: "value",
			        toField: "value"}];
			    dataSet2.dataProvider = chartData2;
			    dataSet2.categoryField = "date";
			
			    var dataSet3 = new AmCharts.DataSet();
			    dataSet3.title = arr_channel[2];
			    dataSet3.fieldMappings = [{
			        fromField: "value",
			        toField: "value"}];
			    dataSet3.dataProvider = chartData3;
			    dataSet3.categoryField = "date";
			
			    var dataSet4 = new AmCharts.DataSet();
			    dataSet4.title = arr_channel[3];
			    dataSet4.fieldMappings = [{
			        fromField: "value",
			        toField: "value"}];
			    dataSet4.dataProvider = chartData4;
			    dataSet4.categoryField = "date";
			
			    // set data sets to the chart
			    chart.dataSets = [dataSet1, dataSet2, dataSet3, dataSet4];
			    chart.mainDataSet = dataSet3;
			
			    // PANELS ///////////////////////////////////////////                                                  
			    // first stock panel
			    var stockPanel1 = new AmCharts.StockPanel();
			    stockPanel1.showCategoryAxis = false;
			    stockPanel1.title = "Value";
			    stockPanel1.percentHeight = 60;
			
			    // graph of first stock panel
			    var graph1 = new AmCharts.StockGraph();
			    graph1.type = "smoothedLine";
			    graph1.valueField = "value";
			    graph1.comparable = true;
			    graph1.compareField = "value";
			    stockPanel1.addStockGraph(graph1);
			
			    // create stock legend                
			    stockPanel1.stockLegend = new AmCharts.StockLegend();

			    // set panels to the chart
			    chart.panels = [stockPanel1];
			
			    // OTHER SETTINGS ////////////////////////////////////
			    var sbsettings = new AmCharts.ChartScrollbarSettings();
			    sbsettings.graph = graph1;
			    sbsettings.usePeriod = "ww";
			    chart.chartScrollbarSettings = sbsettings;
			
			
			    // PERIOD SELECTOR ///////////////////////////////////
			    var periodSelector = new AmCharts.PeriodSelector();
			    periodSelector.position = "left";
			    periodSelector.periods = [{
			        period: "DD",
			        count: 10,
			        label: "10 days"},
			    {
			        period: "MM",
			        selected: true,
			        count: 1,
			        label: "1 month"},
			    {
			        period: "YYYY",
			        count: 1,
			        label: "1 year"},
			    {
			        period: "YTD",
			        label: "YTD"},
			    {
			        period: "MAX",
			        label: "MAX"}];
			    chart.periodSelector = periodSelector;
			
			
			    // DATA SET SELECTOR
			    var dataSetSelector = new AmCharts.DataSetSelector();
			    dataSetSelector.position = "left";
			    chart.dataSetSelector = dataSetSelector;
			
			    chart.write('chartdiv');
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
      
      function judgedatetime(type) { 
      	var getOffDays = function(startDate, endDate) { 
      		 var mmSec = (endDate.getTime() - startDate.getTime()); //得到时间戳相减得到以毫秒为单位的差  
      	   return (mmSec / 3600000 / 24); //单位转换为天并返回  
      	};
      	 
      	var start = document.getElementById('txtcpStart').value; 
      	var end = document.getElementById('txtcpEnd').value; 
      	if(start == "" || end == "") { 
      		alert('开始时间或结束时间不能为空'); 
      		event.returnValue = false; 
      	} 
      		var startyear = start.split("-")[0]; 
      		var startmonth = start.split("-")[1]; 
      		var startday = start.split("-")[2]; 
      		
      		var endyear = end.split("-")[0]; 
      		var endmonth = end.split("-")[1]; 
      		var endday = end.split("-")[2]; 
      		var limit = 60; 
      		if(type == 'yaji') { limit = 45; } 
      			else { } 
      		if(getOffDays(new Date(startyear,startmonth,startday), new Date(endyear,endmonth,endday)) > limit) { alert('跨度不能超过'+limit+'天'); event.returnValue = false; } 
      	}