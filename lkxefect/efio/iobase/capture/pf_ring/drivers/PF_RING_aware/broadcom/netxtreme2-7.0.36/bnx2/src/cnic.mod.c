#include <linux/module.h>
#include <linux/vermagic.h>
#include <linux/compiler.h>

MODULE_INFO(vermagic, VERMAGIC_STRING);

struct module __this_module
__attribute__((section(".gnu.linkonce.this_module"))) = {
 .name = KBUILD_MODNAME,
 .init = init_module,
#ifdef CONFIG_MODULE_UNLOAD
 .exit = cleanup_module,
#endif
 .arch = MODULE_ARCH_INIT,
};

static const struct modversion_info ____versions[]
__used
__attribute__((section("__versions"))) = {
	{ 0x4164b1f3, "module_layout" },
	{ 0x32e373ad, "pci_bus_read_config_byte" },
	{ 0x65e75cb6, "__list_del_entry" },
	{ 0x5a34a45c, "__kmalloc" },
	{ 0xf9a482f9, "msleep" },
	{ 0xe8b05cd2, "__alloc_workqueue_key" },
	{ 0xc8b57c27, "autoremove_wake_function" },
	{ 0xc1e7dd1a, "vlan_dev_vlan_id" },
	{ 0x79aa04a2, "get_random_bytes" },
	{ 0xf15faef6, "malloc_sizes" },
	{ 0xc7a4fbed, "rtnl_lock" },
	{ 0xaba259f1, "_raw_read_lock" },
	{ 0xa85ee2e0, "dst_release" },
	{ 0x87a45ee9, "_raw_spin_lock_bh" },
	{ 0x63ecad53, "register_netdevice_notifier" },
	{ 0xccce8c1, "uio_unregister_device" },
	{ 0x859b97c8, "pci_dev_get" },
	{ 0x84010cfb, "x86_dma_fallback_dev" },
	{ 0xeae3dfd6, "__const_udelay" },
	{ 0x9e1bdc28, "init_timer_key" },
	{ 0x1b9e1323, "mutex_unlock" },
	{ 0x3c2c5af5, "sprintf" },
	{ 0x7d11c268, "jiffies" },
	{ 0x343a1a8, "__list_add" },
	{ 0xfe769456, "unregister_netdevice_notifier" },
	{ 0x27c33efe, "csum_ipv6_magic" },
	{ 0xe174aa7, "__init_waitqueue_head" },
	{ 0xe1bc7ede, "del_timer_sync" },
	{ 0xafc285a8, "current_task" },
	{ 0x27e1a049, "printk" },
	{ 0x479c3c86, "find_next_zero_bit" },
	{ 0xfaef0ed, "__tasklet_schedule" },
	{ 0xb4390f9a, "mcount" },
	{ 0x16305289, "warn_slowpath_null" },
	{ 0x54841d07, "mutex_lock" },
	{ 0x16592094, "_raw_write_lock" },
	{ 0x4bfddf20, "destroy_workqueue" },
	{ 0xa7ef149c, "netdev_printk" },
	{ 0x521445b, "list_del" },
	{ 0x9545af6d, "tasklet_init" },
	{ 0xc2cdbf1, "synchronize_sched" },
	{ 0xd6b8e852, "request_threaded_irq" },
	{ 0x1852b497, "init_net" },
	{ 0xc72b3c19, "flush_workqueue" },
	{ 0xe6fe4af6, "vlan_dev_real_dev" },
	{ 0xa315733e, "uio_event_notify" },
	{ 0x868784cb, "__symbol_get" },
	{ 0xf11543ff, "find_first_zero_bit" },
	{ 0xe523ad75, "synchronize_irq" },
	{ 0x82072614, "tasklet_kill" },
	{ 0xc6cbbc89, "capable" },
	{ 0x78764f4e, "pv_irq_ops" },
	{ 0x6223cafb, "_raw_spin_unlock_bh" },
	{ 0xf0fdf6cb, "__stack_chk_fail" },
	{ 0x3bd1b1f6, "msecs_to_jiffies" },
	{ 0xd62c833f, "schedule_timeout" },
	{ 0xebf1d59e, "kmem_cache_alloc_trace" },
	{ 0x6443d74d, "_raw_spin_lock" },
	{ 0x3577deda, "ip_route_output_flow" },
	{ 0xf09c7f68, "__wake_up" },
	{ 0x37a0cba, "kfree" },
	{ 0xe75663a, "prepare_to_wait" },
	{ 0xea8a6c75, "param_ops_long" },
	{ 0xb00ccc33, "finish_wait" },
	{ 0x7c173883, "__uio_register_device" },
	{ 0x8b535f7a, "pci_dev_put" },
	{ 0x6e9dd606, "__symbol_put" },
	{ 0x6d1f8fa5, "queue_delayed_work" },
	{ 0x6e720ff2, "rtnl_unlock" },
	{ 0xe0cee63, "dma_ops" },
	{ 0xf20dabd8, "free_irq" },
};

static const char __module_depends[]
__used
__attribute__((section(".modinfo"))) =
"depends=uio";


MODULE_INFO(srcversion, "9BD59415B4E657A7532C78A");
