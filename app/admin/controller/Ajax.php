<?php
// +----------------------------------------------------------------------
// | SHULAN
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: GZL [数蓝]
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;

class Ajax
{
	/*
     * 返回行政区域json字符串
     */
	public function getRegion()
	{
		$map['pid']=input('pid');
		$map['type']=input('type');
		$list=Db::name("region")->where($map)->select();
		return json($list);
	}
	/*
     * 返回模块下控制器json字符串
     */
	public function getController()
	{
		$module=input('request_module','admin');
		$list=\ReadClass::readDir(APP_PATH . $module. DS .'controller');
		return json($list);
	}
}