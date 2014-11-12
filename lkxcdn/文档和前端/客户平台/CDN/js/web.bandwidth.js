			$(function(){
				 check_login_user_acc( $("#_login_type").val(),'web', 3, 31 );
				 var user = get_login_user();
			         var settype = "_init";
			 	 
			 	 var date = new Date();
				 $("#startDate").val(date.format("yyyy-MM-dd")); 
				 $("#endDate").val(date.format("yyyy-MM-dd")); 
			 		
			   $.post("../function/web.bandwidth.fun.php",{"user":user,"get_type":settype},function(data){
			   			var arr = data.split("***");
			   		  var zone = eval("("+arr[1]+")");
			   		  var channel = eval("("+arr[0]+")");
			   		  var isp = eval("("+arr[2]+")");
			   		  
			   		  for( count = 0; count < zone.length; count++ ){
			   		  	$("#regionCode").append('<option value="'+zone[count]+'">'+zone[count]+'</option>');
			   		  }
			   		  
			   		  for( count = 0; count < channel.length; count++ ){
			   		  	$("#channel").append('<option value="'+channel[count]+'" selected=selected>'+channel[count]+'</option>');
			   		  }
			
			   		  for( count = 0; count < isp.length; count++ ){
			   		  	$("#selectedIsps").append('<option value="'+isp[count]+'" selected=selected>'+isp[count]+'</option>');
			   		  }
			   		  
			   		  $("select[id='channel']").multiselect(
			   		 		{ minWidth:187,
			   		 		  noneSelectedText:lang.multiselect.channel.noneSelectedText,
			   		 		  selectedText:lang.multiselect.channel.selectedText,
			   		 		  close:function(){changeTip();},
			   		 		  optionWidth:370
                                                         }
			   		 	).multiselectfilter();
			
					  $("select[id='regionCode']").multiselect(
			   		 		{ minWidth:127,
			   		 		  noneSelectedText:lang.multiselect.region.noneSelectedText,
			   		 		  selectedText:lang.multiselect.region.selectedText,
			   		 		  optionWidth:270}
			   		 	).multiselectfilter();
			   		 	   		 	
			   		 $("select[id='selectedIsps']").multiselect(
			   		 		{ minWidth:140,
							  noneSelectedText:lang.multiselect.isp.noneSelectedText,
							  selectedText:lang.multiselect.isp.selectedText,
							  optionWidth:270}
			   		 	).multiselectfilter();
                                        
                                           
			   		 
			   })
			})
			
			function query(){
				 var        user = get_login_user();
			   var     settype = "_bandwidth";
				 var    time_str = "";
				 var    zone_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
					
				 var loading = new ol.loading({id:"highcon"});
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
			    
			    isp = document.getElementById("selectedIsps");  
			    var isp_str = "[";  
			    for( i = 0; i < isp.length; i++ ){     
			         if(isp.options[i].selected){  
			             isp_str += "\"" + isp.options[i].value + "\",";  
			         }  
			     }
			    isp_str = isp_str.substr(0,isp_str.length-1) + "]";
			    
			    if( zone_str.length <= 2 || channel_str.length <= 2 || isp_str.length <= 2 ){ alert('请先选择完整的查询条件'); loading.hide(); return; }
			    
			    $.post("../function/web.bandwidth.fun.php",{"user":user,"get_type":settype,"time":time_str,"channel":channel_str,"zone":zone_str,"isp":isp_str},function(data){
			    		var chartData = [];
			    		var arr = data.split("***");
			    		arr_time = eval("("+arr[0]+")");
			    		arr_value = eval("("+arr[1]+")");
			    		arr_top = eval("("+arr[2]+")");
			    		add_row(arr_top);
						var now=new Date();
			    		for( count=0;count<arr_time.length;count++ ){
							   var mydate = parseDate(arr_time[count]);
							     if((mydate.getTime()+8*60*60*1000)>(now.getTime()+8*60*60*1000-6*60*1000))
							   {
								   break;
							   }
							   var a=[];
							   a[0]= mydate.getTime()+8*60*60*1000;
							   a[1]=arr_value[count];
			   				   chartData.push(a);
					
			    		}
			    	
			     	    document.getElementById("max_value").innerHTML = arr[4] +  " Mbps";
			    		document.getElementById("max_time").innerHTML = arr[3];
						document.getElementById("th95").innerHTML = arr[5]+  " Mbps";
						document.getElementById("zll").innerHTML = arr[6]+  " GB";
						
			    	//	createStockChart(chartData);
			    	createStockChart(chartData,arr[5])	;
			    	loading.hide();
			    })
			    
			}
			
			function add_row(_arr){
					$("#_top_table").empty();

					$("#_top_table").append('<tr><th width="30px" style=\"text-align:center;\" class=\"index\">序号</th><th width="230px" style=\"text-align:center;\">频道</th><th width="150px" style=\"text-align:center;\">峰值(Mbps)</th><th width="150px" style=\"text-align:center;\">峰值时间点</th><th width="100px" style=\"text-align:center;\">总流量&nbsp;&nbsp;</th></tr>');
					for( count = 1;count <= _arr.length;count++ ){
						$("#_top_table").append('<tr><td width="30px" style=\"text-align:center;\" class=\"index\">'+count+'</td><td width="230px" style=\"text-align:center;\">'+_arr[count-1][0]+'</td><td width="150px" style=\"text-align:center;\">'+_arr[count-1][1]+'</td><td width="150px" style=\"text-align:right;\">'+_arr[count-1][2]+'</td><td width="100px" style=\"text-align:center;\">'+_arr[count-1][3]+' MB</td></tr>');
					}
			}
          function createStockChart(chartData,th)
		  {   
		      var $j = jQuery.noConflict();
			  $('#highcon').highcharts('StockChart', {
				  
			             rangeSelector : {
				         selected : 1
			            },
			          title : {
				      text : '带宽流量'
			           },
                       yAxis : {
				title : {
					text : ''
				},
				min:0, 
				plotLines : [{
					value : th,
					color : 'red',
					dashStyle : 'shortdash',
					width : 2,
					zIndex: 4,
					label : {
						text : '    95th：' +'   '+th+'Mbps'
					}
				}]
			},
			          series : [{
				      name : '带宽流量',
				      data : chartData,
				      type : 'area',
					  color:'#56E481',
				      threshold : null,
				      tooltip : {
					   valueDecimals : 2
				      },
				    fillColor : {
					linearGradient : {
						x1: 0, 
						y1: 0, 
						x2: 0, 
						y2: 1
					},
					stops : [[0, Highcharts.getOptions().colors[1]], [1, 'rgba(88,228,113,1)']]
				     }
			        }]
		        });
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
      
    
      