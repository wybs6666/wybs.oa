<?php
// +----------------------------------------------------------------------
// | SHULAN
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: GZL [数蓝]
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\AuthRule;
use think\Db;
use think\log;

class Base extends Common
{
	protected $company_official_website = null;
	public function _initialize()
	{
		parent::_initialize();
		/**
		 * 未登录
		 */
		if (!$this->check_admin_login())
		{
			$this->redirect('admin/Login/login');
		}
		else
		{
			//如果  手机验证码超过两小时
			$admin_id = $this->_get_admin_id();
			$admin_info = Db::name('admin')->field('admin_tel')->where('admin_id', $admin_id)->find();
			$sms_session = session('sms_'.$admin_info['admin_tel']);
			$time_1 = $sms_session[0];
			$time_2 = time();
			$time_3 = $time_2 - $time_1;
			log::error($time_3);

			//超过两小时，直接退出
			if($time_3 > 14400)
			{
				$this->error('验证码已过期',url('admin/Sms/logout'));
			}
		}

		$auth = new AuthRule;

		//该操作id号
		$id_curr = $auth->get_url_id();

		if (!$auth->check_auth($id_curr)) $this->error('没有权限', url('admin/Index/index'));

		/**
		 * 获取有权限的菜单tree
		 */
		$menus = $auth->get_admin_menus();
		$this->assign('menus', $menus);

		/**
		 * 当前方法倒推到顶级菜单ids数组
		 */
		$menus_curr = $auth->get_admin_parents($id_curr);
		$this->assign('menus_curr', $menus_curr);

		/**
		 * 取当前操作菜单父节点下菜单 当前菜单id(仅显示状态)
		 */
		$menus_child = $auth->get_admin_parent_menus($id_curr);
		$this->assign('menus_child', $menus_child);
		$this->assign('id_curr', $id_curr);
		$this->assign('admin_avatar', session('admin_auth.admin_avatar'));

		$this->_get_user();
		$this->_get_current_adminname();


		///////测试账号不能执行的操作  S
//		$param = [$this->_get_admin_id()];
//		$IS_TEST = \think\Hook::listen('test_admin',$param);
//		if($IS_TEST)
//		{
//			$action = \think\Request::instance()->action();
//			if(strpos($action,'run')||strpos($action,'del'))
//			{
//				$this->error('测试账号不能执行此操作','index/index');
//			}
//		}
		///////测试账号不能执行的操作  E

	}

	/**
	 * 获得员工库
	 */
	private function _get_user()
	{
		$user = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$user_1 = $user;
		foreach ($user as $key => &$val) {
			$is_has = Db::name('admin')->where('admin_realname', $key)->value('admin_id');
			if ($is_has) {
				$val = $val . '&nbsp;&nbsp;&nbsp;√</span>';
			}
		}
		$this->assign('user_list', $user);
		return $user_1;
	}

	/**
	 * 调试原型输出
	 *
	 * @param $arr
	 * @param int $param
	 */
	protected function p($arr, $param = 2)
	{
		echo '<pre>';
		print_r($arr);
		echo '</pre>';

		if ($param == 1) {
			exit;
		}
	}

	/**
	 * 打印最后一条测试数据
	 *
	 */
	protected function test_output()
	{
		$output_data = Db::name('demo')->order(['id' => 'desc'])->limit(1)->select();
		$this->p(unserialize($output_data[0]['intro']), 1);
	}

	/**
	 * 存入一条测试数据
	 *
	 * @param $intro
	 */
	protected function test_input($intro)
	{
		$intro = serialize($intro);
		Db::name('demo')->insert(array('intro' => $intro));
	}


	/**
	 * 写入东西
	 *
	 * @param $things
	 * @param int $param
	 */
	function write($things, $param = 2)
	{
		$myfile = fopen("remark.txt", "a+") or die("Unable to open file!");

		if (is_array($things)) {
			foreach ($things as $key => $val) {
				$txt = $key . '-------------' . $val . "\n";
				fwrite($myfile, $txt);
			}
		} else {
			$txt = $things . "\n";
			fwrite($myfile, $txt);
		}
		fclose($myfile);


		if ($param == 1) {
			exit;
		}
	}


	/**
	 * 获取当前管理员id号
	 * @return mixed
	 */
	protected function _get_admin_id()
	{
		$admin_info = $_SESSION['think']['admin_auth'];
		$admin_id = $admin_info['aid'];                           //管理员id
		$admin_group = db('auth_group_access')->where('uid', $admin_id)->column('group_id');

		$this->assign('now_admin_id', $admin_id);
		$this->assign('now_admin_group', $admin_group[0]);

		return $admin_id;
	}


	/**
	 * 获取当前管理员所在的组id号
	 * @return mixed
	 */
	protected function _get_admin_group_id()
	{
		$admin_info = $_SESSION['think']['admin_auth'];
		$admin_id = $admin_info['aid'];                           //管理员id
		$admin_group = db('auth_group_access')->where('uid', $admin_id)->value('group_id');

		return $admin_group;
	}

	/**
	 * 获取当前的 昵称
	 */
	protected function _get_current_adminname()
	{
		$admin_info = $_SESSION['think']['admin_auth']['admin_realname'];
		$user = $this->_get_user();

		if (isset($user[$admin_info]) && $user[$admin_info]) {
			$this->assign('admin_realname', $user[$admin_info]);
		} else {
			$this->assign('admin_realname', $_SESSION['think']['admin_auth']['admin_username']);
		}
	}


	/**
	 * 公司官网
	 */
	protected function get_official()
	{
		$this->company_official_website = Db::name('company')->column('company_id,official_website');
	}
}


















