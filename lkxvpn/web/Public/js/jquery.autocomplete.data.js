/*jslint  browser: true, white: true, plusplus: true */
/*global $: true */
/* info
 * n - 功能名称
 * s - 简单功能被描述
 * a - 动作 id
 * value - 搜索关键词
 * 
 * */

$(function () {
var autosearch = [
{ 	n:"无线设置", 
	s: "设置密码/网络名称/隐藏无线网",
	a: "set_wifi",
	value: "all 无线设置 wuxianshezhi wifi 无线网 wuxianwang wx"
},
{ 	n:"外网设置", 
	s: "pppoe/dhcp/静态ip",
	a: 'set_network',
	value: 'all 外网设置 waiwangshezhi network internet pppoe 拨号 bohao dhcp 静态ip jingtai 上网 shangwang'
},
{ 	n:"修改密码", 
	s: "建议修改系统默认密码",
	a: 'system_config',
	value: 'all 修改密码 xiugaimima password admin'
},
{ 	n:"连接设备列表", 
	s: "智能限速/防蹭网",
	a: 'devices',
	value: 'all 连接设备列表 lianjieshebei 智能限速 zhinengxiansu 防蹭网 fangcengwang 踢除可疑设备 tichukeyishebei 强制断开 qiangzhiduankai qos'
},
{ 	n:"系统状态", 
	s:"系统升级/联网状况/开机时长", 
	a:"state",
	value: 'all 系统状态 xitongzhuangtai 开机时长 kaijishichang 系统版本 xitongbanben 升级提示 系统升级 rom update upgrade'
},
{ 	n:"实时流量", 
	s:"局域网,拨号,无线流量图", 
	a:"traffic_info",
	value: 'all 流量图 liuliangtu traffic 实时流量 shishiliuliang'
},
{ 	n:"局域网 IP 地址", 
	s:"默认为 192.168.199.1", 
	a:"options",
	p:"network/setup/lan",
	value: 'all 局域网 ip 地址 juyuwang dizhi'
},
{ 	n:"局域网 DHCP 服务", 
	s:"IP范围/IP MAC绑定", 
	a:"options",
	p:"network/setup/dhcp",
	value: 'all dhcp 租用时间 zuyongshijian mac绑定 macbangding'
},
{ 	n:"网络 MTU 设置", 
	s:"", 
	a:"options",
	p:"network/setup/mtu",
	value: 'all mtu'
},
{ 	n:"PPP 高级设置", 
	s:"LCP请求发送间隔", 
	a:"options",
	p:"network/setup/ppp_adv",
	value: 'all LCP请求发送间隔 lcpqingqiufasongjiange pppoe'
},
{ 	n:"MAC 地址克隆", 
	s:"设置外网接口mac地址", 
	a:"options",
	p:"network/setup/mac",
	value: 'all mac地址克隆 macdizhikelong'
},
{ 	n:"UPNP 状态", 
	s:"列表", 
	a:"options",
	p:"network/upnp",
	value: 'all UPNP 状态 upnpzhuangtai'
},
{ 	n:"L2TP/PPTP", 
	s:"VPN 拨号", 
	a:"options",
	p:"network/l2tp",
	value: 'all L2TP PPTP VPN 拨号 bohao'
},
{ 	n:"无线信道", 
	s:"调整无线信道", 
	a:"options",
	p:"wifi/setup/channel",
	value: 'all 无线信道 wuxianxindao 信号强度 xinhaoqiangdu 穿墙模式 chuanqiangmoshi'
},
{ 	n:"无线穿墙模式", 
	s:"调整无线信号强度", 
	a:"options",
	p:"wifi/setup/channel",
	value: 'all 无线信道 wuxianxindao 信号强度 xinhaoqiangdu 穿墙模式 chuanqiangmoshi'
},
{ 	n:"无线 MAC 访问控制", 
	s:"断开可疑设备", 
	a:"options",
	p:"wifi/setup/mac_filter",
	value: 'all 无线 MAC 访问控制 强制断开可疑 duankaikeyishebei MAC地址 macdizhi'
},
{ 	n:"系统时间管理", 
	s:"修改校准系统时间", 
	a:"options",
	p:"system/systime",
	value: 'all 系统时间管理 xitongshijianguanli'
},
{ 	n:"路由器升级管理", 
	s:"请保持路由器固件为最新版本", 
	a:"options",
	p:"system/upgrade",
	value: 'all 路由器升级管理 shengjiguanli update upgrade 固件 gujian'
},
{ 	n:"恢复出厂设置", 
	s:"删除所有本地配置", 
	a:"options",
	p:"system/reboot_reset",
	value: 'all 恢复出厂设置 reset'
},
{ 	n:"路由器诊断", 
	s:"允许远程调试/存储状态/路由器模式", 
	a:"options",
	p:"system/disk",
	value: 'all 路由器诊断 luyouqizhenduan 允许远程调试 yunxuyuanchengtiaoshi 存储状态 cunchuzhuangtai 路由器模式 luyouqimoshi'
},
{ 	n:"云插件", 
	s:"去广告/端口转发/SSH/MYDNS", 
	a:"cloud_guide",
	value: 'all 云插件 去广告 quguanggao 端口转发 duankouzhuanfa SSH MYDNS yunchajian yunpingtai yungongneng port"'
},
{ 	n:"网络诊断", 
	s:"DNS ping http 诊断", 
	a:"net_detect",
	value: 'all 网络诊断 wangluozhenduan network detect http ping"'
}
];
  
// Initialize autocomplete with local lookup:
$('#autocomplete').click(function(){$(this).val("");})
$('#autocomplete').autocomplete({
	lookup: autosearch,
	suggestionlimit: 7,
	noCache: true,
    onSelect: function (suggestion) {
    	open_windows(suggestion.a,suggestion.p);
    },
	formatResult: function (suggestion, currentValue) {
		var info_html = "";
		// 高亮
		var show_suggestion_s = high_light(suggestion.s,currentValue);
		var show_suggestion_n = high_light(suggestion.n,currentValue);
		if (suggestion.s != ""){
			info_html = " <span style='color:#999999;'> - "+show_suggestion_s+"</span>";
		}
		return "<b style='color:#333333'>"+show_suggestion_n+"</b>"+info_html;
	}
});
});

function high_light(str,keyworkd){
	return str.replace(keyworkd,'<span style="color:#45d5ff;">'+keyworkd+'</span>');
}