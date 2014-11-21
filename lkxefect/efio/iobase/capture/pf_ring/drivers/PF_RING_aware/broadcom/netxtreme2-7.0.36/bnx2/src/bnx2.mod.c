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
	{ 0xc608ea54, "alloc_pages_current" },
	{ 0x3ce4ca6f, "disable_irq" },
	{ 0x32e373ad, "pci_bus_read_config_byte" },
	{ 0x5a34a45c, "__kmalloc" },
	{ 0xf9a482f9, "msleep" },
	{ 0x77ecac9f, "zlib_inflateEnd" },
	{ 0xd6ee688f, "vmalloc" },
	{ 0x3ec8886f, "param_ops_int" },
	{ 0x30f43d10, "dev_set_drvdata" },
	{ 0x80ac996e, "ethtool_op_set_tx_csum" },
	{ 0x950ffff2, "cpu_online_mask" },
	{ 0x35c3754a, "dma_set_mask" },
	{ 0xff44a95b, "napi_complete" },
	{ 0xf15faef6, "malloc_sizes" },
	{ 0x6c00889d, "pci_disable_device" },
	{ 0xc7a4fbed, "rtnl_lock" },
	{ 0xaa76384d, "pci_disable_msix" },
	{ 0x8b54681a, "netif_carrier_on" },
	{ 0x87a45ee9, "_raw_spin_lock_bh" },
	{ 0x8c4fe8d5, "ethtool_op_get_sg" },
	{ 0xf89843f9, "schedule_work" },
	{ 0x4873f303, "netif_carrier_off" },
	{ 0x88bfa7e, "cancel_work_sync" },
	{ 0x84010cfb, "x86_dma_fallback_dev" },
	{ 0xeae3dfd6, "__const_udelay" },
	{ 0xcc1b5019, "pci_release_regions" },
	{ 0x9e1bdc28, "init_timer_key" },
	{ 0x1b9e1323, "mutex_unlock" },
	{ 0x999e8297, "vfree" },
	{ 0x2ecd2192, "pci_bus_write_config_word" },
	{ 0x47c7b0d2, "cpu_number" },
	{ 0x3c2c5af5, "sprintf" },
	{ 0x540497c4, "netif_napi_del" },
	{ 0x7d11c268, "jiffies" },
	{ 0xefc497d1, "__netdev_alloc_skb" },
	{ 0xfe7c4287, "nr_cpu_ids" },
	{ 0xf7269eb, "pci_set_master" },
	{ 0xe1bc7ede, "del_timer_sync" },
	{ 0xde0bdcff, "memset" },
	{ 0x6cd1e5ed, "pci_enable_pcie_error_reporting" },
	{ 0x9983b8d3, "pci_enable_msix" },
	{ 0xac1c960b, "pci_restore_state" },
	{ 0x5f03e9d8, "dev_err" },
	{ 0xdf04890b, "__mutex_init" },
	{ 0x27e1a049, "printk" },
	{ 0x2fa5a500, "memcmp" },
	{ 0x66148dc9, "ethtool_op_set_flags" },
	{ 0x1e40576e, "free_netdev" },
	{ 0xaff1b4d9, "register_netdev" },
	{ 0xb4390f9a, "mcount" },
	{ 0xce5ac24f, "zlib_inflate_workspacesize" },
	{ 0x16305289, "warn_slowpath_null" },
	{ 0x54841d07, "mutex_lock" },
	{ 0x92ea4ae4, "crc32_le" },
	{ 0x4c74e934, "dev_close" },
	{ 0xa7ef149c, "netdev_printk" },
	{ 0x4ce8018d, "netif_set_real_num_rx_queues" },
	{ 0xc2cdbf1, "synchronize_sched" },
	{ 0xce095088, "mod_timer" },
	{ 0xc8c57286, "netif_set_real_num_tx_queues" },
	{ 0x1902adf, "netpoll_trap" },
	{ 0x834bac7c, "netif_napi_add" },
	{ 0xd6b8e852, "request_threaded_irq" },
	{ 0x96eabc7a, "ethtool_op_get_flags" },
	{ 0xe523ad75, "synchronize_irq" },
	{ 0x3fe46d16, "pci_find_capability" },
	{ 0x881039d0, "zlib_inflate" },
	{ 0x3ff62317, "local_bh_disable" },
	{ 0x89c90723, "netif_device_attach" },
	{ 0x7ea34af0, "napi_gro_receive" },
	{ 0x78764f4e, "pv_irq_ops" },
	{ 0x532b2d49, "__free_pages" },
	{ 0xad92d338, "netif_device_detach" },
	{ 0x42c8de35, "ioremap_nocache" },
	{ 0x1fa8b847, "pci_bus_read_config_word" },
	{ 0x4963be0b, "ethtool_op_set_sg" },
	{ 0x915c4f35, "__napi_schedule" },
	{ 0x2c97167e, "pci_bus_read_config_dword" },
	{ 0x6223cafb, "_raw_spin_unlock_bh" },
	{ 0x19b8311d, "pci_cleanup_aer_uncorrect_error_status" },
	{ 0xf0fdf6cb, "__stack_chk_fail" },
	{ 0x799aca4, "local_bh_enable" },
	{ 0x3d048e98, "eth_type_trans" },
	{ 0x7b9cf684, "pci_unregister_driver" },
	{ 0xcc5005fe, "msleep_interruptible" },
	{ 0xebf1d59e, "kmem_cache_alloc_trace" },
	{ 0x6443d74d, "_raw_spin_lock" },
	{ 0xe52947e7, "__phys_addr" },
	{ 0x4211c3c1, "zlib_inflateInit2" },
	{ 0xdde56a64, "eth_validate_addr" },
	{ 0x55f3b03b, "pci_disable_pcie_error_reporting" },
	{ 0xfcec0987, "enable_irq" },
	{ 0x37a0cba, "kfree" },
	{ 0x236c8c64, "memcpy" },
	{ 0xad613b59, "pci_request_regions" },
	{ 0x9cbab2ba, "pci_disable_msi" },
	{ 0x2d3b776f, "dma_supported" },
	{ 0xedc03953, "iounmap" },
	{ 0xe0fdc845, "__pci_register_driver" },
	{ 0x5bacafae, "ethtool_op_get_tx_csum" },
	{ 0xa7cfe2c9, "pci_get_device" },
	{ 0x4cbbd171, "__bitmap_weight" },
	{ 0x6aef7e0c, "unregister_netdev" },
	{ 0x8b535f7a, "pci_dev_put" },
	{ 0x15eb4cb5, "ethtool_op_get_tso" },
	{ 0x9edbecae, "snprintf" },
	{ 0x1acea66e, "pci_enable_msi_block" },
	{ 0x5a17a05d, "pci_choose_state" },
	{ 0xbabeec57, "__netif_schedule" },
	{ 0xa3a5be95, "memmove" },
	{ 0xd00bbeaa, "consume_skb" },
	{ 0x5055e5c5, "vlan_gro_receive" },
	{ 0xede50f68, "skb_put" },
	{ 0x43b043e2, "pci_enable_device" },
	{ 0x3fa90d3c, "dev_get_drvdata" },
	{ 0x6e720ff2, "rtnl_unlock" },
	{ 0x9d90675e, "ethtool_op_set_tx_ipv6_csum" },
	{ 0xe0cee63, "dma_ops" },
	{ 0xf20dabd8, "free_irq" },
	{ 0x61242197, "pci_save_state" },
	{ 0xe914e41e, "strcpy" },
	{ 0xe11e7915, "alloc_etherdev_mqs" },
};

static const char __module_depends[]
__used
__attribute__((section(".modinfo"))) =
"depends=";

MODULE_ALIAS("pci:v000014E4d0000164Asv0000103Csd00003101bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000164Asv0000103Csd00003106bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000164Asv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000164Csv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d000016AAsv0000103Csd00003102bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d000016AAsv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d000016ACsv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d00001639sv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000163Asv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000163Bsv*sd*bc*sc*i*");
MODULE_ALIAS("pci:v000014E4d0000163Csv*sd*bc*sc*i*");

MODULE_INFO(srcversion, "7E1EE93994B8FED08A24866");
