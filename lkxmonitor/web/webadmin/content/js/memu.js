var treeNodes =[
		{id:1, pId:0, name:"模版页面", file:"tpl/content"},

		{id:2, pId:0, name:"系统管理", open:true},
		{id:201, pId:2, name:"进程管理", file:"base-sysmgr/system_ps"},
		{id:202, pId:2, name:"账号管理", file:"base-sysmgr/system_users"},
		{id:203, pId:2, name:"磁盘管理", file:"base-sysmgr/system_disk"},
		{id:204, pId:2, name:"日志管理", file:"base-sysmgr/system_log"},
		{id:205, pId:2, name:"软件管理", file:"base-sysmgr/system_soft"},

		{id:3, pId:0, name:"服务管理", open:false},
		{id:301, pId:3, name:"SSH服务", file:"base-service/drag"},
		{id:302, pId:3, name:"其他服务", file:"base-service/drag_super"},

		{id:4, pId:0, name:"网络管理", open:false},
		{id:401, pId:4, name:"防火墙", file:"base-netmgr/net_iptables"},
		{id:402, pId:4, name:"网络配置", file:"base-netmgr/net_mgr"},
		
		{id:5, pId:0, name:"硬件", open:false},
		{id:501, pId:5, name:"系统时间", file:"base-hardware/common"},
		{id:502, pId:5, name:"重启关机", file:"base-hardware/diy_async"},

		{id:6, pId:0, name:"平台管理", open:false},
		{id:601, pId:6, name:"账号管理", file:"base-platform/oneroot"},
		{id:602, pId:6, name:"日志管理", file:"base-platform/oneclick"},

		{id:7, pId:0, name:"其他功能", open:false},
		{id:701, pId:7, name:"命令行", file:"base-other/common"},
		{id:702, pId:7, name:"上传下载", file:"base-other/checkbox"}
	];
