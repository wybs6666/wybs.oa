<?php
// +----------------------------------------------------------------------
// | SHULAN
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: GZL [数蓝]
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Admin as AdminModel;
use app\admin\model\AuthRule;
use think\Db;
use think\Cache;

class Admin extends Base
{
	/**
	 * 管理员列表
	 */
	public function admin_list()
	{
		$search_name=input('search_name');
		$this->assign('search_name',$search_name);

		$map=array();
		$map['admin_realname'] = array('neq',28);     //屏蔽测试员
		if($search_name)
		{
			$map['admin_username']= array('like',"%".$search_name."%");
		}
		$admin_list=Db::name('admin')->where($map)->order('admin_id')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
		$page = $admin_list->render();

		foreach($admin_list as $key => $row)
		{
			//非超管
			if($this->_get_admin_group_id() != 1)
			{
				$row['is_most_admin'] = 2;
				$group_id = Db::name('auth_group_access')->where('uid',$row['admin_id'])->value('group_id');
				if(isset($group_id)&&$group_id&&($group_id == 1))
				{
					$row['is_most_admin'] = 1;
				}
			}
			else
			{
				$row['is_most_admin'] = 2;
			}

			if((int)trim($row['admin_realname']) != 0)
			{
				$row['admin_realname'] = Db::name('customer_waiter')->where('customer_waiter_id',(int)trim($row['admin_realname']))->value('customer_waiter_name');
			}

			$admin_list[$key] = $row;
		}



		$this->assign('admin_list',$admin_list);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/**
	 * 管理员添加
	 */
	public function admin_add()
	{
		$this->_get_auth_group();
		return $this->fetch();
	}

	/**
	 * 根据管理员获得 管理员组下拉
	 */
	private function _get_auth_group()
	{
		$auth_group=Db::name('auth_group')->select();
		$aa = $this->_get_admin_group_id();

//		不是超级管理员
		if($aa != 1)
		{
			foreach($auth_group as $key => $val)
			{
				if($val['id'] == 1)
				{
					unset($auth_group[$key]);
				}
			}
		}
		$this->assign('auth_group',$auth_group);
	}

	/**
	 * 管理员添加操作
	 */
	public function admin_runadd()
	{
		$is_has = db('admin')->where('admin_username',input('admin_username'))->value('admin_id');
		if($is_has)
		{
			$this->error('管理员已存在,重新添加',url('admin/Admin/admin_add'));
		}

		$tel_has = db('admin')->where('admin_tel',input('admin_tel'))->value('admin_id');
		if($tel_has)
		{
			$this->error('管理员手机号已存在,重新添加',url('admin/Admin/admin_add'));
		}


		$admin_id=AdminModel::add(input('admin_username'),'',input('admin_pwd'),input('admin_email',''),input('admin_tel',''),input('admin_open',0),input('admin_realname',''),input('group_id'));
		if($admin_id){
			$this->success('管理员添加成功',url('admin/Admin/admin_list'));
		}else{
			$this->error('管理员添加失败',url('admin/Admin/admin_list'));
		}
	}
	/**
	 * 管理员修改
	 */
	public function admin_edit()
	{
		$this->_get_auth_group();
		$admin_list=Db::name('admin')->find(input('admin_id'));

		$auth_group_access=Db::name('auth_group_access')->where(array('uid'=>$admin_list['admin_id']))->value('group_id');
		$this->assign('admin_list',$admin_list);
		$this->assign('auth_group_access',$auth_group_access);
		return $this->fetch();
	}


	/**
	 * 管理员修改操作
	 */
	public function admin_runedit()
	{
		$data=input('post.');

		$is_yourself = false;
		if(isset($data['is_yourself'])&&$data['is_yourself'] == 1)
		{
			$is_yourself = true;
			unset($data['is_yourself']);
		}

		///手机号去重：  S
		$map['admin_tel'] = ['eq',$data['admin_tel']];
		$map['admin_id'] = ['neq',$data['admin_id']];
		$is_has = db('admin')->where($map)->value('admin_id');
		if($is_has)
		{
			$this->error('修改失败,该手机号已存在','/admin/admin/admin_edit/admin_id/'.$data['admin_id'].'.html');
		}
		///手机号去重： E


		$rst=AdminModel::edit($data);
		if($rst!==false)
		{
			/**
			 * 自己修改自己的信息
			 */
			if($is_yourself)
			{
				$this->success('管理员修改成功',url('admin/Admin/profile'));
			}
			/**
			 *管理员在管理员列表里修改
			 */
			else
			{
				$this->success('管理员修改成功',url('admin/Admin/admin_list'));
			}
		}else{
			$this->error('管理员修改失败','/admin/admin/admin_edit/admin_id/'.$data['admin_id'].'.html');
		}
	}
	/**
	 * 管理员删除
	 */
	public function admin_del()
	{
		$admin_id=input('admin_id');
		if (empty($admin_id)){
			$this->error('用户ID不存在',url('admin/Admin/admin_list'));
		}
		//对应会员ID
		$member_id=Db::name('admin')->where('admin_id',$admin_id)->value('member_id');
		Db::name('admin')->delete($admin_id);
		//删除对应会员
		if($member_id){
			Db::name('member_list')->delete($member_id);
		}
		$rst=Db::name('auth_group_access')->where('uid',$admin_id)->delete();
		if($rst!==false){
			$this->success('管理员删除成功',url('admin/Admin/admin_list'));
		}else{
			$this->error('管理员删除失败',url('admin/Admin/admin_list'));
		}
	}
	/**
	 * 管理员开启/禁止
	 */
	public function admin_state()
	{
		$id=input('x');
		if (empty($id)){
			$this->error('用户ID不存在',url('admin/Admin/admin_list'));
		}
		$status=Db::name('admin')->where('admin_id',$id)->value('admin_open');//判断当前状态情况
		if($status==1){
			$statedata = array('admin_open'=>0);
			Db::name('admin')->where('admin_id',$id)->setField($statedata);
			$this->success('状态禁止');
		}else{
			$statedata = array('admin_open'=>1);
			Db::name('admin')->where('admin_id',$id)->setField($statedata);
			$this->success('状态开启');
		}
	}


	/**
	 * 用户组列表
	 */
	public function admin_group_list()
	{
		$auth_group=Db::name('auth_group')->select();
		$this->assign('auth_group',$auth_group);
		return $this->fetch();
	}
	/**
	 * 用户组添加
	 */
	public function admin_group_add()
	{
		return $this->fetch();
	}
	/**
	 * 用户组添加操作
	 */
	public function admin_group_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Admin/admin_group_list'));
		}else{
			$sldata=array(
				'title'=>input('title'),
				'status'=>input('status',0),
				'addtime'=>time(),
			);
			$rst=Db::name('auth_group')->insert($sldata);
			if($rst!==false){
				$this->success('用户组添加成功',url('admin/Admin/admin_group_list'));
			}else{
				$this->error('用户组添加失败',url('admin/Admin/admin_group_list'));
			}
		}
	}
	/**
	 * 用户组删除操作
	 */
	public function admin_group_del()
	{
		$rst=Db::name('auth_group')->delete(input('id'));
		if($rst!==false){
			$this->success('用户组删除成功',url('admin/Admin/admin_group_list'));
		}else{
			$this->error('用户组删除失败',url('admin/Admin/admin_group_list'));
		}
	}
	/**
	 * 用户组编辑
	 */
	public function admin_group_edit()
	{
		$group=Db::name('auth_group')->find(input('id'));
		$this->assign('group',$group);
		return $this->fetch();
	}
	/**
	 * 用户组编辑操作
	 */
	public function admin_group_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Admin/admin_group_list'));
		}else{
			$sldata=array(
				'id'=>input('id'),
				'title'=>input('title'),
				'status'=>input('status'),
			);
			Db::name('auth_group')->update($sldata);
			$this->success('用户组修改成功',url('admin/Admin/admin_group_list'));
		}
	}
	/**
	 * 用户组开启/禁用
	 */
	public function admin_group_state()
	{
		$id=input('x');
		$status=Db::name('auth_group')->where('id',$id)->value('status');//判断当前状态情况
		if($status==1){
			$statedata = array('status'=>0);
			Db::name('auth_group')->where('id',$id)->setField($statedata);
			$this->success('状态禁止');
		}else{
			$statedata = array('status'=>1);
			Db::name('auth_group')->where('id',$id)->setField($statedata);
			$this->success('状态开启');
		}
	}
	/**
	 * 权限配置
	 */
	public function admin_group_access()
	{
		$admin_group=Db::name('auth_group')->where(array('id'=>input('id')))->find();
		$data=AuthRule::get_ruels_tree();
		$this->assign('admin_group',$admin_group);
		$this->assign('datab',$data);
		return $this->fetch();
	}
	/**
	 * 权限配置保存
	 */
	public function admin_group_runaccess()
	{
		$new_rules = input('new_rules/a');
		$imp_rules = implode(',', $new_rules);
		$sldata=array(
			'id'=>input('id'),
			'rules'=>$imp_rules,
		);
		if(Db::name('auth_group')->update($sldata)!==false){
			Cache::clear();
			$this->success('权限配置成功',url('admin/Admin/admin_group_list'));
		}else{
			$this->error('权限配置失败',url('admin/Admin/admin_group_list'));
		}
	}
	/*
	 * 管理员信息
	 */
	public function profile()
	{

//		$this->test_output();
		$admin=array();
		if(session('admin_auth.aid'))
		{
			$admin=Db::name('admin')->alias("a")->join(config('database.prefix').'auth_group_access b','a.admin_id =b.uid')
				->join(config('database.prefix').'auth_group c','b.group_id = c.id')
				->where(array('a.admin_id'=>session('admin_auth.aid')))->find();
		}

		$this->assign('admin', $admin);
		return $this->fetch();
	}
	/*
	 * 管理员头像
	 */
	public function avatar()
	{
		$imgurl=input('imgurl');
		//去'/'
		$imgurl=str_replace('/','',$imgurl);
		$url='/data/upload/avatar/'.$imgurl;
		$state=false;
		if(config('storage.storage_open')){
			//七牛
			$upload = \Qiniu::instance();
			$info = $upload->uploadOne('.'.$url,"image/");
			if ($info) {
				$state=true;
				$imgurl= config('storage.domain').$info['key'];
				@unlink('.'.$url);
			}
		}
		if($state !=true){
			//本地
			//写入数据库
			$data['uptime']=time();
			$data['filesize']=filesize('.'.$url);
			$data['path']=$url;
			Db::name('plug_files')->insert($data);
		}
		$admin=Db::name('admin')->where(array('admin_id'=>session('admin_auth.aid')))->find();
		$admin['admin_avatar']=$imgurl;
		$rst=Db::name('admin')->where(array('admin_id'=>session('admin_auth.aid')))->update($admin);
		if($rst!==false){
			session('admin_avatar',$imgurl);
			$this->success ('头像更新成功',url('admin/Admin/profile'));
		}else{
			$this->error ('头像更新失败',url('admin/Admin/profile'));
		}
	}



	//////////////////////////////////////////

	/*
    *  会员登录详情
	 *
    * shulan
    */
	public function login_detail()
	{
		$admin_id = input('company_id');


		$data = Db::name('login')->where('admin_id',$admin_id)->order('id desc')->select();


//		array (
//			'id' => 2,
//			'admin_id' => 1,
//			'admin_username' => 'admin',
//			'admin_realname' => '杨文林',
//			'admin_ip' => '127.0.0.1',
//			'admin_time' => 1525931323,
//			'record' => 'a:43:{s:15:"REDIRECT_STATUS";s:3:"200";s:9:"HTTP_HOST";s:7:"demo.oa";s:15:"HTTP_CONNECTION";s:10:"keep-alive";s:14:"CONTENT_LENGTH";s:2:"31";s:11:"HTTP_ACCEPT";s:46:"application/json, text/javascript, */*; q=0.01";s:11:"HTTP_ORIGIN";s:14:"http://demo.oa";s:21:"HTTP_X_REQUESTED_WITH";s:14:"XMLHttpRequest";s:15:"HTTP_USER_AGENT";s:110:"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36";s:12:"CONTENT_TYPE";s:48:"application/x-www-form-urlencoded; charset=UTF-8";s:12:"HTTP_REFERER";s:37:"http://demo.oa/admin/login/login.html";s:20:"HTTP_ACCEPT_ENCODING";s:13:"gzip, deflate";s:20:"HTTP_ACCEPT_LANGUAGE";s:14:"zh-CN,zh;q=0.9";s:11:"HTTP_COOKIE";s:93:"PHPSESSID=n5ll7ua0s1i3qjsib7fqudnga4; sms_time=1525930063; yf_logged_user=oOW%3DhSGO0RGzAvBEE";s:4:"PATH";s:254:"C:\\WINDOWS\\system32;C:\\WINDOWS;C:\\WINDOWS\\System32\\Wbem;C:\\WINDOWS\\System32\\WindowsPowerShell\\v1.0\\;C:\\Program Files\\Git\\cmd;C:\\composer;C:\\Program Files\\nodejs\\;C:\\AppServ\\php5;C:\\WINDOWS\\system32\\config\\systemprofile\\AppData\\Local\\Microsoft\\WindowsApps";s:10:"SystemRoot";s:10:"C:\\WINDOWS";s:7:"COMSPEC";s:27:"C:\\WINDOWS\\system32\\cmd.exe";s:7:"PATHEXT";s:53:".COM;.EXE;.BAT;.CMD;.VBS;.VBE;.JS;.JSE;.WSF;.WSH;.MSC";s:6:"WINDIR";s:10:"C:\\WINDOWS";s:16:"SERVER_SIGNATURE";s:0:"";s:15:"SERVER_SOFTWARE";s:47:"Apache/2.4.25 (Win32) OpenSSL/1.0.2j PHP/5.6.30";s:11:"SERVER_NAME";s:7:"demo.oa";s:11:"SERVER_ADDR";s:9:"127.0.0.1";s:11:"SERVER_PORT";s:2:"80";s:11:"REMOTE_ADDR";s:9:"127.0.0.1";s:13:"DOCUMENT_ROOT";s:22:"C:/AppServ/www/demo/oa";s:14:"REQUEST_SCHEME";s:4:"http";s:14:"CONTEXT_PREFIX";s:0:"";s:21:"CONTEXT_DOCUMENT_ROOT";s:22:"C:/AppServ/www/demo/oa";s:12:"SERVER_ADMIN";s:16:"275526597@qq.com";s:15:"SCRIPT_FILENAME";s:32:"C:/AppServ/www/demo/oa/index.php";s:11:"REMOTE_PORT";s:5:"63992";s:12:"REDIRECT_URL";s:24:"/admin/sms/runlogin.html";s:17:"GATEWAY_INTERFACE";s:7:"CGI/1.1";s:15:"SERVER_PROTOCOL";s:8:"HTTP/1.1";s:14:"REQUEST_METHOD";s:4:"POST";s:12:"QUERY_STRING";s:0:"";s:11:"REQUEST_URI";s:24:"/admin/sms/runlogin.html";s:11:"SCRIPT_NAME";s:10:"/index.php";s:9:"PATH_INFO";s:24:"/admin/sms/runlogin.html";s:15:"PATH_TRANSLATED";s:61:"redirect:\\index.php\\admin\\sms\\runlogin.html\\sms\\runlogin.html";s:8:"PHP_SELF";s:34:"/index.php/admin/sms/runlogin.html";s:18:"REQUEST_TIME_FLOAT";d:1525931323.108;s:12:"REQUEST_TIME";i:1525931323;}',
//		),

		if($data&&count($data)>=1)
		{
			$str = '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
								<th>登录时间</th>
									<th>用户名</th>
									<th>真实名字</th>
									<th>登录IP</th>
								</tr>
								</thead>
								<tbody>';
			foreach($data as $row)
			{
				$str .= '<tr>
						<td>'.date('Y-m-d H:i:s',$row['admin_time']).'</td>
						<td>'.$row['admin_username'].'</td>
						<td>'.$row['admin_realname'].'</td>
						<td>'.$row['admin_ip'].'</td>
					</tr>';
			}

			$str .= '
								</tbody>
							</table>';
		}
		else
		{
			$str = '
				<table class="table table-striped table-bordered table-hover" id="dynamic-table">
				<tbody>
				<tr>
				<td colspan="4">暂无登录记录</td>
					</tr></tbody>
							</table>';
		}

		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}
}