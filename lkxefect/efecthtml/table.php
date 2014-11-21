<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<style>
			* {
				padding: 0;
				margin: 0;
			}

			body {
				font: 14px/1.5 "Microsoft YaHei",Arial,Helvetica,sans-serif,"宋体";
			}

			a {
				text-decoration: none;
			}

			a:hover {
				text-decoration: underline;
			}

			table {
				width:100%; border:1px solid #ccc; border-collapse:collapse;
			}

			table td {
				border:1px solid #ccc; border-collapse:collapse; padding:2px 5px; color:#828282;
			}

			table th {
				font-size: 14px;
				line-height: 30px;
				background: #ccc;
			}

			.wrapper {
				margin: 0 auto;
				width: 1600px;
			}

			.title {
				height: 80px;
				text-align: center;
			}

			.title span {
				font-size: 45px;
			}

			.left {
				float: left;
				width: 50%;
				height: 100%;
				overflow: auto;
				/*background: green;*/
			}

			.right {
				margin-left: 50%;
				height: 100%;
				overflow: auto;
				/*background: blue;*/
			}

			.content {
				margin: 5px 10px;
				text-align: center;
			}

			.content .box {
				margin: 0 2px 20px;
				display: inline-block;
				*display: inline;
				*zoom: 1;
				vertical-align: top;
				width: 45%;
				/*background: green;*/
			}
		</style>

		<script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>

		<script type="text/javascript">

			$(function(){

				goToPage(1);//left

				drawTableTopAll();//right

				// window.leftTimer = setInterval(function(){
				// 	$("#flushBtn").click();
				// }, 5000);

				// window.rightTimer = setInterval(drawTableTopAll, 1000);
			});


			function drawTableTopAll(){
				if(window.rightTimer){
					clearInterval(window.rightTimer);
				}

				// $.get("get_data.php?opt=top", function(data){
				$.get("http://120.31.133.101/detect.php?name=fscnc&type=top", function(data){
					data = eval("(" + data + ")");

					var $content = $(".right .content");
					$content.empty();

					for(var k in data){

						var html = '' + 
							'<div class="box">' + 
								'<table>' + 
									'<tr>' + 
										'<th colspan="2">' + k + ' top 10</th>' + 
									'</tr>' + 
								'</table>' + 
							'</div>' + 
						'';

						var $box = $(html);
						$content.append($box);

						drawTableTop($box.find('table'), data[k]);
					}

					window.rightTimer = setInterval(drawTableTopAll, 1000);
				});
			}

			function drawTableTop($table, data){
				for( var k in data ){
					$tr = '\
						<tr>\
							<td width="50%">' + k + '</td>\
							<td>' + data[k] + '</td>\
						</tr>\
					';

					$table.append($tr);
				}
			}

			function goToPage(begin){
				if(window.leftTimer){
					clearInterval(leftTimer);
				}

				var total = 70;

				// $.get("get_data.php?opt=page&begin=" + begin + "&total=" + total, function(result){
				$.get("http://120.31.133.101/detect.php?name=fscnc&type=print&begin=" + begin + "&total=" + total, function(result){
					var json = eval("(" + result + ")");
					var count = json.total;
					var data = json.data;

					var $table = $("#table1");
					$table.children(':not(:eq(0))').remove();

					for(var i = 0; i < data.length; i++){
						var $tr = '\
							<tr>\
								<td>' + data[i].ip + '</td>\
								<td>' + data[i].recv + '</td>\
								<td>' + data[i].send + '</td>\
								<td>' + data[i].inflow + '</td>\
								<td>' + data[i].outflow + '</td>\
								<td>' + data[i].tcpflow + '</td>\
								<td>' + data[i].udpflow + '</td>\
							</tr>\
						';

						$table.append($tr);
					}

					//var pageTotal = count % total == 0 ? count / total : count / total + 1;
					//var pageNow = begin / total + 1;
					var pageTotal = Math.ceil(count / total);
					var pageNow = Math.floor(begin / total) + 1;

					var next = begin + total > count ? (pageTotal - 1) * total + 1 : begin + total;
					var prev = begin - total < 1 ? 1 : begin - total;

					var $tr = '\
						<tr>\
							<td colspan="7" class="pager">\
								<a id="flushBtn" style="float:left; padding-left: 5px;" href="javascript:void(0);" onclick="goToPage(' + begin + ');">刷新</a>\
								<a style="margin:0 5px;" href="javascript:void(0);" onclick="goToPage(' + 1 + ');">首页</a>\
								<a style="margin:0 5px;" href="javascript:void(0);" onclick="goToPage(' + prev + ');">上一页</a>\
								' + pageNow + '/' + pageTotal + '\
								<a style="margin:0 5px;" href="javascript:void(0);" onclick="goToPage(' + next + ');">下一页</a>\
								<a style="margin:0 5px;" href="javascript:void(0);" onclick="goToPage(' + ((pageTotal - 1) * total + 1) + ');">尾页</a>\
							</td>\
						</tr>\
					';

					$table.append($tr);


					window.leftTimer = setInterval(function(){
						$("#flushBtn").click();
					}, 1000);
				});
			}

			function clickPager(which){
				$(".pager").children('a').eq(which).click();
			}

		</script>
	</head>
	<body>
		<style type="text/css">
			.quick_box {
				position: fixed;
				width: 62px;
				left: -62px;
				top: 30%;
				border-right: 8px solid #87CEEB;

				-webkit-border-top-right-radius: 6px;
				-moz-border-top-right-radius: 6px;
				border-top-right-radius: 6px;
				-webkit-border-bottom-right-radius: 6px;
				-moz-border-bottom-right-radius: 6px;
				border-bottom-right-radius: 6px;

				-webkit-box-shadow: 5px 5px 5px #888888;
				-moz-box-shadow: 5px 5px 5px #888888;
				box-shadow: 5px 5px 5px #888888;

				-webkit-transition: all .5s ease;
				-moz-transition: all .5s ease;
				transition: all .5s ease;
			}

			.quick_box span {
				width: 42px;
				display: inline-block;
				padding: 5px 10px;
				text-align: center;
				background: #F5F5F5;
				cursor: pointer;
				border-bottom: 1px solid #87CEEB;

				-webkit-transition: all .5s ease;
				-moz-transition: all .5s ease;
				transition: all .5s ease;
			}

			.quick_box span.last {
				border-bottom: none;
			}

			.quick_box:hover {
				left: 0;
			}

			.quick_box span:hover {
				background: #87CEEB;
			}

			@media screen and (min-width:1705px) {
			  	.quick_box {
			  		left: 0;
				}
			}
		</style>

		<div class="quick_box">
			<span onclick="clickPager(0);" onselectstart="return false;">刷&nbsp;&nbsp;&nbsp;新</span>
			<span onclick="clickPager(1);" onselectstart="return false;">首&nbsp;&nbsp;&nbsp;页</span>
			<span onclick="clickPager(2);" onselectstart="return false;">上一页</span>
			<span onclick="clickPager(3);" onselectstart="return false;">下一页</span>
			<span class="last" onclick="clickPager(4);" onselectstart="return false;">尾&nbsp;&nbsp;&nbsp;页</span>
		</div>

		<div class="wrapper">
			<div class="title">
				<span>睿江检测中心</span>
			</div>

			<div class="left">
				<div class="content">
					<table id="table1">
						<tr>
							<th>ip</th>
							<th>recv</th>
							<th>send</th>
							<th>inflow</th>
							<th>outflow</th>
							<th>tcpflow</th>
							<th>udpflow</th>
						</tr>
					</table>
				</div>
			</div>

			<div class="right">
				<div class="content">
					<!--
					<div class="box">
						<table id="recv">
							<tr>
								<th colspan="2">recv top 10</th>
							</tr>
						</table>
					</div>

					<div class="box">
						<table id="send">
							<tr>
								<th colspan="2">send top 10</th>
							</tr>
						</table>
					</div>
					-->
				</div>
			</div>

		</div>
	</body>
</html>
