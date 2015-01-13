Highcharts.setOptions({
	global : {
		useUTC : false
	}
});
function createTrafficChart(dom, dw, series){
	
	chart = new Highcharts.Chart({
		chart : {
			renderTo: dom,
			zoomType: 'x'
			//events:{ load: getdata }
		},
		title : {
			text : ""
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
			title: {
			    text: '流量值'
			},
			min: 0,
			minorTickInterval: 'auto',
			lineColor: '#000',
			lineWidth: 1,
			tickWidth: 1,
			tickColor: '#C0C0C0'
		},
		legend: {
			enabled: false
		},
		tooltip: {
			//valueSuffix: ' Byte/s',
			xDateFormat: '%Y-%m-%d %H:%M',
			//enabled: false,
			shared: true,
			pointFormat: '<span style="color:{series.color}">{series.name}</span>： <b>{point.y}</b> Byte/s<br/>'
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
		series : series
	});
	return chart;
}

function createChart(dom, dw, series){
	
	chart = new Highcharts.Chart({
		chart : {
			renderTo: dom,
			zoomType: 'x'
			//events:{ load: getdata }
		},
		title : {
			text : ""
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
			title: {
			    text: '使用率'
			},
			min: 0,
			max: 100,
			minorTickInterval: 'auto',
			lineColor: '#000',
			lineWidth: 1,
			tickWidth: 1,
			tickColor: '#C0C0C0'
		},
		legend: {
			enabled: false
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
		series : series
	});
	return chart;
}