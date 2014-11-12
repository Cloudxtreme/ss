/*******************************************************************************

  Intel PRO/1000 Linux driver
  Copyright(c) 1999 - 2012 Intel Corporation.

  This program is free software; you can redistribute it and/or modify it
  under the terms and conditions of the GNU General Public License,
  version 2, as published by the Free Software Foundation.

  This program is distributed in the hope it will be useful, but WITHOUT
  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
  FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
  more details.

  You should have received a copy of the GNU General Public License along with
  this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin St - Fifth Floor, Boston, MA 02110-1301 USA.

  The full GNU General Public License is included in this distribution in
  the file called "COPYING".

  Contact Information:
  Linux NICS <linux.nics@intel.com>
  e1000-devel Mailing List <e1000-devel@lists.sourceforge.net>
  Intel Corporation, 5200 N.E. Elam Young Parkway, Hillsboro, OR 97124-6497

*******************************************************************************/

#ifndef _E1000_ICH8LAN_H_
#define _E1000_ICH8LAN_H_

#define ICH_FLASH_GFPREG		0x0000
#define ICH_FLASH_HSFSTS		0x0004
#define ICH_FLASH_HSFCTL		0x0006
#define ICH_FLASH_FADDR			0x0008
#define ICH_FLASH_FDATA0		0x0010

/* Requires up to 10 seconds when MNG might be accessing part. */
#define ICH_FLASH_READ_COMMAND_TIMEOUT	10000000
#define ICH_FLASH_WRITE_COMMAND_TIMEOUT	10000000
#define ICH_FLASH_ERASE_COMMAND_TIMEOUT	10000000
#define ICH_FLASH_LINEAR_ADDR_MASK	0x00FFFFFF
#define ICH_FLASH_CYCLE_REPEAT_COUNT	10

#define ICH_CYCLE_READ			0
#define ICH_CYCLE_WRITE			2
#define ICH_CYCLE_ERASE			3

#define FLASH_GFPREG_BASE_MASK		0x1FFF
#define FLASH_SECTOR_ADDR_SHIFT		12

#define ICH_FLASH_SEG_SIZE_256		256
#define ICH_FLASH_SEG_SIZE_4K		4096
#define ICH_FLASH_SEG_SIZE_8K		8192
#define ICH_FLASH_SEG_SIZE_64K		65536
#define ICH_FLASH_SECTOR_SIZE		4096

#define ICH_FLASH_REG_MAPSIZE		0x00A0

#define E1000_ICH_FWSM_RSPCIPHY		0x00000040	/* Reset PHY on PCI Reset */
#define E1000_ICH_FWSM_DISSW		0x10000000	/* FW Disables SW Writes */
/* FW established a valid mode */
#define E1000_ICH_FWSM_FW_VALID		0x00008000
#define E1000_ICH_FWSM_PCIM2PCI		0x01000000	/* ME PCIm-to-PCI active */
#define E1000_ICH_FWSM_PCIM2PCI_COUNT	2000

#define E1000_ICH_MNG_IAMT_MODE		0x2

#define E1000_FWSM_PROXY_MODE		0x00000008	/* FW is in proxy mode */
#define E1000_FWSM_MEMC			0x00000010	/* ME Messaging capable */
#define E1000_FWSM_WLOCK_MAC_MASK	0x0380
#define E1000_FWSM_WLOCK_MAC_SHIFT	7

/* Shared Receive Address Registers */
#define E1000_SHRAL(_i)		(0x05438 + ((_i) * 8))
#define E1000_SHRAH(_i)		(0x0543C + ((_i) * 8))
#define E1000_SHRAH_AV		0x80000000	/* Addr Valid bit */
#define E1000_SHRAH_MAV		0x40000000	/* Multicast Addr Valid bit */

#define E1000_SHRAL_PCH_LPT(_i)		(0x05408 + ((_i) * 8))
#define E1000_SHRAH_PCH_LPT(_i)		(0x0540C + ((_i) * 8))

#define E1000_H2ME		0x05B50	/* Host to ME */
#define E1000_H2ME_LSECREQ	0x00000001	/* Linksec Request */
#define E1000_H2ME_LSECA	0x00000002	/* Linksec Active */
#define E1000_H2ME_LSECSF	0x00000004	/* Linksec Failed */
#define E1000_H2ME_LSECD	0x00000008	/* Linksec Disabled */
#define E1000_H2ME_SLCAPD	0x00000010	/* Start LCAPD */
#define E1000_H2ME_IPV4_ARP_EN	0x00000020	/* Arp Offload enable bit */
#define E1000_H2ME_IPV6_NS_EN	0x00000040	/* NS Offload enable bit */

#define ID_LED_DEFAULT_ICH8LAN	((ID_LED_DEF1_DEF2 << 12) | \
				 (ID_LED_OFF1_OFF2 <<  8) | \
				 (ID_LED_OFF1_ON2  <<  4) | \
				 (ID_LED_DEF1_DEF2))

#define E1000_ICH_NVM_SIG_WORD		0x13
#define E1000_ICH_NVM_SIG_MASK		0xC000
#define E1000_ICH_NVM_VALID_SIG_MASK	0xC0
#define E1000_ICH_NVM_SIG_VALUE		0x80

#define E1000_ICH8_LAN_INIT_TIMEOUT	1500

#define E1000_FEXTNVM_SW_CONFIG		1
#define E1000_FEXTNVM_SW_CONFIG_ICH8M	(1 << 27)	/* Bit redefined for ICH8M */

#define E1000_FEXTNVM3	0x0003C	/* Future Extended NVM 3 - RW */
#define E1000_FEXTNVM3_PHY_CFG_COUNTER_MASK	0x0C000000
#define E1000_FEXTNVM3_PHY_CFG_COUNTER_50MSEC	0x08000000

#define E1000_FEXTNVM4_BEACON_DURATION_MASK	0x7
#define E1000_FEXTNVM4_BEACON_DURATION_8USEC	0x7
#define E1000_FEXTNVM4_BEACON_DURATION_16USEC	0x3

#define PCIE_ICH8_SNOOP_ALL	PCIE_NO_SNOOP_ALL

#define E1000_ICH_RAR_ENTRIES	7
#define E1000_PCH2_RAR_ENTRIES	5	/* RAR[0], SHRA[0-3] */
#define E1000_PCH_LPT_RAR_ENTRIES	12	/* RAR[0], SHRA[0-10] */

#define PHY_PAGE_SHIFT		5
#define PHY_REG(page, reg)	(((page) << PHY_PAGE_SHIFT) | \
				 ((reg) & MAX_PHY_REG_ADDRESS))
#define IGP3_KMRN_DIAG		PHY_REG(770, 19)	/* KMRN Diagnostic */
#define IGP3_VR_CTRL		PHY_REG(776, 18)	/* Voltage Regulator Control */
#define IGP3_CAPABILITY		PHY_REG(776, 19)	/* Capability */
#define IGP3_PM_CTRL		PHY_REG(769, 20)	/* Power Management Control */

#define IGP3_KMRN_DIAG_PCS_LOCK_LOSS		0x0002
#define IGP3_VR_CTRL_DEV_POWERDOWN_MODE_MASK	0x0300
#define IGP3_VR_CTRL_MODE_SHUTDOWN		0x0200
#define IGP3_PM_CTRL_FORCE_PWR_DOWN		0x0020

/* PHY Wakeup Registers and defines */
#define BM_PORT_GEN_CFG		PHY_REG(BM_PORT_CTRL_PAGE, 17)
#define BM_RCTL			PHY_REG(BM_WUC_PAGE, 0)
#define BM_WUC			PHY_REG(BM_WUC_PAGE, 1)
#define BM_WUFC			PHY_REG(BM_WUC_PAGE, 2)
#define BM_WUS			PHY_REG(BM_WUC_PAGE, 3)
#define BM_RAR_L(_i)		(BM_PHY_REG(BM_WUC_PAGE, 16 + ((_i) << 2)))
#define BM_RAR_M(_i)		(BM_PHY_REG(BM_WUC_PAGE, 17 + ((_i) << 2)))
#define BM_RAR_H(_i)		(BM_PHY_REG(BM_WUC_PAGE, 18 + ((_i) << 2)))
#define BM_RAR_CTRL(_i)		(BM_PHY_REG(BM_WUC_PAGE, 19 + ((_i) << 2)))
#define BM_MTA(_i)		(BM_PHY_REG(BM_WUC_PAGE, 128 + ((_i) << 1)))
#define BM_IPAV			(BM_PHY_REG(BM_WUC_PAGE, 64))
#define BM_IP4AT_L(_i)		(BM_PHY_REG(BM_WUC_PAGE, 82 + ((_i) * 2)))
#define BM_IP4AT_H(_i)		(BM_PHY_REG(BM_WUC_PAGE, 83 + ((_i) * 2)))

#define BM_SHRAL_LOWER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 44 + ((_i) * 4)))
#define BM_SHRAL_UPPER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 45 + ((_i) * 4)))
#define BM_SHRAH_LOWER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 46 + ((_i) * 4)))
#define BM_SHRAH_UPPER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 47 + ((_i) * 4)))

#define I217_SHRAL_LOWER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 20 + ((_i) * 4)))
#define I217_SHRAL_UPPER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 21 + ((_i) * 4)))
#define I217_SHRAH_LOWER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 22 + ((_i) * 4)))
#define I217_SHRAH_UPPER(_i)	(BM_PHY_REG(BM_WUC_PAGE, 23 + ((_i) * 4)))

#define BM_RCTL_UPE		0x0001	/* Unicast Promiscuous Mode */
#define BM_RCTL_MPE		0x0002	/* Multicast Promiscuous Mode */
#define BM_RCTL_MO_SHIFT	3	/* Multicast Offset Shift */
#define BM_RCTL_MO_MASK		(3 << 3)	/* Multicast Offset Mask */
#define BM_RCTL_BAM		0x0020	/* Broadcast Accept Mode */
#define BM_RCTL_PMCF		0x0040	/* Pass MAC Control Frames */
#define BM_RCTL_RFCE		0x0080	/* Rx Flow Control Enable */

#define HV_LED_CONFIG		PHY_REG(768, 30)	/* LED Configuration */
#define HV_MUX_DATA_CTRL	PHY_REG(776, 16)
#define HV_MUX_DATA_CTRL_GEN_TO_MAC	0x0400
#define HV_MUX_DATA_CTRL_FORCE_SPEED	0x0004
#define HV_STATS_PAGE	778
#define HV_SCC_UPPER	PHY_REG(HV_STATS_PAGE, 16)	/* Single Collision Count */
#define HV_SCC_LOWER	PHY_REG(HV_STATS_PAGE, 17)
#define HV_ECOL_UPPER	PHY_REG(HV_STATS_PAGE, 18)	/* Excessive Coll. Count */
#define HV_ECOL_LOWER	PHY_REG(HV_STATS_PAGE, 19)
#define HV_MCC_UPPER	PHY_REG(HV_STATS_PAGE, 20)	/* Multiple Coll. Count */
#define HV_MCC_LOWER	PHY_REG(HV_STATS_PAGE, 21)
#define HV_LATECOL_UPPER PHY_REG(HV_STATS_PAGE, 23)	/* Late Collision Count */
#define HV_LATECOL_LOWER PHY_REG(HV_STATS_PAGE, 24)
#define HV_COLC_UPPER	PHY_REG(HV_STATS_PAGE, 25)	/* Collision Count */
#define HV_COLC_LOWER	PHY_REG(HV_STATS_PAGE, 26)
#define HV_DC_UPPER	PHY_REG(HV_STATS_PAGE, 27)	/* Defer Count */
#define HV_DC_LOWER	PHY_REG(HV_STATS_PAGE, 28)
#define HV_TNCRS_UPPER	PHY_REG(HV_STATS_PAGE, 29)	/* Transmit with no CRS */
#define HV_TNCRS_LOWER	PHY_REG(HV_STATS_PAGE, 30)

#define E1000_FCRTV_PCH	0x05F40	/* PCH Flow Control Refresh Timer Value */

/*
 * For ICH, the name used for NVM word 17h is LED1 Config.
 * For PCH, the word was re-named to OEM Config.
 */
#define E1000_NVM_LED1_CONFIG		0x17	/* NVM LED1/LPLU Config Word */
#define E1000_NVM_LED1_CONFIG_LPLU_NONDOA 0x0400	/* NVM LPLU in non-D0a Bit */
#define E1000_NVM_OEM_CONFIG		E1000_NVM_LED1_CONFIG
#define E1000_NVM_OEM_CONFIG_LPLU_NONDOA E1000_NVM_LED1_CONFIG_LPLU_NONDOA

#define E1000_NVM_K1_CONFIG	0x1B	/* NVM K1 Config Word */
#define E1000_NVM_K1_ENABLE	0x1	/* NVM Enable K1 bit */

/* SMBus Control Phy Register */
#define CV_SMB_CTRL		PHY_REG(769, 23)
#define CV_SMB_CTRL_FORCE_SMBUS	0x0001

/* SMBus Address Phy Register */
#define HV_SMB_ADDR		PHY_REG(768, 26)
#define HV_SMB_ADDR_MASK	0x007F
#define HV_SMB_ADDR_PEC_EN	0x0200
#define HV_SMB_ADDR_VALID	0x0080
#define HV_SMB_ADDR_FREQ_MASK		0x1100
#define HV_SMB_ADDR_FREQ_LOW_SHIFT	8
#define HV_SMB_ADDR_FREQ_HIGH_SHIFT	12

/* Strapping Option Register - RO */
#define E1000_STRAP			0x0000C
#define E1000_STRAP_SMBUS_ADDRESS_MASK	0x00FE0000
#define E1000_STRAP_SMBUS_ADDRESS_SHIFT	17
#define E1000_STRAP_SMT_FREQ_MASK	0x00003000
#define E1000_STRAP_SMT_FREQ_SHIFT	12

/* OEM Bits Phy Register */
#define HV_OEM_BITS		PHY_REG(768, 25)
#define HV_OEM_BITS_LPLU	0x0004	/* Low Power Link Up */
#define HV_OEM_BITS_GBE_DIS	0x0040	/* Gigabit Disable */
#define HV_OEM_BITS_RESTART_AN	0x0400	/* Restart Auto-negotiation */

#define LCD_CFG_PHY_ADDR_BIT	0x0020	/* Phy addr bit from LCD Config word */

/* KMRN Mode Control */
#define HV_KMRN_MODE_CTRL	PHY_REG(769, 16)
#define HV_KMRN_MDIO_SLOW	0x0400

/* KMRN FIFO Control and Status */
#define HV_KMRN_FIFO_CTRLSTA			PHY_REG(770, 16)
#define HV_KMRN_FIFO_CTRLSTA_PREAMBLE_MASK	0x7000
#define HV_KMRN_FIFO_CTRLSTA_PREAMBLE_SHIFT	12

/* PHY Power Management Control */
#define HV_PM_CTRL		PHY_REG(770, 17)
#define HV_PM_CTRL_PLL_STOP_IN_K1_GIGA	0x100
#define I217_MEM_PM_CFG		PHY_REG(772, 27)	/* I217 PHY Mem PM Cfg Reg */
#define I217_MEM_PM_CFG_TXF_SD	0x0020	/* Tx FIFO Memories Shutdown */

#define SW_FLAG_TIMEOUT		1000	/* SW Semaphore flag timeout in ms */

/* PHY Low Power Idle Control */
#define I82579_LPI_CTRL				PHY_REG(772, 20)
#define I82579_LPI_CTRL_ENABLE_MASK		0x6000
#define I82579_LPI_CTRL_FORCE_PLL_LOCK_COUNT	0x80

/* EMI Registers */
#define I82579_EMI_ADDR		0x10
#define I82579_EMI_DATA		0x11
#define I82579_LPI_UPDATE_TIMER	0x4805	/* in 40ns units + 40 ns base value */
#define I82579_MSE_THRESHOLD	0x084F	/* Mean Square Error Threshold */
#define I82579_MSE_LINK_DOWN	0x2411	/* MSE count before dropping link */
#define I217_EEE_ADVERTISEMENT	0x8001	/* IEEE MMD Register 7.60 */
#define I217_EEE_LP_ABILITY	0x8002	/* IEEE MMD Register 7.61 */
#define I217_EEE_100_SUPPORTED	(1 << 1)	/* 100BaseTx EEE supported */

/* Intel Rapid Start Technology Support */
#define I217_PROXY_CTRL			PHY_REG(BM_WUC_PAGE, 70)
#define I217_PROXY_CTRL_AUTO_DISABLE	0x0080
#define I217_SxCTRL			PHY_REG(BM_PORT_CTRL_PAGE, 28)
#define I217_SxCTRL_MASK		0x1000
#define I217_CGFREG			PHY_REG(772, 29)
#define I217_CGFREG_MASK		0x0002
#define I217_MEMPWR			PHY_REG(772, 26)
#define I217_MEMPWR_MASK		0x0010

/*
 * Additional interrupts need to be handled for ICH family:
 *  DSW = The FW changed the status of the DISSW bit in FWSM
 *  PHYINT = The LAN connected device generates an interrupt
 *  EPRST = Manageability reset event
 */
#define IMS_ICH_ENABLE_MASK (\
	E1000_IMS_DSW   | \
	E1000_IMS_PHYINT | \
	E1000_IMS_EPRST)

/* Additional interrupt register bit definitions */
#define E1000_ICR_LSECPNC	0x00004000	/* PN threshold - client */
#define E1000_IMS_LSECPNC	E1000_ICR_LSECPNC	/* PN threshold - client */
#define E1000_ICS_LSECPNC	E1000_ICR_LSECPNC	/* PN threshold - client */

/* Security Processing bit Indication */
#define E1000_RXDEXT_LINKSEC_STATUS_LSECH	0x01000000
#define E1000_RXDEXT_LINKSEC_ERROR_BIT_MASK	0x60000000
#define E1000_RXDEXT_LINKSEC_ERROR_NO_SA_MATCH	0x20000000
#define E1000_RXDEXT_LINKSEC_ERROR_REPLAY_ERROR	0x40000000
#define E1000_RXDEXT_LINKSEC_ERROR_BAD_SIG	0x60000000

/* Receive Address Initial CRC Calculation */
#define E1000_PCH_RAICC(_n)	(0x05F50 + ((_n) * 4))

void e1000e_set_kmrn_lock_loss_workaround_ich8lan(struct e1000_hw *hw,
						  bool state);
void e1000e_igp3_phy_powerdown_workaround_ich8lan(struct e1000_hw *hw);
void e1000e_gig_downshift_workaround_ich8lan(struct e1000_hw *hw);
void e1000_suspend_workarounds_ich8lan(struct e1000_hw *hw);
void e1000_resume_workarounds_pchlan(struct e1000_hw *hw);
s32 e1000_configure_k1_ich8lan(struct e1000_hw *hw, bool k1_enable);
void e1000_copy_rx_addrs_to_phy_ich8lan(struct e1000_hw *hw);
s32 e1000_lv_jumbo_workaround_ich8lan(struct e1000_hw *hw, bool enable);
#endif
