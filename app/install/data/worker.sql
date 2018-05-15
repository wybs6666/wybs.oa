SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `yf_department` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT '部门名称',
  `description` text NOT NULL COMMENT '部门描述',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='部门管理';

ALTER TABLE `yf_department`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `yf_department`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;