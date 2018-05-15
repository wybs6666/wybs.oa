-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2018-04-20 02:28:37
-- 服务器版本： 5.7.17-log
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `demo_oa`
--

-- --------------------------------------------------------

--
-- 表的结构 `yf_cust`
--

CREATE TABLE `yf_cust` (
  `id` int(11) NOT NULL COMMENT '自增id',
  `cust_contact` varchar(20) DEFAULT NULL COMMENT '客户联系人',
  `cust_sex` tinyint(4) DEFAULT '1' COMMENT '客户性别1;男；2女',
  `company_id` int(5) NOT NULL COMMENT '所属公司id值',
  `cust_mobile` varchar(12) DEFAULT NULL COMMENT '客户手机号',
  `cust_tel` varchar(15) DEFAULT NULL COMMENT '客户座机号',
  `cust_remark` varchar(200) DEFAULT NULL COMMENT '客户备注',
  `cust_postal_code` varchar(10) NOT NULL COMMENT '客户邮编',
  `cust_fax` varchar(25) NOT NULL COMMENT '客户传真',
  `cust_micro_blog` varchar(100) DEFAULT NULL COMMENT '微博地址',
  `cust_level` tinyint(4) NOT NULL COMMENT '客户级别',
  `cust_jibie` tinyint(4) NOT NULL DEFAULT '1' COMMENT '客户评级',
  `cust_add_time` int(11) NOT NULL COMMENT '添加时间',
  `cust_belong` int(4) NOT NULL COMMENT '跟进人',
  `cust_auto` tinyint(4) NOT NULL DEFAULT '1' COMMENT '添加人id号',
  `cust_update_time` int(10) NOT NULL COMMENT '最近一次修改时间',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='客户详情表';

--
-- 转存表中的数据 `yf_cust`
--

INSERT INTO `yf_cust` (`id`, `cust_contact`, `cust_sex`, `company_id`, `cust_mobile`, `cust_tel`, `cust_remark`, `cust_postal_code`, `cust_fax`, `cust_micro_blog`, `cust_level`, `cust_jibie`, `cust_add_time`, `cust_belong`, `cust_auto`, `cust_update_time`, `remark`) VALUES
(1, '国女', 2, 48, '18210921258', '', '', '', '', '', 2, 1, 1513046039, 9, 8, 1513071999, ''),
(2, '金女士', 2, 49, '13810484699', '13810484699', '', '', '', '', 2, 1, 1513066798, 12, 8, 1513071999, ''),
(3, '张先生', 1, 50, '13681174771', '', '', '', '', '', 2, 1, 1513068510, 4, 8, 1513071999, ''),
(4, '苗先生', 1, 51, '18612923995', '', '', '', '', '', 2, 1, 1513068590, 12, 8, 1513071999, ''),
(5, '徐先生', 1, 52, '13810315977', '', '', '', '', '', 2, 1, 1513068692, 5, 8, 1513071999, ''),
(6, '姜 ', 1, 53, '15330261417', '', '', '', '', '', 2, 1, 1513068764, 9, 8, 1513071999, ''),
(7, '陈女士', 2, 54, '15230658411', '', '', '', '', '', 2, 1, 1513068961, 4, 8, 1513071999, ''),
(8, '郭睿', 1, 55, '', '', '', '', '', 'g552032790', 2, 1, 1513069054, 9, 8, 1513071999, ''),
(9, '谭明', 1, 48, '', '', '', '', '', '', 2, 1, 1513071999, 11, 8, 1514304000, ''),
(10, '唐女士', 2, 77, '18210187618', '18210187618', '', '', '', 'QQ914714008', 2, 1, 1513248537, 12, 8, 1513180800, ''),
(11, '王淑真', 2, 76, '13552856252', '13552856252', '', '', '', '', 2, 1, 1513248716, 12, 8, 1513180800, ''),
(12, '袁涛生', 1, 75, '', '', '', '', '', 'QQ2217177302', 2, 1, 1513248846, 12, 8, 1513180800, ''),
(13, '张小欠', 2, 74, '18090810212	', '', '', '', '', '', 2, 1, 1513248905, 12, 8, 1513180800, ''),
(14, 'wendy', 2, 73, '15801486365', '', '', '', '', '', 2, 1, 1513248960, 12, 8, 1513180800, ''),
(15, '甘露', 2, 72, '', '', '', '', '', 'QQ65107777', 2, 1, 1513249019, 11, 8, 1513180800, ''),
(16, 'vicky', 2, 71, '13810538330', '', '', '', '', '', 2, 1, 1513249073, 12, 8, 1513249073, ''),
(17, '无名', 1, 85, '', '', '', '', '', 'yinsoncen', 2, 1, 1514024823, 12, 8, 1514024823, ''),
(18, '无名', 2, 84, '13910839153', '13910839153', '', '', '', '', 2, 1, 1514024859, 12, 8, 1514024859, ''),
(19, '无名', 1, 83, '18001360103', '', '', '', '', '', 2, 1, 1514024908, 12, 8, 1514024908, ''),
(21, '修正骨肽康医药', 1, 81, '18801377709', '158125858659', '', '', '', '', 2, 1, 1514025022, 12, 8, 1514025022, ''),
(22, 'dandelion', 2, 80, '15264200001', '1111111111', '', '', '', '', 2, 1, 1514025055, 12, 8, 1514025055, ''),
(29, '1245669877755', 1, 4, '15828585698', '', '', '', '', '', 1, 1, 1515645945, 5, 5, 1515645945, ''),
(30, '012548', 1, 102, '15812585869', '', '', '', '', '', 2, 1, 1515662325, 12, 1, 1515662325, ''),
(31, 'aaaaaaa', 1, 103, '15812585869', '', '', '', '', '', 1, 1, 1515663041, 27, 1, 1516723200, ''),
(32, '0000000', 1, 5, '15812585869', '', '', '', '', '', 1, 1, 1516179149, 4, 1, 1516179149, ''),
(33, '测试一个新的0215', 1, 13, '15812585869', '', '', '', '', '', 1, 1, 1516785968, 2, 1, 1516785968, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `yf_cust`
--
ALTER TABLE `yf_cust`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `yf_cust`
--
ALTER TABLE `yf_cust`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id', AUTO_INCREMENT=34;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
