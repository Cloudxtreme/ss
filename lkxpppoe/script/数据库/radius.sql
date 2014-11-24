-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2012 年 04 月 11 日 19:35
-- 服务器版本: 5.1.61
-- PHP 版本: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `radius`
--

-- --------------------------------------------------------

--
-- 表的结构 `nas`
--

CREATE TABLE IF NOT EXISTS `nas` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `nasname` varchar(128) NOT NULL,
  `shortname` varchar(32) DEFAULT NULL,
  `type` varchar(30) DEFAULT 'other',
  `ports` int(5) DEFAULT NULL,
  `secret` varchar(60) NOT NULL DEFAULT 'secret',
  `server` varchar(64) DEFAULT NULL,
  `community` varchar(50) DEFAULT NULL,
  `description` varchar(200) DEFAULT 'RADIUS Client',
  PRIMARY KEY (`id`),
  KEY `nasname` (`nasname`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

--
-- 导出表中的数据 `nas`
--

INSERT INTO `nas` (`id`, `nasname`, `shortname`, `type`, `ports`, `secret`, `server`, `community`, `description`) VALUES
(1, '192.168.22.131', '192.168.22.131', 'other', 1812, '123456', NULL, NULL, 'RADIUS Client'),
(2, '192.168.22.132', '192.168.22.132', 'other', 1812, '123456', NULL, NULL, 'RADIUS Client'),
(7, '192.168.22.6', '192.168.22.6', 'other', 1812, '123456', '', '', 'RADIUS Client'),
(9, '192.168.22.7', '192.168.22.7', 'other', 1812, '123456', '', '', 'RADIUS Client'),
(11, '192.168.22.112', '192.168.22.112', 'other', 1812, '123456', '', '', 'RADIUS Client'),
(13, '192.168.22.124', '192.168.22.124', 'other', 1812, '123456', '', '', 'RADIUS Client');

-- --------------------------------------------------------

--
-- 表的结构 `radacct`
--

CREATE TABLE IF NOT EXISTS `radacct` (
  `radacctid` bigint(21) NOT NULL AUTO_INCREMENT,
  `acctsessionid` varchar(64) NOT NULL DEFAULT '',
  `acctuniqueid` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `realm` varchar(64) DEFAULT '',
  `nasipaddress` varchar(15) NOT NULL DEFAULT '',
  `nasportid` varchar(15) DEFAULT NULL,
  `nasporttype` varchar(32) DEFAULT NULL,
  `acctstarttime` datetime DEFAULT NULL,
  `acctstoptime` datetime DEFAULT NULL,
  `acctsessiontime` int(12) DEFAULT NULL,
  `acctauthentic` varchar(32) DEFAULT NULL,
  `connectinfo_start` varchar(50) DEFAULT NULL,
  `connectinfo_stop` varchar(50) DEFAULT NULL,
  `acctinputoctets` bigint(20) DEFAULT NULL,
  `acctoutputoctets` bigint(20) DEFAULT NULL,
  `calledstationid` varchar(50) NOT NULL DEFAULT '',
  `callingstationid` varchar(50) NOT NULL DEFAULT '',
  `acctterminatecause` varchar(32) NOT NULL DEFAULT '',
  `servicetype` varchar(32) DEFAULT NULL,
  `framedprotocol` varchar(32) DEFAULT NULL,
  `framedipaddress` varchar(15) NOT NULL DEFAULT '',
  `acctstartdelay` int(12) DEFAULT NULL,
  `acctstopdelay` int(12) DEFAULT NULL,
  `xascendsessionsvrkey` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`radacctid`),
  KEY `username` (`username`),
  KEY `framedipaddress` (`framedipaddress`),
  KEY `acctsessionid` (`acctsessionid`),
  KEY `acctsessiontime` (`acctsessiontime`),
  KEY `acctuniqueid` (`acctuniqueid`),
  KEY `acctstarttime` (`acctstarttime`),
  KEY `acctstoptime` (`acctstoptime`),
  KEY `nasipaddress` (`nasipaddress`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=304404 ;

--
-- Triggers `radacct`
--
DROP TRIGGER IF EXISTS `radius`.`monthly_limit`;
DELIMITER //
CREATE TRIGGER `radius`.`monthly_limit` AFTER UPDATE ON `radius`.`radacct`
 FOR EACH ROW BEGIN

call update_session_time(new.username);
call update_session_octets(new.username);
	
END
//
DELIMITER ;

--
-- 导出表中的数据 `radacct`
--


-- --------------------------------------------------------

--
-- 表的结构 `radcheck`
--

CREATE TABLE IF NOT EXISTS `radcheck` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '==',
  `value` varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `username` (`username`(32))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=782 ;

--
-- 导出表中的数据 `radcheck`
--

INSERT INTO `radcheck` (`id`, `username`, `attribute`, `op`, `value`) VALUES
(739, 'test', 'User-Password', ':=', 'test'),
(740, 'lj', 'User-Password', ':=', 'lj'),
(741, 'mj', 'User-Password', ':=', '123456'),
(742, 'he', 'User-Password', ':=', '111111'),
(743, 'sam', 'User-Password', ':=', '123456'),
(757, 'wangwu', 'User-Password', ':=', '222222'),
(763, 'heliu', 'User-Password', ':=', '111111'),
(765, 'www', 'User-Password', ':=', '111111'),
(767, 'fdsa', 'User-Password', ':=', '123'),
(769, 'ddd', 'User-Password', ':=', 'ddd'),
(771, 'ccc', 'User-Password', ':=', 'ccc'),
(773, 'bbb', 'User-Password', ':=', 'bbb'),
(775, 'eee', 'User-Password', ':=', 'eee'),
(777, 'test0001', 'User-Password', ':=', '111111'),
(779, 'test0002', 'User-Password', ':=', '111111');

-- --------------------------------------------------------

--
-- 表的结构 `radgroupcheck`
--

CREATE TABLE IF NOT EXISTS `radgroupcheck` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '==',
  `value` varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `groupname` (`groupname`(32))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=52 ;

--
-- 导出表中的数据 `radgroupcheck`
--

INSERT INTO `radgroupcheck` (`id`, `groupname`, `attribute`, `op`, `value`) VALUES
(4, 'group', 'Simultaneous-Use', ':=', '1'),
(3, 'group', 'Auth-Type', ':=', 'Local'),
(47, 'testgroup', 'Max-Monthly-Traffic', ':=', '1073741824'),
(48, 'testgroup', 'Max-Monthly-Time', ':=', '360000'),
(51, 'TIME_LIMIT_50_G', 'Max-Monthly-Traffic', ':=', '6710886400');

-- --------------------------------------------------------

--
-- 表的结构 `radgroupreply`
--

CREATE TABLE IF NOT EXISTS `radgroupreply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '=',
  `value` varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `groupname` (`groupname`(32))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

--
-- 导出表中的数据 `radgroupreply`
--

INSERT INTO `radgroupreply` (`id`, `groupname`, `attribute`, `op`, `value`) VALUES
(41, 'testgroup', 'Acct-Interim-Interval', ':=', '1');

-- --------------------------------------------------------

--
-- 表的结构 `radippool`
--

CREATE TABLE IF NOT EXISTS `radippool` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pool_name` varchar(30) NOT NULL,
  `framedipaddress` varchar(15) NOT NULL DEFAULT '',
  `nasipaddress` varchar(15) NOT NULL DEFAULT '',
  `calledstationid` varchar(30) NOT NULL,
  `callingstationid` varchar(30) NOT NULL,
  `expiry_time` datetime DEFAULT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `pool_key` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- 导出表中的数据 `radippool`
--


-- --------------------------------------------------------

--
-- 表的结构 `radreply`
--

CREATE TABLE IF NOT EXISTS `radreply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '=',
  `value` varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `username` (`username`(32))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=280 ;

--
-- 导出表中的数据 `radreply`
--

INSERT INTO `radreply` (`id`, `username`, `attribute`, `op`, `value`) VALUES
(277, 'test0001', 'Framed-IP-Address', ':=', '10.0.0.100');

-- --------------------------------------------------------

--
-- 表的结构 `radusergroup`
--

CREATE TABLE IF NOT EXISTS `radusergroup` (
  `username` varchar(64) NOT NULL DEFAULT '',
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT '1',
  KEY `username` (`username`(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 导出表中的数据 `radusergroup`
--

INSERT INTO `radusergroup` (`username`, `groupname`, `priority`) VALUES
('test', 'testgroup', 1),
('bbb', 'testgroup', 1),
('mj', 'testgroup', 1),
('sam', 'testgroup', 1),
('eee', 'testgroup', 1),
('fdsa', 'TIME_LIMIT_50_G', 1),
('www', 'testgroup', 1),
('heliu', 'TIME_LIMIT_50_G', 1),
('ccc', 'testgroup', 1);

-- --------------------------------------------------------

--
-- 表的结构 `test`
--

CREATE TABLE IF NOT EXISTS `test` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `proc` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4077236 ;

--
-- 导出表中的数据 `test`
--


-- --------------------------------------------------------

--
-- 表的结构 `userdate`
--

CREATE TABLE IF NOT EXISTS `userdate` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `begin` date NOT NULL DEFAULT '2012-01-01',
  `end` date NOT NULL DEFAULT '2013-01-01',
  `exceed` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  `forbidden` varchar(10) CHARACTER SET utf8 NOT NULL DEFAULT 'false',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=46 ;

--
-- 导出表中的数据 `userdate`
--

INSERT INTO `userdate` (`id`, `username`, `begin`, `end`, `exceed`, `forbidden`) VALUES
(1, 'wangwu', '2012-01-01', '2013-01-01', 'false', 'false'),
(2, 'test', '2012-01-01', '2013-01-01', 'false', 'false'),
(7, 'lj', '2012-01-01', '2013-01-01', 'false', 'false'),
(6, 'heliu', '2012-04-01', '2013-01-01', 'false', 'false'),
(8, 'he', '2012-01-01', '2013-01-01', 'false', 'false'),
(9, 'www', '0000-00-00', '0000-00-00', 'false', 'false'),
(11, 'fdsa', '0000-00-00', '0000-00-00', 'false', 'false'),
(13, 'ddd', '0000-00-00', '0000-00-00', 'false', 'false'),
(15, 'ccc', '0000-00-00', '0000-00-00', 'false', 'false'),
(17, 'bbb', '0000-00-00', '0000-00-00', 'false', 'false'),
(19, 'test', '2012-01-01', '2013-01-01', 'false', 'false'),
(21, 'eee', '0000-00-00', '0000-00-00', 'false', 'false'),
(23, 'test0001', '0000-00-00', '0000-00-00', 'false', 'false'),
(25, 'test0002', '0000-00-00', '0000-00-00', 'false', 'false'),
(35, 'mj', '2012-01-01', '2013-01-01', 'false', 'false'),
(37, 'sam', '2012-01-01', '2013-01-01', 'false', 'false'),
(39, 'eee', '2012-01-01', '2013-01-01', 'false', 'false'),
(41, 'bbb', '2012-01-01', '2013-01-01', 'false', 'false'),
(43, 'ccc', '2012-01-01', '2013-01-01', 'false', 'false'),
(45, 'www', '2012-01-01', '2013-01-01', 'false', 'false');

-- --------------------------------------------------------

--
-- 表的结构 `userinfo`
--

CREATE TABLE IF NOT EXISTS `userinfo` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `server` varchar(50) NOT NULL,
  `pid` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '0',
  `uploadLimit` int(32) NOT NULL DEFAULT '131072',
  `downloadLimit` int(32) NOT NULL DEFAULT '131072',
  `timeLimit` int(20) NOT NULL DEFAULT '0',
  `trafficLimit` int(30) NOT NULL DEFAULT '0',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=28 ;

--
-- 导出表中的数据 `userinfo`
--

INSERT INTO `userinfo` (`id`, `username`, `server`, `pid`, `uploadLimit`, `downloadLimit`, `timeLimit`, `trafficLimit`, `time`) VALUES
(3, 'wangwu', 'off', '0', 262144, 524288, 0, 0, '2012-03-28 03:50:25'),
(5, 'heliu', 'off', '0', 524288, 1048576, 0, 0, '2012-03-28 17:25:59'),
(17, 'test', 'off', '0', 131072, 131072, 0, 0, '2012-03-30 05:59:38'),
(7, 'www', 'off', '0', 524288, 1048576, 0, 0, '2012-03-30 00:37:53'),
(9, 'fdsa', 'off', '0', 524288, 1048576, 0, 0, '2012-03-30 00:52:04'),
(11, 'ddd', 'off', '0', 131072, 131072, 0, 0, '2012-03-30 01:05:01'),
(13, 'ccc', 'off', '0', 131072, 131072, 0, 0, '2012-03-30 01:07:23'),
(15, 'bbb', 'off', '0', 131072, 131072, 0, 0, '2012-03-30 01:08:37'),
(19, 'eee', 'off', '0', 524288, 1048576, 0, 2147483647, '2012-03-30 19:57:02'),
(21, 'test0001', 'off', '0', 262144, 524288, 0, 0, '2012-03-31 23:44:20'),
(23, 'test0002', 'off', '0', 262144, 524288, 0, 0, '2012-03-31 23:55:52'),
(27, 'sam', 'off', '0', 131072, 131072, 0, 0, '2012-04-10 04:02:51');
