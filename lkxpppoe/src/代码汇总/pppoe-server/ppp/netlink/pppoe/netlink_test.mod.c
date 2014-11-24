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
	{ 0x41086e, "module_layout" },
	{ 0x4af723c7, "sock_release" },
	{ 0x1db00ffd, "nf_register_hook" },
	{ 0xd08c914c, "netlink_kernel_create" },
	{ 0xb72397d5, "printk" },
	{ 0x2f2a965d, "netlink_unicast" },
	{ 0x2a3902b4, "init_net" },
	{ 0x669fad8a, "__alloc_skb" },
	{ 0xf0fdf6cb, "__stack_chk_fail" },
	{ 0x44744ddb, "kfree_skb" },
	{ 0xd0d81f0e, "nf_unregister_hook" },
	{ 0x7e0913d, "skb_put" },
};

static const char __module_depends[]
__used
__attribute__((section(".modinfo"))) =
"depends=";


MODULE_INFO(srcversion, "9CC197A40C7DAB79B1029F1");

static const struct rheldata _rheldata __used
__attribute__((section(".rheldata"))) = {
	.rhel_major = 6,
	.rhel_minor = 2,
};
