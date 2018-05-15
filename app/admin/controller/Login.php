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
use app\admin\model\Admin as AdminModel;
use think\Db;

class Login extends Common 
{
	protected function _initialize()
	{
		parent::_initialize();
	}
    /**
     * 登录显示
     */
	public function login()
	{
		if($this->check_admin_login()) $this->redirect('admin/Index/index');
		return $this->fetch();
	}
    /**
     * 验证码
     */
	public function verify()
    {
		if($this->check_admin_login()) $this->redirect('admin/Index/index');	
		return $this->verify_build('aid');
    }

	/**
     * 登录验证
     */
	public function runlogin()
	{
		if (!request()->isAjax())
		{
			$this->error("提交方式错误！",url('admin/Login/login'));
		}
		else
		{
			if(config('geetest.geetest_on'))
			{
                if(!geetest_check(input('post.')))
				{
                    $this->error('验证不通过',url('admin/Login/login'));
                };
            }
			else
			{
				$this->verify_check('aid');
            }
			$admin_username=input('admin_username');
			$password=input('admin_pwd');
			$rememberme=input('rememberme');
			//1、是否属于登录超过五次错误，且时间在两小时之内的人员
			$this->verify_five($admin_username);


			$admin=new AdminModel;
			if($admin->login($admin_username,$password,$rememberme))
			{
				$this->_update_failure_times($admin_username,1);              //登录失败次数清0
				$this->_safe_code($admin_username);                           //设置安全码
				$this->success('恭喜您，登陆成功',url('admin/Index/index'));
			}
			else
			{
				$this->_update_failure_times($admin_username);             //登录错误次数 +1
				$this->error($admin->getError(),url('admin/Login/login'));
			}
		}
	}


	/**
	 * 验证是否属于五次验证范围内；且时间在两小时内
	 *
	 * @param $admin_username
	 * @return bool
	 */
	private function verify_five($admin_username)
	{
		$admin_info = Db::name('admin')->field('failure_times,last_time')->where('admin_username',$admin_username)->find();
		$min_time = time() - 7200;

		//如果失败次数大于等于五次
		if(isset($admin_info['failure_times'])&&$admin_info['failure_times'] > 2)
		{
			//大于五次，但时间已过了两小时
			if(isset($admin_info['last_time'])&&($admin_info['last_time'] < $min_time))
			{
				$this->_update_last_time($admin_username);          //更新上次登录时间
//				return true;
			}
			//大于五次,但时间未超过两小时
			else
			{
				//不需更新上次登录时间
				$this->error("登录错误次数超过三次，两个小时内不能登录",url('admin/Login/login'));
			}
		}
		else
		{
			$this->_update_last_time($admin_username);         //更新上次登录时间
//			return true;
		}
	}

	/**
	 * 更新最后一次登录时间
	 *
	 * @param $admin_username
	 * @throws \think\Exception
	 */
	private function _update_last_time($admin_username)
	{
		$admin_info = Db::name('admin')->where('admin_username',$admin_username)->value('admin_id');
		$admin['admin_id'] = $admin_info;
		$admin['last_time'] = time();
		Db::name('admin')->update($admin);
	}

	/**
	 * 更新最新的
	 *
	 * @param $admin_username
	 */
	private function _safe_code($admin_username)
	{
		$admin_info = Db::name('admin')->field('admin_id,admin_username,safety_code')->where('admin_username',$admin_username)->find();
		$admin_info['safety_code'] =  md5(time());
		Db::name('admin')->update($admin_info);

		//加入cookie
		cookie('safety_code', $admin_info);
	}


	/**
	 * 更新失败次数  【默认次数加1】
	 *
	 * @param $admin_username
	 * @param int $time
	 * @throws \think\Exception
	 */
	private function _update_failure_times($admin_username,$time = 99)
	{
		$admin_info = Db::name('admin')->field('admin_id,failure_times')->where('admin_username',$admin_username)->find();

		//次数加1
		if($time == 99)
		{
			$admin_info['failure_times'] = $admin_info['failure_times']+1;
		}
		//次数清0
		else
		{
			$admin_info['failure_times'] = 0;
		}
		Db::name('admin')->update($admin_info);
	}


    /**
     * 退出登录
     */
	public function logout()
	{
		session('admin_auth',null);
		session('admin_auth_sign',null);
		cookie('aid', null);
        cookie('signin_token', null);
		$this->redirect('admin/Login/login');
	}
}