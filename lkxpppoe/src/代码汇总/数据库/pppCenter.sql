-- phpMyAdmin SQL Dump
-- version 4.0.10
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014-07-22 15:29:02
-- 服务器版本: 5.1.73
-- PHP 版本: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `pppCenter`
--

-- --------------------------------------------------------

--
-- 表的结构 `adminInfo`
--

CREATE TABLE IF NOT EXISTS `adminInfo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `pass` varchar(64) NOT NULL,
  `level` int(4) NOT NULL COMMENT '身份级别',
  `desc` varchar(64) NOT NULL,
  `statu` varchar(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

--
-- 转存表中的数据 `adminInfo`
--

INSERT INTO `adminInfo` (`id`, `name`, `pass`, `level`, `desc`, `statu`) VALUES
(28, 'hezx', 'e120746f9cb2e749fe1cdd3a2c2be67b', 1, '', 'true'),
(29, 'chenjh', '2bc4c9f7076a91d0706cddf05b004bad', 2, '', 'true');

-- --------------------------------------------------------

--
-- 表的结构 `agentInfo`
--

CREATE TABLE IF NOT EXISTS `agentInfo` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `user` varchar(32) NOT NULL,
  `pwd` varchar(64) NOT NULL,
  `realname` varchar(32) NOT NULL,
  `email` varchar(32) NOT NULL,
  `qq` varchar(32) NOT NULL,
  `address` varchar(128) NOT NULL,
  `phoneNum` varchar(32) NOT NULL,
  `codeNum` varchar(64) NOT NULL,
  `addtime` varchar(32) NOT NULL,
  `statu` varchar(8) NOT NULL,
  `desc` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

--
-- 转存表中的数据 `agentInfo`
--

INSERT INTO `agentInfo` (`id`, `user`, `pwd`, `realname`, `email`, `qq`, `address`, `phoneNum`, `codeNum`, `addtime`, `statu`, `desc`) VALUES
(41, 'hezuoxiang', '123456', '香港', '', '', '', '12345678900', '', '2014-03-10 15:03:25', 'true', '');

-- --------------------------------------------------------

--
-- 表的结构 `agentRadius`
--

CREATE TABLE IF NOT EXISTS `agentRadius` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `aId` int(12) NOT NULL COMMENT '代理ID',
  `rId` int(4) NOT NULL COMMENT 'radius编号',
  `statu` varchar(8) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=52 ;

--
-- 转存表中的数据 `agentRadius`
--

INSERT INTO `agentRadius` (`id`, `aId`, `rId`, `statu`) VALUES
(51, 41, 5, 'true');

-- --------------------------------------------------------

--
-- 表的结构 `businessInfo`
--

CREATE TABLE IF NOT EXISTS `businessInfo` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `clientId` int(12) NOT NULL,
  `salerId` int(4) NOT NULL,
  `salertype` varchar(32) NOT NULL COMMENT '业务员类型',
  `mealid` int(4) NOT NULL COMMENT '套餐类型',
  `fixId` int(12) NOT NULL COMMENT '安装工单',
  `starttime` varchar(32) NOT NULL COMMENT '开通时间',
  `addtime` varchar(32) NOT NULL COMMENT '工单添加时间',
  `statu` varchar(32) NOT NULL COMMENT '工单状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `client`
--

CREATE TABLE IF NOT EXISTS `client` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `user` varchar(32) NOT NULL,
  `pwd` varchar(64) NOT NULL,
  `agentId` int(12) NOT NULL,
  `groupId` int(12) NOT NULL,
  `radiusId` int(12) NOT NULL,
  `inpointId` int(11) NOT NULL,
  `mealid` int(4) NOT NULL COMMENT '套餐ID号',
  `Simultaneous` int(10) NOT NULL DEFAULT '1' COMMENT '同时登陆',
  `statu` varchar(20) NOT NULL COMMENT '可用状态',
  `httpInfo` varchar(128) NOT NULL COMMENT '消息队列执行返回信息',
  `online` varchar(8) NOT NULL COMMENT '在线状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`),
  KEY `statu` (`statu`),
  KEY `user_2` (`user`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=578062 ;

-- --------------------------------------------------------

--
-- 表的结构 `clientInfo`
--

CREATE TABLE IF NOT EXISTS `clientInfo` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `cId` int(12) NOT NULL COMMENT '客户ID',
  `realname` varchar(64) DEFAULT NULL,
  `address` varchar(256) DEFAULT NULL,
  `phoneNum` varchar(64) DEFAULT NULL COMMENT '电话号码',
  `cardType` varchar(20) DEFAULT NULL,
  `codeNum` varchar(128) DEFAULT NULL COMMENT '证件号码',
  `email` varchar(32) DEFAULT NULL,
  `qq` varchar(20) DEFAULT NULL,
  `gfqPort` varchar(32) NOT NULL,
  `Mac` varchar(32) NOT NULL,
  `addtime` varchar(20) DEFAULT NULL COMMENT '添加时间',
  `desc` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cId` (`cId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=578037 ;

-- --------------------------------------------------------

--
-- 表的结构 `groupInfo`
--

CREATE TABLE IF NOT EXISTS `groupInfo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `desc` varchar(128) DEFAULT NULL COMMENT '群组详细信息',
  `statu` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- 转存表中的数据 `groupInfo`
--

INSERT INTO `groupInfo` (`id`, `name`, `desc`, `statu`) VALUES
(6, '测试', '', 'true');

-- --------------------------------------------------------

--
-- 表的结构 `inpointInfo`
--

CREATE TABLE IF NOT EXISTS `inpointInfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) CHARACTER SET utf8 NOT NULL,
  `statu` varchar(8) CHARACTER SET utf8 NOT NULL,
  `desc` varchar(128) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

--
-- 转存表中的数据 `inpointInfo`
--

INSERT INTO `inpointInfo` (`id`, `name`, `statu`, `desc`) VALUES
(17, '测试', 'true', '');

-- --------------------------------------------------------

--
-- 表的结构 `levelInfo`
--

CREATE TABLE IF NOT EXISTS `levelInfo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '身份名称',
  `desc` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- 转存表中的数据 `levelInfo`
--

INSERT INTO `levelInfo` (`id`, `name`, `desc`) VALUES
(1, '超级管理员', '全部权限'),
(2, '客户经理', ''),
(3, '代理商', '');

-- --------------------------------------------------------

--
-- 表的结构 `log_fix`
--

CREATE TABLE IF NOT EXISTS `log_fix` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `gongdanId` int(12) NOT NULL COMMENT '业务工单ID号',
  `optman` varchar(32) NOT NULL COMMENT '装机人员',
  `fixtime` varchar(32) NOT NULL COMMENT '装机时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `log_operate`
--

CREATE TABLE IF NOT EXISTS `log_operate` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `optman` varchar(32) NOT NULL COMMENT '操作人',
  `optmanlevel` varchar(32) NOT NULL COMMENT '操作者身份级别',
  `opttype` varchar(32) NOT NULL COMMENT '操作类型（查看，编辑，删除，添加）',
  `optobject` varchar(256) NOT NULL COMMENT '操作对象',
  `optobjtype` varchar(32) NOT NULL COMMENT '操作对象类型',
  `opttime` varchar(32) NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=599733 ;

-- --------------------------------------------------------

--
-- 表的结构 `log_repair`
--

CREATE TABLE IF NOT EXISTS `log_repair` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `clientId` int(12) NOT NULL,
  `optman` varchar(32) NOT NULL COMMENT '维护人',
  `rprtime` varchar(32) DEFAULT NULL COMMENT '维护时间',
  `rprcause` varchar(128) NOT NULL COMMENT '故障原因',
  `rprresult` varchar(128) NOT NULL COMMENT '维护结果',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `log _userOperate`
--

CREATE TABLE IF NOT EXISTS `log _userOperate` (
  `id` int(11) NOT NULL,
  `Optman` varchar(32) NOT NULL,
  `opttype` varchar(32) NOT NULL,
  `Optobject` varchar(2000) NOT NULL,
  `opttime` char(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `mealinfo`
--

CREATE TABLE IF NOT EXISTS `mealinfo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '套餐名称',
  `price` varchar(32) NOT NULL COMMENT '套餐价格',
  `content` varchar(256) NOT NULL COMMENT '套餐具体内容',
  `roleSpeed` int(4) NOT NULL,
  `roleMonth` int(4) NOT NULL COMMENT '套餐月份数',
  `addMonth` int(4) NOT NULL COMMENT '赠送月数',
  `roleTime` int(4) NOT NULL,
  `roleTraffic` int(4) NOT NULL,
  `desc` varchar(256) NOT NULL COMMENT '备注信息或附加条件',
  `statu` varchar(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=62 ;

--
-- 转存表中的数据 `mealinfo`
--

INSERT INTO `mealinfo` (`id`, `name`, `price`, `content`, `roleSpeed`, `roleMonth`, `addMonth`, `roleTime`, `roleTraffic`, `desc`, `statu`) VALUES
(56, '测试套餐', '0', '测试', 13, 12, 1, 0, 0, '', 'true'),
(57, 'test', '0', '测试用', 14, 18, 18, 0, 0, '', 'true'),
(58, '2M-1年', '0', '2M1年套餐', 14, 12, 0, 0, 0, '', 'true'),
(59, '路由器-2M-2年', '0', '睿江盒子专用套餐', 14, 24, 0, 0, 0, '', 'true'),
(60, '路由器-测试-2M-2月', '', '路由测试套餐', 14, 2, 0, 0, 0, '', 'true'),
(61, '不限速度-2年', '', '不限速', 15, 24, 0, 0, 0, '', 'true');

-- --------------------------------------------------------

--
-- 表的结构 `nonespeedlimit`
--

CREATE TABLE IF NOT EXISTS `nonespeedlimit` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `ipaddr` varchar(32) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- 表的结构 `overdue`
--

CREATE TABLE IF NOT EXISTS `overdue` (
  `username` varchar(64) NOT NULL,
  `enddate` date NOT NULL,
  `status` varchar(16) NOT NULL,
  `other` varchar(128) DEFAULT NULL,
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `radiusInfo`
--

CREATE TABLE IF NOT EXISTS `radiusInfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT 'pppoe单元名称',
  `serverip` varchar(64) NOT NULL,
  `dbname` varchar(64) NOT NULL,
  `username` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `desc` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- 转存表中的数据 `radiusInfo`
--

INSERT INTO `radiusInfo` (`id`, `name`, `serverip`, `dbname`, `username`, `password`, `desc`) VALUES
(5, '测试', '127.0.0.1', 'radius', 'root', 'rjkj@rjkj', '');

-- --------------------------------------------------------

--
-- 表的结构 `roleDate`
--

CREATE TABLE IF NOT EXISTS `roleDate` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `cId` int(12) NOT NULL COMMENT '客户ID',
  `startdate` varchar(32) NOT NULL,
  `enddate` varchar(32) NOT NULL,
  `Indate` varchar(32) NOT NULL,
  `MonthCount` int(11) NOT NULL,
  `Recdate` varchar(32) NOT NULL,
  `FristMoney` decimal(10,2) NOT NULL,
  `UseMoney` decimal(10,2) NOT NULL,
  `ModelMoney` decimal(10,2) NOT NULL,
  `ChangeMoney` decimal(10,2) NOT NULL,
  `MobilMoney` decimal(10,2) NOT NULL,
  `DLMoney` decimal(10,2) NOT NULL,
  `OtherMoney` decimal(10,2) NOT NULL,
  `desc` varchar(256) NOT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1653 ;

-- --------------------------------------------------------

--
-- 表的结构 `roleSpeed`
--

CREATE TABLE IF NOT EXISTS `roleSpeed` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `limit` varchar(32) DEFAULT NULL COMMENT '下载速度限制大小',
  `statu` varchar(8) NOT NULL DEFAULT 'true',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

--
-- 转存表中的数据 `roleSpeed`
--

INSERT INTO `roleSpeed` (`id`, `limit`, `statu`) VALUES
(13, '4', 'true'),
(14, '2', 'true'),
(15, '500', 'true');

-- --------------------------------------------------------

--
-- 表的结构 `roleTime`
--

CREATE TABLE IF NOT EXISTS `roleTime` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `limit` varchar(32) NOT NULL COMMENT '单位（h）',
  `statu` varchar(8) NOT NULL DEFAULT 'true',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- 表的结构 `roleTraffic`
--

CREATE TABLE IF NOT EXISTS `roleTraffic` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `limit` varchar(32) NOT NULL COMMENT '单位（G）',
  `statu` varchar(8) NOT NULL DEFAULT 'true',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- 表的结构 `speedtest`
--

CREATE TABLE IF NOT EXISTS `speedtest` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `ipaddr` varchar(20) CHARACTER SET utf8 NOT NULL,
  `description` varchar(256) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- 替换视图以便查看 `view_client`
--
CREATE TABLE IF NOT EXISTS `view_client` (
`id` int(12)
,`user` varchar(32)
,`pwd` varchar(64)
,`agentId` int(12)
,`groupId` int(12)
,`radiusId` int(12)
,`mealid` int(4)
,`statu` varchar(20)
,`httpInfo` varchar(128)
,`online` varchar(8)
,`realname` varchar(64)
,`address` varchar(256)
,`phoneNum` varchar(64)
,`codeNum` varchar(128)
,`email` varchar(32)
,`qq` varchar(20)
,`addtime` varchar(20)
,`desc` varchar(128)
);
-- --------------------------------------------------------

--
-- 替换视图以便查看 `view_client_agent`
--
CREATE TABLE IF NOT EXISTS `view_client_agent` (
`id` int(12)
,`user` varchar(32)
,`pwd` varchar(64)
,`agentId` int(12)
,`groupId` int(12)
,`radiusId` int(12)
,`mealid` int(4)
,`statu` varchar(20)
,`httpInfo` varchar(128)
,`online` varchar(8)
,`realname` varchar(64)
,`address` varchar(256)
,`phoneNum` varchar(64)
,`codeNum` varchar(128)
,`email` varchar(32)
,`qq` varchar(20)
,`addtime` varchar(20)
,`desc` varchar(128)
,`agentUser` varchar(32)
,`agentPwd` varchar(64)
,`agentRealname` varchar(32)
,`agentEmail` varchar(32)
,`agentQQ` varchar(32)
,`agentAddress` varchar(128)
,`agentPhone` varchar(32)
,`agentCode` varchar(64)
,`agentdesc` varchar(128)
);
-- --------------------------------------------------------

--
-- 替换视图以便查看 `view_client_all`
--
CREATE TABLE IF NOT EXISTS `view_client_all` (
`id` int(12)
,`user` varchar(32)
,`pwd` varchar(64)
,`httpInfo` varchar(128)
,`agentId` int(12)
,`groupId` int(12)
,`radiusId` int(12)
,`mealid` int(4)
,`online` varchar(8)
,`desc` varchar(128)
,`statu` varchar(20)
,`name` varchar(32)
,`groupDesc` varchar(128)
,`groupStatu` varchar(8)
,`agentUser` varchar(32)
,`agentPwd` varchar(64)
,`agentRealname` varchar(32)
,`agentEmail` varchar(32)
,`agentQQ` varchar(32)
,`agentAddress` varchar(128)
,`agentPhone` varchar(32)
,`agentCode` varchar(64)
,`agentdesc` varchar(128)
);
-- --------------------------------------------------------

--
-- 替换视图以便查看 `view_client_group`
--
CREATE TABLE IF NOT EXISTS `view_client_group` (
`id` int(12)
,`user` varchar(32)
,`pwd` varchar(64)
,`agentId` int(12)
,`groupId` int(12)
,`radiusId` int(12)
,`mealid` int(4)
,`statu` varchar(20)
,`httpInfo` varchar(128)
,`online` varchar(8)
,`realname` varchar(64)
,`address` varchar(256)
,`phoneNum` varchar(64)
,`codeNum` varchar(128)
,`email` varchar(32)
,`qq` varchar(20)
,`addtime` varchar(20)
,`desc` varchar(128)
,`name` varchar(32)
,`groupDesc` varchar(128)
,`groupStatu` varchar(8)
);
-- --------------------------------------------------------

--
-- 视图结构 `view_client`
--
DROP TABLE IF EXISTS `view_client`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_client` AS select `c`.`id` AS `id`,`c`.`user` AS `user`,`c`.`pwd` AS `pwd`,`c`.`agentId` AS `agentId`,`c`.`groupId` AS `groupId`,`c`.`radiusId` AS `radiusId`,`c`.`mealid` AS `mealid`,`c`.`statu` AS `statu`,`c`.`httpInfo` AS `httpInfo`,`c`.`online` AS `online`,`i`.`realname` AS `realname`,`i`.`address` AS `address`,`i`.`phoneNum` AS `phoneNum`,`i`.`codeNum` AS `codeNum`,`i`.`email` AS `email`,`i`.`qq` AS `qq`,`i`.`addtime` AS `addtime`,`i`.`desc` AS `desc` from (`client` `c` left join `clientInfo` `i` on((`c`.`id` = `i`.`cId`))) order by `c`.`id`;

-- --------------------------------------------------------

--
-- 视图结构 `view_client_agent`
--
DROP TABLE IF EXISTS `view_client_agent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_client_agent` AS select `c`.`id` AS `id`,`c`.`user` AS `user`,`c`.`pwd` AS `pwd`,`c`.`agentId` AS `agentId`,`c`.`groupId` AS `groupId`,`c`.`radiusId` AS `radiusId`,`c`.`mealid` AS `mealid`,`c`.`statu` AS `statu`,`c`.`httpInfo` AS `httpInfo`,`c`.`online` AS `online`,`c`.`realname` AS `realname`,`c`.`address` AS `address`,`c`.`phoneNum` AS `phoneNum`,`c`.`codeNum` AS `codeNum`,`c`.`email` AS `email`,`c`.`qq` AS `qq`,`c`.`addtime` AS `addtime`,`c`.`desc` AS `desc`,`a`.`user` AS `agentUser`,`a`.`pwd` AS `agentPwd`,`a`.`realname` AS `agentRealname`,`a`.`email` AS `agentEmail`,`a`.`qq` AS `agentQQ`,`a`.`address` AS `agentAddress`,`a`.`phoneNum` AS `agentPhone`,`a`.`codeNum` AS `agentCode`,`a`.`desc` AS `agentdesc` from (`view_client` `c` left join `agentInfo` `a` on((`c`.`agentId` = `a`.`id`))) order by `c`.`id`;

-- --------------------------------------------------------

--
-- 视图结构 `view_client_all`
--
DROP TABLE IF EXISTS `view_client_all`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_client_all` AS select `g`.`id` AS `id`,`g`.`user` AS `user`,`g`.`pwd` AS `pwd`,`g`.`httpInfo` AS `httpInfo`,`g`.`agentId` AS `agentId`,`g`.`groupId` AS `groupId`,`g`.`radiusId` AS `radiusId`,`g`.`mealid` AS `mealid`,`g`.`online` AS `online`,`g`.`desc` AS `desc`,`g`.`statu` AS `statu`,`g`.`name` AS `name`,`g`.`groupDesc` AS `groupDesc`,`g`.`groupStatu` AS `groupStatu`,`a`.`agentUser` AS `agentUser`,`a`.`agentPwd` AS `agentPwd`,`a`.`agentRealname` AS `agentRealname`,`a`.`agentEmail` AS `agentEmail`,`a`.`agentQQ` AS `agentQQ`,`a`.`agentAddress` AS `agentAddress`,`a`.`agentPhone` AS `agentPhone`,`a`.`agentCode` AS `agentCode`,`a`.`agentdesc` AS `agentdesc` from (`view_client_agent` `a` left join `view_client_group` `g` on((`a`.`id` = `g`.`id`)));

-- --------------------------------------------------------

--
-- 视图结构 `view_client_group`
--
DROP TABLE IF EXISTS `view_client_group`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_client_group` AS select `c`.`id` AS `id`,`c`.`user` AS `user`,`c`.`pwd` AS `pwd`,`c`.`agentId` AS `agentId`,`c`.`groupId` AS `groupId`,`c`.`radiusId` AS `radiusId`,`c`.`mealid` AS `mealid`,`c`.`statu` AS `statu`,`c`.`httpInfo` AS `httpInfo`,`c`.`online` AS `online`,`c`.`realname` AS `realname`,`c`.`address` AS `address`,`c`.`phoneNum` AS `phoneNum`,`c`.`codeNum` AS `codeNum`,`c`.`email` AS `email`,`c`.`qq` AS `qq`,`c`.`addtime` AS `addtime`,`c`.`desc` AS `desc`,`g`.`name` AS `name`,`g`.`desc` AS `groupDesc`,`g`.`statu` AS `groupStatu` from (`view_client` `c` left join `groupInfo` `g` on((`c`.`groupId` = `g`.`id`))) order by `c`.`id`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
