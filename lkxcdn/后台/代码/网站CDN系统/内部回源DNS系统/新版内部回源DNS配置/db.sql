-- phpMyAdmin SQL Dump
-- version 2.11.11.1
-- http://www.phpmyadmin.net
--
-- ����: localhost
-- ��������: 2013 �� 06 �� 27 �� 14:10
-- �������汾: 5.0.95
-- PHP �汾: 5.1.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- ���ݿ�: `squid_dns`
--
CREATE DATABASE `squid_dns` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `squid_dns`;

-- --------------------------------------------------------

--
-- ��Ľṹ `cnc`
--

CREATE TABLE IF NOT EXISTS `cnc` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `ttl` int(11) default NULL,
  `rdtype` varchar(255) default NULL,
  `rdata` varchar(255) default NULL,
  `status` char(50) default 'true',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- �������е����� `cnc`
--

INSERT INTO `cnc` (`id`, `name`, `ttl`, `rdtype`, `rdata`, `status`) VALUES
(1, '.', 300, 'SOA', 'ns. root. 2011012600 3600 900 3600 300', 'true'),
(2, '.', 300, 'NS', 'ns.', 'true'),
(3, 'ns', 300, 'A', '183.60.46.164', 'true'),
(4, 'www.efly.cc', 300, 'A', '121.9.13.185', 'true'),
(5, 'www.rjidc.cn', 300, 'A', '14.17.120.4', 'true');

-- --------------------------------------------------------

--
-- ��Ľṹ `ct`
--

CREATE TABLE IF NOT EXISTS `ct` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `ttl` int(11) default NULL,
  `rdtype` varchar(255) default NULL,
  `rdata` varchar(255) default NULL,
  `status` char(50) default 'true',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- �������е����� `ct`
--

INSERT INTO `ct` (`id`, `name`, `ttl`, `rdtype`, `rdata`, `status`) VALUES
(1, '.', 300, 'SOA', 'ns. root. 2011012600 3600 900 3600 300', 'true'),
(2, '.', 300, 'NS', 'ns.', 'true'),
(3, 'ns', 300, 'A', '183.60.46.164', 'true'),
(4, 'www.efly.cc', 300, 'A', '121.9.13.185', 'true'),
(5, 'www.rjidc.cn', 300, 'A', '14.17.120.4', 'true');
