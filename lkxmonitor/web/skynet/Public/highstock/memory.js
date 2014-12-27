Highcharts.setOptions({
	global : {
		useUTC : false
	}
});

function createChart(dom, title, data){
	
	chart = new Highcharts.StockChart({
		chart : {
			renderTo: dom,
			zoomType: 'x'
			//events:{ load: getdata }
		},
		rangeSelector : {
			buttons: [{
				count: 6,
				type: 'hour',
				text: '6h'
			}, {
				count: 12,
				type: 'hour',
				text: '12h'
			}, {
				type: 'all',
				text: '1d'
			}],
			inputEnabled: false,
			selected: 0
		},
		title : {
			text : title
		},
		xAxis: {
			type: 'datetime',
			tickPixelInterval : 60,
			gridLineWidth: 1,
			minorTickInterval: 'auto',
			lineColor: '#C0C0C0',
			tickColor: '#C0C0C0'
		},
		yAxis: {
			/*title: {
			    text: '流量值'
			},*/
			min: 0,
			max: 100,
			minorTickInterval: 'auto',
			lineColor: '#000',
			lineWidth: 1,
			tickWidth: 1,
			tickColor: '#C0C0C0'
		},
		tooltip: {
			//valueSuffix: ' Byte/s',
			xDateFormat: '%Y-%m-%d %H:%M',
			//enabled: false,
			shared: true,
			pointFormat: '<span style="color:{series.color}">{series.name}</span>： <b>{point.y}</b> %<br/>'
		},
		plotOptions: {
			line: {
				lineWidth: 1,
				marker: {
					enabled: false
				},
				shadow: false,
				states: {
					hover: {
						lineWidth: 1
					}
				},
				threshold: null
			},area: {
				lineWidth: 1,
				marker: {
					enabled: false
				},
				shadow: false,
				states: {
					hover: {
						lineWidth: 1
					}
				},
				threshold: null
			}
		},
		exporting: {
			enabled: false
		},
		series : [{
			name : "物理内存使用率",
			color: 'rgba(4,206,3,.9)',
			type : 'area',
			data : data,
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
			color: "blue",
			data : data
		}]
	});
	return chart;
}