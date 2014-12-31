<?php
	header('Location: http://120.31.133.103/pingxi/');
	exit;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<!-- <meta http-equiv="refresh" content="8640"> -->

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
				/*width: 1600px;*/
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
			}

			.right {
				margin-left: 50%;
				height: 100%;
				overflow: auto;
			}

			.content {
				margin: 5px 10px;
				text-align: center;
			}

			.content .down_box {
				margin-bottom: 25px;
			}

			.content .box {
				margin: 0 2px 20px;
				display: inline-block;
				*display: inline;
				*zoom: 1;
				vertical-align: top;
				width: 45%;
			}
		</style>

		<script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>

		<script type="text/javascript">

			var genTableTimer;
			var attackTableTimer;
			var rightTimer;

			$(function(){

				//goToPage(1);//left
				genTable();
				attackTable();

				drawTableTopAll();//right

			});

			function genTable(){
				if(genTableTimer){
					clearInterval(genTableTimer);
				}

				$.get("get_data.php?opt=gen", function(data){
					if(data == null){
						return;
					}

					data = eval("(" + data + ")");

					var firstRow = $('<tr></tr>');
					var secondRow = $('<tr></tr>');
					for(var k in data){
						firstRow.append('<th>' + k + '</th>');
						secondRow.append('<td>' + data[k] + '</td>');
					}

					$("#genTable").empty().append(firstRow).append(secondRow);

					genTableTimer = setInterval(genTable, 1000);
				});
			}

			function attackTable(){
				if(attackTableTimer){
					clearInterval(attackTableTimer);
				}

				$.get("get_data.php?opt=attack", function(data){
					if(data == null){
						return;
					}

					data = eval("(" + data + ")");

					var $header = $('<tr></tr>');
					for(var k in data[0]){
						$header.append('<th>' + k + '</th>');
					}

					var $t = $("#attackTable").empty().append($header);

					for(var i = 0; i < data.length; i++){
						var $row = $('<tr></tr>');

						for(var k in data[i]){
                            if(k == 'ip'){
                                var pxUrl = 'http://192.168.85.166/pingxi/index.php/Monitor/ip_detail?ip=' + data[i][k];

							    $row.append('<td><a href="' + pxUrl + '" target="_self">' + data[i][k] + '</a></td>');
							    continue;
                            }

							$row.append('<td>' + data[i][k] + '</td>');
						}

						$t.append($row);
					}

					attackTableTimer = setInterval(attackTable, 5000);
				});
			}

			function drawTableTopAll(){
				if(rightTimer){
					clearInterval(rightTimer);
				}

				$.get("get_data.php?opt=top", function(data){
				//$.get("http://120.31.133.101/detect.php?name=fscnc&type=top", function(data){
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

					rightTimer = setInterval(drawTableTopAll, 1000);
				});
			}

			function drawTableTop($table, data){
				for( var k in data ){
					var pxUrl = 'http://192.168.85.166/pingxi/index.php/Monitor/ip_detail?ip=' + k;

					$tr = '\
						<tr>\
							<td width="50%"><a href="' + pxUrl + '" target="_self">' + k + '</a></td>\
							<td>' + data[k] + '</td>\
						</tr>\
					';

					$table.append($tr);
				}
			}

		</script>
	</head>
	<body>

		<div class="wrapper">
			<div class="title">
				<span>睿江监测中心</span>
			</div>

			<div class="left">
				<div class="content">
					<div class="down_box">
						<table id="genTable">
						</table>
					</div>

					<div class="down_box">
						<table id="attackTable">
							<tr>
								<th>ip</th>
								<th>攻击状态</th>
								<th>攻击类型</th>
								<th>峰值包数</th>
								<th>峰值流量</th>
								<th>攻击时间</th>
								<th>持续时间</th>
							</tr>
						</table>
					</div>
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
