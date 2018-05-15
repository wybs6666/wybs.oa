SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `yf_login` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(5) UNSIGNED NOT NULL,
  `admin_username` varchar(20) NOT NULL COMMENT '管理员用户名',
  `admin_realname` varchar(20) DEFAULT NULL COMMENT '真实姓名',
  `admin_ip` varchar(20) DEFAULT NULL COMMENT 'IP地址',
  `admin_time` int(11) UNSIGNED DEFAULT '0' COMMENT '登陆时间',
  `record` text NOT NULL COMMENT '部门描述'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='登录详情';

ALTER TABLE `yf_login`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `yf_login`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;