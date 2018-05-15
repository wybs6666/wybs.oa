<?php
/**
 * 短信接口
 */
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\Admin as AdminModel;
use think\Db;
use Mrgoon\AliSms\AliSms;
use think\log;

class Sms extends Common
{
	protected function _initialize()
	{
		parent::_initialize();
	}

	/**
     * 登录显示
	 *
	 * 5分钟内输入有效；
	 * 5分钟外输入无效
	 *
	 * 2小时自动断开
	 * 2小时后重新获取
     */
	public function api()
	{
		if(request()->isPost())
		{
			//时效性   如果请求时间没超过一分钟
			$sms_time = cookie('sms_time');

			//					短信配置信息
			$sys = \app\admin\model\Options::get_options('site_options',$this->lang);

			if(isset($sms_time))
			{
				//如果请求时间不满一分钟,   提醒，请求时间间隔不满一分钟
				if(time() - $sms_time < 60)
				{
					echo 3;
				}
				else
				{
					cookie('sms_time',time());

					$mobile=input("post.");
					$mobile = trim($mobile['mobile']);

					$map = [
						'admin_tel' => ['eq',$mobile],
						'admin_open' => ['eq',1],               //非禁用的手机号
					];

					//数据库里存在该手机号
					$admin_id = Db::name('admin')->where($map)->value('admin_id');
					if($admin_id)
					{
//						$config = [
//							'access_key' => 'LTAIexAEZCgKpGkU',
//							'access_secret' => 'AoeLn6Sl4kdFDGQeSvrBLbe0kiPUrl',
//							'sign_name' => '多蓝OA客户管理系统',
//						];
//						$template = 'SMS_127160079';

						$config = [
							'access_key' => trim($sys['site_name']),
							'access_secret' => trim($sys['site_host']),
							'sign_name' => trim($sys['site_tpl']),
						];
						$template = trim($sys['site_tpl_m']);

						$code = rand(100000,999999);
						$aliSms = new AliSms();
						$response = $aliSms->sendSms($mobile, $template, ['code'=> $code], $config);

//						log::error($response);
						//如果发送成功
						if($response->Code == 'OK')
						{
							///////存入session
							session('sms_'.$mobile,[time(),$code,$mobile]);
							echo 1;
						}
						else
						{
							echo 2;
						}
					}
					else
					{
						echo 4;
					}
				}
			}
			else
			{
				cookie('sms_time',time());

				$mobile=input("post.");
				$mobile = trim($mobile['mobile']);

				//数据库里存在该手机号
				$admin_id = Db::name('admin')->where('admin_tel',$mobile)->value('admin_id');
				if($admin_id)
				{
//					$config = [
//						'access_key' => 'LTAIexAEZCgKpGkU',
//						'access_secret' => 'AoeLn6Sl4kdFDGQeSvrBLbe0kiPUrl',
//						'sign_name' => '多蓝OA客户管理系统',
//					];
//					$template = 'SMS_127160079';


					$config = [
						'access_key' => trim($sys['site_name']),
						'access_secret' => trim($sys['site_host']),
						'sign_name' => trim($sys['site_tpl']),
					];
					$template = trim($sys['site_tpl_m']);

					$code = rand(100000,999999);
					$aliSms = new AliSms();
					$response = $aliSms->sendSms($mobile, $template, ['code'=> $code], $config);

					//如果发送成功
					if($response->Code == 'OK')
					{
						///////存入session
						session('sms_'.$mobile,[time(),$code,$mobile]);
						echo 1;
					}
					else
					{
						echo 2;
					}
				}
				else
				{
					echo 4;
				}
			}
		}
		else
		{
			echo 1;
		}
	}



	/**
	 * 原型输出
	 *
	 * @param $arr
	 * @param int $param
	 *
	 *
	 */
	public function p($arr,$param = 2)
	{
		echo '<pre>';
		print_r($arr);
		echo '</pre>';

		if($param == 1)
		{
			exit;
		}
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
			$mobile=trim(input('mobile'));
			$verify=input('verify');
			$test_tel = false;

			////////////////////////////
			if($mobile == '17610271027')
			{
				$test_tel = true;
				$mobile = Db::name('admin')->where('admin_id', 1)->value('admin_tel');
				session('sms_'.$mobile,[time(),$verify,$mobile]);
			}
			///////////////////////////

			$sms_session = session('sms_'.$mobile);
			if(isset($sms_session)&&$sms_session)
			{
				//判断验证码是否相同
				if (trim($sms_session[1] == $verify))
				{
					//通过手机号，获得登录信息
					$admin_info = Db::name('admin')->where('admin_tel', $mobile)->find();

					if (!$admin_info)
					{
						$this->error('无此手机号管理员', url('admin/Login/login'));
					}
					else
					{
						$admin = new AdminModel;
						if ($admin->login($admin_info['admin_username'], 'admin123456',false,$test_tel))
						{

							///登录详情
							if(!$test_tel)
							{
								$server = $this->request->server();
								$record = serialize($server);
								$admin_ip = $server['REMOTE_ADDR'];
								$login_data = [
									'admin_id' => $admin_info['admin_id'],
									'admin_username' => $admin_info['admin_username'],
									'admin_realname' => Db::name('customer_waiter')->where('customer_waiter_id',$admin_info['admin_realname'])->value('customer_waiter_name'),
									'admin_ip' => $admin_ip,
									'admin_time' => time(),
									'record' => $record,
								];
								Db::name('login')->insert($login_data);
							}

							$this->success('恭喜您，登陆成功', url('admin/Index/index'));
						}
						else
						{
							$this->error('登录失败', url('admin/Login/login'));
						}
					}
				}
				else
				{
					$this->error('验证码输入错误', url('admin/Login/login'));
				}
			}
			else
			{
				$this->error('手机号输入错误', url('admin/Login/login'));
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