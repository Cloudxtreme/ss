Highcharts.setOptions({
	global : {
		useUTC : false
	}
});

function createTrafficChart(dom, dw, serie){
	
	var chart = new Highcharts.StockChart({
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
			selected: 1
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
			minorTickInterval: 'auto',
			lineColor: '#000',
			lineWidth: 1,
			tickWidth: 1,
			tickColor: '#C0C0C0'
		},
		tooltip: {
			//valueSuffix: ' %',
			xDateFormat: '%Y-%m-%d %H:%M',
			//enabled: false,
			shared: true,
			pointFormat: '<span style="color:{series.color}">{series.name}</span>： <b>{point.y}</b> '+dw+'<br/>'
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
		series : serie
	});
	return chart;
}

function createChart(dom, dw, serie){
	
	var chart = new Highcharts.StockChart({
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
			//valueSuffix: ' %',
			xDateFormat: '%Y-%m-%d %H:%M',
			//enabled: false,
			shared: true,
			pointFormat: '<span style="color:{series.color}">{series.name}</span>： <b>{point.y}</b> '+dw+'<br/>'
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
		series : serie
	});
	return chart;
}