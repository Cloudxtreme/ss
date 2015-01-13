//获取地址栏参数
//使用方法：
//var strHref = window.location.href;
//if(strHref.getQuery("v")) var param = strHref.getQuery("v");
String.prototype.getQuery = function (name) {
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
	var r = window.location.search.substr(1).match(reg);
	if (r != null) return r[2]; return null;  //unescape()
}

var myseries = {
  
	traffic:
	[{
		name : "流入值",
		color: 'rgba(4,206,3,.9)',
		type : 'area',
		data : [],
		fillColor : {
			linearGradient : {
				x1: 0, 
				y1: 0, 
				x2: 0, 
				y2: 1
			},
			stops : [[0, 'rgba(4,206,3,.9)'], [1, 'rgba(4,206,3,.5)']]
		}
	},{
		name : "流出值",
		color: 'blue',
		type : 'line',
		data : []
	}],
	
	cpu:
	[{
		name : "cpu使用率",
		color: 'rgba(4,206,3,.9)',
		type : 'area',
		data : [],
		fillColor : {
			linearGradient : {
				x1: 0, 
				y1: 0, 
				x2: 0, 
				y2: 1
			},
			stops : [[0, 'rgba(4,206,3,.9)'], [1, 'rgba(4,206,3,.5)']]
		}
	}],
	
	memory:
	[{
		name : "物理内存使用率",
		color: 'rgba(4,206,3,.9)',
		type : 'area',
		data : [],
		fillColor : {
			linearGradient : {
				x1: 0, 
				y1: 0, 
				x2: 0, 
				y2: 1
			},
			stops : [[0, 'rgba(4,206,3,.9)'], [1, 'rgba(4,206,3,.5)']]
		}
	},{
		name : "交换内存使用率",
		color: 'blue',
		type : 'line',
		data : []
	}],
	
	disk:
	[{
		name : "磁盘使用率",
		color: 'rgba(4,206,3,.9)',
		type : 'area',
		data : [],
		fillColor : {
			linearGradient : {
				x1: 0, 
				y1: 0, 
				x2: 0, 
				y2: 1
			},
			stops : [[0, 'rgba(4,206,3,.9)'], [1, 'rgba(4,206,3,.5)']]
		}
	}]
}