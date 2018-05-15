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
use think\Cache;

class WebLog extends Base
{

	//获得操作对应数据
	private function _get_operate_explain()
	{
		$need_data = cache('web_nav');
		$need_data= false;
		if(empty($need_data))
		{
			$map_two = [
				'status' => ['eq',1],
				'level' => ['eq',2],
			];

			$data_two = Db::name('auth_rule')->where($map_two)->column('id,name,title,pid');

			$data_two['373']['title'] = '项目管理——添加显示';
			$need_data = [
				['admin/sys/export','数据库备份 -- 执行备份'],
				['admin/customer/customer_acount1_doing1','账户修改'],
				['admin/customer/cust_communication_details_21','修改——沟通详情'],
				['admin/customer/customer_add','项目管理——添加显示'],
				['admin/sms/runlogin','后台登录操作'],
				['admin/index/index','后台首页展示'],
				['admin/sms/logout','后台登出'],
				['admin/login/login','后台登录页面'],
				['admin/sys/excel_import','导入'],
				['admin/sys/optimize','数据表优化'],
				['admin/login/runlogin','登录动作'],
				['admin/cust/cust_communication_details','弹框——沟通详情'],
				['admin/cust/cust_runadd','客户——添加显示'],
				['admin/cust/cust_communication_rundetails','弹框——修改沟通详情操作'],
				['admin/cust/cust_cust_belong','所跟进的客户 列表展示'],
				['admin/cust/cust_detail','回款详情'],
				['/admin/','后台首页'],
			];


			foreach($data_two as $row_two)
			{
				$row_two['name'] = strtolower($row_two['name']);

				$two_title = $row_two['title'];
				if(strpos($row_two['name'],'_list'))
				{
					$two_title = $two_title.'——'.'列表显示';
				}

				$need_data[] = [
					trim($row_two['name']),$two_title
				];

				$map_three = [
					'pid' => ['eq',$row_two['id']],
				];
				$data_three = Db::name('auth_rule')->where($map_three)->column('id,name,title,pid');
				if($data_three)
				{
					foreach($data_three as $row_three)
					{
						$row_three['name'] = strtolower($row_three['name']);


						$need_data[] = [
							trim($row_three['name']),$row_two['title'].'——'.$row_three['title'],
						];
					}
				}
				else
				{
					if(strpos($row_two['name'],'_list'))
					{
						$unit_name = str_replace('_list','',$row_two['name']);
						$need_data[] = [
							$unit_name.'_add',$row_two['title'].'——'.'添加显示'
						];
						$need_data[] = [
							$unit_name.'_edit',$row_two['title'].'——'.'编辑显示'
						];
						$need_data[] = [
							$unit_name.'_runadd',$row_two['title'].'——'.'添加操作'
						];
						$need_data[] = [
							$unit_name.'_runedit',$row_two['title'].'——'.'编辑操作'
						];
						$need_data[] = [
							$unit_name.'_del',$row_two['title'].'——'.'删除操作'
						];
					}
				}
			}
			cache('web_nav',$need_data);
		}
		return $need_data;
	}


	/**
	 * 缓存 会员信息  数据
	 *
	 * @param $one
	 */
	private function cache_admin($one)
	{
		$waiter = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');

		foreach($one as &$row)
		{

			if(isset($waiter[$row]))
			{
				$row = $waiter[$row];
			}
			else
			{
				$row = '未定义';continue;
			}
		}

		cache('waiter_info',$one);
	}

	/*
     * 网站日志列表
     */
	public function weblog_list()
	{
		//会员
		$one = Db::name('admin')->column('admin_id,admin_realname');
		$waiter_info = cache('waiter_info');
		if(empty($waiter_info))
		{
			$this->cache_admin($one);
			$waiter_info = cache('waiter_info');
		}
		else
		{
			if(count($waiter_info) != count($one))
			{
				$this->cache_admin($one);
				$waiter_info = cache('waiter_info');
			}
		}

		$this->assign('waiter_info',$waiter_info);

		$log_node = $this->_get_operate_explain();


//		/admin/cust/cust_list.html
		$methods=['GET','POST','PUT','DELETE','HEAD','PATCH','OPTIONS','Ajax','Pjax'];
		$request_module=input('request_module','');
		$controllers=array();
		$controllers_arr=array();
		if($request_module){
			$controllers_arr=\ReadClass::readDir(APP_PATH . $request_module. DS .'controller');
			$controllers=array_keys($controllers_arr);
		}
		$request_controller=input('request_controller','');
		$actions=array();
		if($request_module && $request_controller){
			$actions=$controllers_arr[$request_controller];
			$actions=array_map('array_shift',$actions['method']);
		}
		$request_action=input('request_action','');
		$request_method=input('request_method','');
		//组成where
//		13520599955
		$where=array();
//		$join = [
//			[config('database.prefix').'member_list b','b.member_list_id=a.uid', 'LEFT']
//		];
//		$join = [
//			[config('database.prefix').'admin b','b.admin_id=a.uid', 'LEFT']
//		];
		if($request_module){
			$where['module']=$request_module;
		}

		if($request_controller){
			$where['controller']=$request_controller;
		}
		if($request_action){
			$where['action']=$request_action;
		}

		if($request_method)
		{
			$where['method']=$request_method;
		}

		$where['uid']=['gt',0];
//		$weblog_list=Db::name('web_log')->alias('a')->join($join)->where($where)
//				->order('otime desc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);


		$weblog_list=Db::name('web_log')->alias('a')->where($where)
			->order('otime desc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);

		$show=$weblog_list->render();
		$show=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$show);


		foreach($weblog_list as $key => $row)
		{
			if(isset($waiter_info[$row['uid']]))
			{
				$row['waiter'] = $waiter_info[$row['uid']];
			}
			else
			{
				$row['waiter'] = "未知操作员";
			}

			$row['operate'] = '未定义操作';
			foreach($log_node as $node)
			{
				if(strpos('aaaa'.strtolower($row['url']),$node[0]))
				{
					$row['operate'] = $node[1];
					continue;
				}
			}

			$weblog_list[$key] = $row;
		}

//$this->p($weblog_list,1);
		$this->assign('weblog_list',$weblog_list);
		$this->assign('page',$show);
		$this->assign('request_module',$request_module);
		$this->assign('request_controller',$request_controller);
		$this->assign('request_action',$request_action);
		$this->assign('request_method',$request_method);
		$this->assign('controllers',$controllers);
		$this->assign('actions',$actions);
		$this->assign('methods',$methods);


		$arr_sign = array(';','}');

		foreach($weblog_list as $key => $val)
		{
			if(!in_array(substr($val['data'],-1),$arr_sign))
			{
				unset($weblog_list[$key]);
			}
		}

		if(request()->isAjax()){
			return $this->fetch('ajax_weblog_list');
		}else{
			return $this->fetch();
		}
	}
	/*
     * 网站日志删除
     */
	public function weblog_del()
	{
		$rst=Db::name('web_log')->delete(input('id'));
		if($rst!==false){
			$this->success('删除成功',url('admin/WebLog/weblog_list'));
		}else{
			$this -> error("删除失败",url('admin/WebLog/weblog_list'));
		}
	}
	/*
     * 网站日志全选删除
     */
	public function weblog_alldel()
	{
		$ids = input('id/a');
		if(empty($ids)){
			$this -> error("请至少选择一行",url('admin/WebLog/weblog_list'));
		}
		if(is_array($ids)){
			$where = 'id in('.implode(',',$ids).')';
		}else{
			$where = 'id='.$ids;
		}
		$rst=Db::name('web_log')->where($where)->delete();
		if($rst!==false){
			$this->success("删除成功",url('admin/WebLog/weblog_list'));
		}else{
			$this -> error("删除失败",url('admin/WebLog/weblog_list'));
		}
	}
	/*
     * 网站日志清空
     */
	public function weblog_drop()
	{
		$rst=Db::name('web_log')->where('id','gt',0)->delete();
		if($rst!==false){
			$this->success('清空成功',url('admin/WebLog/weblog_list'));
		}else{
			$this -> error("清空失败",url('admin/WebLog/weblog_list'));
		}
	}

	/*
     * 网站日志设置显示
     */
    public function weblog_setting()
	{
		$web_log=config('web_log');
		//模块
		$web_log['not_log_module']=(isset($web_log['not_log_module']) && $web_log['not_log_module'])?join(',',$web_log['not_log_module']):'';
		$web_log['not_log_controller']=(isset($web_log['not_log_controller']) && $web_log['not_log_controller'])?join(',',$web_log['not_log_controller']):'';
		$web_log['not_log_action']=(isset($web_log['not_log_action']) && $web_log['not_log_action'])?join(',',$web_log['not_log_action']):'';
		$web_log['not_log_data']=(isset($web_log['not_log_data']) && $web_log['not_log_data'])?join(',',$web_log['not_log_data']):'';
		$web_log['not_log_request_method']=(isset($web_log['not_log_request_method']) && $web_log['not_log_request_method'])?join(',',$web_log['not_log_request_method']):'';
		//控制器 模块
		$controllers=array();
		$actions=array();
		$modules=['home','admin','install'];


		foreach($modules as $module)
		{
			$arr=cache('controllers_'.$module);

			if(empty($arr))
			{
				$arr=\ReadClass::readDir(APP_PATH . $module. DS .'controller');

				cache('controllers'.'_'.$module,$arr);
			}

			if($arr)
			{
				foreach($arr as $key=>$v)
				{
					$controllers[$module][]=$module.'/'.$key;
					$actions[$module.'/'.$key]=array_map('array_shift',$v['method']);
				}
			}
		}

		$methods=['GET','POST','PUT','DELETE','HEAD','PATCH','OPTIONS','Ajax','Pjax'];
		$this->assign('methods',$methods);
		$this->assign('actions',$actions);
		$this->assign('modules',$modules);
		$this->assign('controllers',$controllers);
		$this->assign('web_log',$web_log);
		return $this->fetch();
	}
	/*
     * 网站日志设置保存
     */
	public function weblog_runset()
	{
		$weblog_on=input('weblog_on',0,'intval')?true:false;
		//设置tags
		$configs=include APP_PATH.'tags.php';
		$module_init=$configs['module_init'];
		if($weblog_on){
			if(!in_array('app\\common\\behavior\\WebLog',$module_init)){
				$module_init[]='app\\common\\behavior\\WebLog';
			}
		}else{
			$key = array_search('app\\common\\behavior\\WebLog', $module_init);
			if($key!==false){
				unset($module_init[$key]);
			}
		}
		$configs=array_merge($configs,['module_init'=>$module_init]);
		file_put_contents(APP_PATH.'tags.php', "<?php\treturn " . var_export($configs, true) . ";");
		$web_log['weblog_on']=$weblog_on;
		$web_log['not_log_module']=input('not_log_module/a');
		$web_log['not_log_controller']=input('not_log_controller/a');
		$web_log['not_log_action']=input('not_log_action/a');
		$web_log['not_log_data']=input('not_log_data/a');
		$web_log['not_log_request_method']=input('not_log_request_method/a');
		$rst=sys_config_setbykey('web_log',$web_log);
		if($rst){
			Cache::clear();
			$this->success('设置保存成功',url('admin/WebLog/weblog_setting'));
		}else{
			$this->error('设置保存失败',url('admin/WebLog/weblog_setting'));
		}
	}	
}