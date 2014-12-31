<?php

if($_GET['opt'] === 'print'){
	echo file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=print");
}else if($_GET['opt'] === 'top'){
	echo file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=top");
}else if($_GET['opt'] === 'left'){	
	echo '
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
	';

	$data1 = file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=print");
	$data1 = json_decode($data1, true);

	foreach( $data1 as $k => $v ){
		echo "<tr>
				<td>" . $v['ip'] . "</td>
				<td>" . $v['recv'] . "</td>
				<td>" . $v['send'] . "</td>
				<td>" . $v['inflow'] . "</td>
				<td>" . $v['outflow'] . "</td>
				<td>" . $v['tcpflow'] . "</td>
				<td>" . $v['udpflow'] . "</td>
			</tr>
		";
	}

	echo '</table>';
}else if($_GET['opt'] == 'page'){
	$begin = $_GET['begin'];
	$total = $_GET['total'];
	//file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=print&begin=1&total=20");
	echo file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=print&begin=$begin&total=$total");
}else if($_GET['opt'] === 'gen'){

	// gen这个是有五个信息
	// 总流入报文，总流入流量，总流出报文，总流出流量，总ip数

	echo file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=gen");
}else if($_GET['opt'] === 'attack'){

	// attack的接口要是有数据
	// ip, 攻击状态，攻击类型，峰值包数，峰值流量，攻击时间，持续时间
	// 这几项属性就会有值的了

	echo file_get_contents("http://120.31.133.101/detect.php?name=fscnc&type=attack");
}