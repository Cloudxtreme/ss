// JavaScript Documen
$(function(){
	var user =$("#user").val();
	var type=$("#type").val();
	var id=$("#id").val();
	var url="CDNywcx!ini";
	var date = new Date();
	$("#startDate").val(date.format("yyyy-MM-dd")); 
    $("#endDate").val(date.format("yyyy-MM-dd")); 
	$.post(url,{"user":user,"type":type,"id":id},function(data){
			   			var arr = data.split("***");
			   		  var zone = eval("("+arr[1]+")");
			   		  var channel = eval("("+arr[0]+")");
			   		  var isp = eval("("+arr[2]+")");
			   		  
			   		  for( count = 0; count < zone.length; count++ ){
			   		  	$("#regionCode").append('<option value="'+zone[count]+'">'+zone[count]+'</option>');
			   		  }
			   		  
			   		  for( count = 0; count < channel.length; count++ ){
			   		  	$("#channel").append('<option value="'+channel[count]+'">'+channel[count]+'</option>');
			   		  }
			
			   		  for( count = 0; count < isp.length; count++ ){
			   		  	$("#selectedIsps").append('<option value="'+isp[count]+'">'+isp[count]+'</option>');
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
			   		 	   		 	
			   		 	$("select[id='selectedIsps']").multiselect(
			   		 		{ minWidth:140,
									noneSelectedText:lang.multiselect.isp.noneSelectedText,
									selectedText:lang.multiselect.isp.selectedText,
									optionWidth:270}
			   		 	).multiselectfilter();
			 
			   		 
			   })
})

		function query(){
				var user =$("#user").val();
				var type=$("#type").val();
	            var id=$("#id").val();
	            var url="CDNywcx!kdcx";
			    var     settype = "_bandwidth";
				 var    time_str = "";
				 var    zone_str = "";
				 var channel_str = "";
				 var       start = $("#startDate").val();
				 var         end = $("#endDate").val();
					
				 var loading = new ol.loading({id:"chartdiv"});
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
				 
			 var  channel = document.getElementById("channel");  
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
			    
			    $.post(url,{"user":user,"type":type,"id":id,"time":time_str,"channel":channel_str,"zone":zone_str,"isp":isp_str},function(data){
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
			    		createStockChart(chartData);
			    		
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
function createStockChart(chartData)
		  {   
		     
			  $('#chartdiv').highcharts('StockChart', {
				  
			             rangeSelector : {
				         selected : 1
			            },
			          title : {
				      text : '带宽流量'
			           },

			          series : [{
				      name : '带宽流量',
				      data : chartData,
				      type : 'areaspline',
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
					stops : [[0, Highcharts.getOptions().colors[0]], [1, 'rgba(0,0,0,0)']]
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
	  
	  
Date.prototype.format = function(format)
{
 var o = {
 "M+" : this.getMonth()+1, //month
 "d+" : this.getDate(),    //day
 "h+" : this.getHours(),   //hour
 "m+" : this.getMinutes(), //minute
 "s+" : this.getSeconds(), //second
 "q+" : Math.floor((this.getMonth()+3)/3),  //quarter
 "S" : this.getMilliseconds() //millisecond
 }
 if(/(y+)/.test(format)) format=format.replace(RegExp.$1,
 (this.getFullYear()+"").substr(4 - RegExp.$1.length));
 for(var k in o)if(new RegExp("("+ k +")").test(format))
 format = format.replace(RegExp.$1,
 RegExp.$1.length==1 ? o[k] :
 ("00"+ o[k]).substr((""+ o[k]).length));
 return format;
}