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
	{ 0xd8a5d65f, "module_put" },
	{ 0x4141f80, "__tracepoint_module_get" },
	{ 0x343a1a8, "__list_add" },
	{ 0xebf1d59e, "kmem_cache_alloc_trace" },
	{ 0xf15faef6, "malloc_sizes" },
	{ 0x2bc09a00, "pf_ring_add_module_dependency" },
	{ 0x63ecad53, "register_netdevice_notifier" },
	{ 0x9edbecae, "snprintf" },
	{ 0x37a0cba, "kfree" },
	{ 0x521445b, "list_del" },
	{ 0xfe769456, "unregister_netdevice_notifier" },
	{ 0x27e1a049, "printk" },
	{ 0xb4390f9a, "mcount" },
};

static const char __module_depends[]
__used
__attribute__((section(".modinfo"))) =
"depends=pf_ring";


MODULE_INFO(srcversion, "469AC281345BC69AC85B21E");
