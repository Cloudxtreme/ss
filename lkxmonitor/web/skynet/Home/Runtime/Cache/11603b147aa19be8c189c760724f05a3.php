<?php if (!defined('THINK_PATH')) exit();?>﻿<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="x-ua-compatible" content="IE=9">
<meta name="format-detection" content="telephone=no">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
<meta http-equiv="expires" content="Wed, 26 Feb 1997 08:21:57 GMT">
<link rel="stylesheet" href="__ROOT__/Public/css/style.css" type="text/css">
<title></title>
<script type="text/javascript" src="__ROOT__/Public/js/jquery-1.8.1.min.js"></script>
<link rel="stylesheet" href="__ROOT__/Public/js/artDialog/skins/blueskin.css?4.1.7" />
<script type="text/javascript" src="__ROOT__/Public/js/artDialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="__ROOT__/Public/js/artDialog/plugins/iframeTools.source.js"></script>
<script type="text/javascript" src="__ROOT__/Public/js/util.js"></script>
<script language="JavaScript"> 
function myrefresh() { 
    //window.location.reload(); 
} 
setTimeout('myrefresh()',5000); //指定1秒刷新一次 
</script>
<script type="text/javascript">
function DelChain(id){
	art.dialog({
		title: false,
		content: "操作提示：确定删除该链路吗？",
		icon: 'question',
		id:"wating_box",
		ok: function(){
			$.ajax({
				url: './link_del',
				dataType: 'json',
				data: { id: id },
				type: "POST",
				success: function (data) {
					if (data.result == 1) {
						art.dialog({content: '操作提示：添加删除该链路！',icon: 'succeed',lock: true,time: 1.5});
						window.location.reload();
					}
					else{
						art.dialog({content: '操作提示：' + data.reason,icon: 'error',lock: false,time: 1.5});
					}
				},
				error: function (data) {
					alert(data.statusText);
				}
			});
			this.close();
			return false;
		},
		okVal: "确定",
		cancel: function(){
			this.close();
			return false;
		}
	}).lock();
}
function stopChain(id){
	art.dialog({
		title: false,
		content: "操作提示：确定停用该链路吗？",
		icon: 'question',
		id:"wating_box",
		ok: function(){
			//alert(mac_format("Ab25d2d214s2"));
			$.ajax({
				url: './link_disable',
				dataType: 'json',
				data: { id: id },
				type: "POST",
				success: function (data) {
					if (data.result == 1) {
						art.dialog({content: '操作提示：添加停用该链路！',icon: 'succeed',lock: true,time: 1.5});
						window.location.reload();
					}
					else{
						art.dialog({content: '操作提示：' + data.reason,icon: 'error',lock: false,time: 1.5});
					}
				},
				error: function (data) {
					alert(data.statusText);
				}
			});
			this.close();
			return false;
		},
		okVal: "确定",
		cancel: function(){
			this.close();
			return false;
		}
	}).lock();
}
function startChain(id){
	art.dialog({
		title: false,
		content: "操作提示：确定启用该链路吗？",
		icon: 'question',
		id:"wating_box",
		ok: function(){
			$.ajax({
				url: './link_enable',
				dataType: 'json',
				data: { id: id },
				type: "POST",
				success: function (data) {
					if (data.result == 1) {
						art.dialog({content: '操作提示：添加启用该链路！',icon: 'succeed',lock: true,time: 1.5});
						window.location.reload();
					}
					else{
						art.dialog({content: '操作提示：' + data.reason,icon: 'error',lock: false,time: 1.5});
					}
				},
				error: function (data) {
					alert(data.statusText);
				}
			});
			this.close();
			return false;
		},
		okVal: "确定",
		cancel: function(){
			this.close();
			return false;
		}
	}).lock();
}
function addChain(){
	var gate = $("#gate").val(), vlan = $("#vlan").val(), 
		source = $("#source").val(), dest = $("#dest").val();
	//检测gate
	if(gate.length == 0 || typeof(gate) == "undefined"){
		$("#gate").focus();
		return false;
	}
	if(!CheckIp(gate)){
		alert("请输入正确的IP地址！");
		$("#gate").focus();
		return false;
	}
	//检测vlan
	if(vlan.length == 0 || typeof(vlan) == "undefined"){
		$("#vlan").focus();
		return false;
	}
	if(!isNumeric(vlan) || parseInt(vlan) < 0 || parseInt(vlan) > 4096){
		alert("请输入正确的vlan值，数值范围：0~4096！");
		$("#vlan").focus();
		return false;
	}
	//检测source
	if(source.length == 0 || typeof(source) == "undefined"){
		$("#source").focus();
		return false;
	}
	if(!CheckIp(source)){
		alert("请输入正确的IP地址！");
		$("#source").focus();
		return false;
	}
	//检测dest
	if(dest.length == 0 || typeof(dest) == "undefined"){
		$("#dest").focus();
		return false;
	}
	if(!CheckIp(dest)){
		alert("请输入正确的IP地址！");
		$("#dest").focus();
		return false;
	}
	$.ajax({
		url: './link_add',
		dataType: 'json',
		data: { gate: gate, vlan: vlan, source: source, dest: dest },
		type: "POST",
		success: function (data) {
			if (data.result == 1) {
				art.dialog({
					content: '操作提示：添加成功，mac地址需要等待一分钟左右才能获取！',
					icon: 'succeed',
					lock: true,
					time: 1.5
				});
				window.location.reload();
			}
			else{
				art.dialog({
					content: data.reason,
					icon: 'error',
					lock: false,
					time: 1.5
				});
			}
		},
		error: function (data) {
			alert(data.statusText);
		}
	});
}

//修改mac地址
function saveMac(evt, id){
	var input = $(evt).parent().children("input").get(0);
	var mac = $(input).val();
	alert(mac + "__" + id);
}
function showMacEdit(evt){
	var btn = $(evt).parent().children("a").get(0);
	$(btn).show();
}
function leaveMacEdit(evt){
	var btn = $(evt).parent().children("a").get(0);
	$(btn).hide();
}
</script>
<style type="text/css">
.header td{
	padding-left:1px; border-right: solid 1px #6daed6; border-left:solid 1px #e7f4fc;
}
.mytb td{
	border-bottom:solid 1px #c3d7e3;
}
</style>
</head>
<body>
<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0" style="font-size:16px;">
	<tr>
		<td height="58px" bgcolor="#1078b5" style="padding-left:20px;"><span style="color: #FFFFFF;font-weight: bold;">现有链路列表</span></td>
	</tr>
	<tr>
    <td valign="top">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="30" background="__ROOT__/Public/img/bg.gif"  style="border-bottom:solid 1px #8db6cf; padding-top:0px; padding-bottom:0px;">
		<table class="header" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
			<td width="5%"><div align="center"><span>状态</span></div></td>
            <td width="17%"><div align="center"><span>下一跳</span></div></td>
            <td width="5%"><div align="center"><span>vlan值</span></div></td>
            <td width="15%"><div align="center"><span>本地地址</span></div></td>
            <td width="15%"><div align="center"><span>目标地址</span></div></td>
			<td width="7%"><div align="center"><span>延时(ms)</span></div></td>
			<td width="10%"><div align="center"><span>发包数</span></div></td>
			<td width="10%"><div align="center"><span>字节数</span></div></td>
			<td width="6%"><div align="center"><span>丢包率</span></div></td>
			<td width="10%" height="22"><div align="center"><span>操作</span></div></td>
            </tr>
        </table>
		</td>
      </tr>
      <tr>
        <td style="padding-top:10px;" align="left">
			<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
            <td>
				<table class="mytb" width="100%" border="0" align="center" cellpadding="6" cellspacing="6">
					<?php if(is_array($link)): $i = 0; $__LIST__ = $link;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><tr>
							<?php if($item["conn"] == 'green'): ?><td width="4%"><div align="center"><img src="__ROOT__/Public/img/lightbulb_green.png" title="当前链路状态良好" alt="当前链路状态良好" /></div></td><?php endif; ?>
							<?php if($item["conn"] == 'yellow'): ?><td width="4%"><div align="center"><img src="__ROOT__/Public/img/lightbulb_yellow.png" title="当前链路状态较差" alt="当前链路状态较差" /></div></td><?php endif; ?>
							<?php if($item["conn"] == 'red'): ?><td width="4%"><div align="center"><img src="__ROOT__/Public/img/lightbulb_red.png" title="当前链路不通" alt="当前链路不通" /></div></td><?php endif; ?>
							<td width="18%"><div align="center"><?php echo ($item["gate"]); ?><br>
								<span style="display:inline;"><input style="width:100px;border-style:none;text-align:center;height:20px;margin-bottom:-6px;font-size:12px;" border="0" type="text" value="<?php echo ($item["mac"]); ?>" tabindex="1" onfocus="showMacEdit(this);" onblur="leaveMacEdit(this);"/><a href="javascript:void(0);" style="display:none;" onclick="saveMac(this,<?php echo ($item["id"]); ?>);"> 修改</a></span></div></td>
							<td width="5%"><div align="center"><?php echo ($item["vlan"]); ?></div></td>
							<td width="15%"><div align="center"><?php echo ($item["source"]); ?></div></td>
							<td width="15%"><div align="center"><?php echo ($item["dest"]); ?></div></td>
							<td width="7%"><div align="center"><?php echo ($item["delay"]); ?></div></td>
							<td width="10%"><div align="center"><?php echo ($item["pkg"]); ?></div></td>
							<td width="10%"><div align="center"><?php echo ($item["flow"]); ?></div></td>
							<td width="6%"><div align="center"><?php echo ($item["lost"]); ?></div></td>
							<?php if($item["stat"] == 'enable'): ?><td width="10%" height="22"><div align="center"><a href="javascript:void(0);" onclick="DelChain(<?php echo ($item["id"]); ?>);">删除</a> | <a href="javascript:void(0);" onclick="stopChain(<?php echo ($item["id"]); ?>);">停用</a></div></td>
								<?php else: ?><td width="15%" height="22" style="border-bottom:solid 1px #c3d7e3;"><div align="center"><a href="javascript:void(0);" onclick="DelChain(<?php echo ($item["id"]); ?>);">删除</a> | <a href="javascript:void(0);" onclick="startChain(<?php echo ($item["id"]); ?>);">启用</a></div></td><?php endif; ?>
						</tr><?php endforeach; endif; else: echo "" ;endif; ?>
					<td width="4%" border="0" style="border-style:none;"></td>
					<td width="18%"><input style="width:100%;border-style:none;text-align:center;height:22px;margin-bottom:-6px;" border="0" type="text" placeholder="下一跳" id="gate"/></td>
					<td width="5%"><input style="width:100%;border-style:none;text-align:center;height:22px;margin-bottom:-6px;" border="0" type="text" placeholder="vlan" id="vlan"/></td>
					<td width="15%"><input style="width:100%;border-style:none;text-align:center;height:22px;margin-bottom:-6px;" border="0" type="text" placeholder="本地地址" id="source"/></td>
					<td width="15%"><input style="width:100%;border-style:none;text-align:center;height:22px;margin-bottom:-6px;" border="0" type="text" placeholder="目标地址" id="dest"/></td>
					<td width="7%" border="0" style="border-style:none;"><div align="center"></div></td>
					<td width="10%" border="0" style="border-style:none;"><div align="center"></div></td>
					<td width="10%" border="0" style="border-style:none;"><div align="center"></div></td>
					<td width="6%" border="0" style="border-style:none;"><div align="center"></div></td>
					<td width="10%" height="22"><div align="center"><a href="javascript:void(0);" onclick="addChain();">添加链路</a></div></td>
				  </tr>
				</table>
			</td>
			</tr>
        </table>
		</td>
      </tr>
    </table>
	</td>
  </tr>
</table>
</body>
</html>