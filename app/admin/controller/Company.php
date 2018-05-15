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

class Company extends Base
{
	/**
	 * 公司列表
	 */
	public function company_list()
	{
		$this->_get_select();

		$search_name=input('search_name');
		$this->assign('search_name',$search_name);
		$map=array();
		if($search_name){
			$map['company_name']= array('like',"%".$search_name."%");
		}
		$company_list=Db::name('company')->where($map)->order('is_open desc,company_id desc')->paginate(10,false,['query'=>get_query()]);
		$page = $company_list->render();

		$this->assign('company_list',$company_list);
		$this->assign('page',$page);

		$can_del = 1;
		$group_id = $this->_get_admin_group_id();
		if($group_id == 3)
		{
			$can_del = 0;
		}
		$this->assign('can_del',$can_del);
//		echo $this->_get_admin_group_id();exit;
		return $this->fetch();
	}

	/**
	 * 管理员开启/禁止
	 */
	public function company_state()
	{
		/**
		 * 普通会员无权修改
		 */
		if($this->_get_admin_group_id() == 3)
		{
			$this->error('无权修改',url('admin/Comapany/comapany_list'));
		}

		$id=input('x');
		if (empty($id))
		{
			$this->error('公司不存在',url('admin/Comapany/comapany_list'));
		}
		$status=Db::name('company')->where('company_id',$id)->value('is_open');//判断当前状态情况
		if($status==1)
		{
			//0 是显示
			$statedata = array('is_open'=>0);
			Db::name('company')->where('company_id',$id)->setField($statedata);

//			另外相应的所有项目也审核通过
			$customer_statedata = array('customer_open'=>1);
			Db::name('customer')->where('company_id',$id)->setField($customer_statedata);

			$this->success(0);
		}else{
			$statedata = array('is_open'=>1);
			Db::name('company')->where('company_id',$id)->setField($statedata);

			//			另外相应的所有项目也审核通过
			$customer_statedata = array('customer_open'=>0);
			Db::name('customer')->where('company_id',$id)->setField($customer_statedata);

			$this->success(1);
		}
	}


	/**
	 * 获取行业类别相关知识
	 */
	private function _get_select()
	{
		//行业类别
		$industry_category = Db::name('industry_category')->field('industry_category_id,industry_category_name')->select();
		$this->assign('industry_category',$industry_category);

		foreach($industry_category as $industry_category_unit)
		{
			$industry_category_need[$industry_category_unit['industry_category_id']] = $industry_category_unit['industry_category_name'];
		}
		$this->assign('industry_category_need',$industry_category_need);
	}

	/**
	 * 公司添加
	 */
	public function company_add()
	{
		$this->_get_select();
		return $this->fetch();
	}

	/**
	 * 公司添加操作
	 */
	public function company_runadd()
	{
		$data=input('post.');
		$data['company_create_time'] = time();

		//是否需要审核，若不需审核
		if($this->_is_company_need_audit())
		{
			$data['is_open'] = 0;
		}


		Db::name('company')->insert($data);
		$company_id = db('company')->getLastInsID();
		if($company_id){
			$this->success('公司添加成功',url('company/company_list'));
		}else{
			$this->error('公司添加失败',url('company/company_list'));
		}
	}


	/**
	 * 公司添加时，是否需要审核    false 需要，true 无需
	 *
	 * @return bool
	 */
	private function _is_company_need_audit()
	{
		$info = \app\admin\model\Options::get_options('site_options',$this->lang);
		$info = $info['site_co_name'];

		//无需审核
		if(trim($info) == 2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 公司修改
	 */
	public function company_edit()
	{
		$this->_get_select();
		$company_list=Db::name('company')->find(input('company_id'));


		/**
		 * 普通管理员组，不能修改审核通过的公司
		 */
		if($this->_get_admin_group_id() == 3)
		{
			if($company_list['is_open'] === 0)
			{
				$this->error('公司已通过审核，并显示；不能再修改',url('admin/company/company_list'));
			}
		}

		$this->assign('company_list',$company_list);
		return $this->fetch();
	}

	/**
	 * 公司修改操作
	 */
	public function company_runedit()
	{
		$data=input('post.');
		$rst = db('company')->update($data);
		if($rst!==false)
		{
			$this->success('公司修改成功',url('admin/company/company_list'));

		}else{
			$this->error('公司修改失败',url('admin/company/company_list'));
		}
	}
	/**
	 * 公司删除
	 */
	public function company_del()
	{
		$company_id=input('company_id');
		if (empty($company_id))
		{
			$this->error('公司ID不存在',url('company/company_list'));
		}

		//对应公司ID
		$rst=Db::name('company')->where('company_id',$company_id)->delete();
		if($rst!==false){
			$this->success('公司删除成功',url('company/company_list'));
		}else{
			$this->error('公司删除失败',url('company/company_list'));
		}
	}

	/**
	 * 行业类别修改
	 */
	public function industry_category_doing3()
	{
		$industry_category_doing3=input('industry_category_doing3');           //行业类别id号
		$n_id=input('n_id');          //项目id

		$data = db('company')->find($n_id);

		if($data['is_open'] == 1)
		{
			if($data)
			{
				$rst=db('company')->where('company_id',$n_id)->update(['industry_category'=>$industry_category_doing3]);
				if($rst!==false)
				{
					$this->success('行业类别更新成功');
				}
				else
				{
					$this->error('行业类别更新失败');
				}
			}
			else
			{
				$this->error('项目不存在');
			}
		}
		else
		{
			$this->error('该公司信息已审核通过，请调整公司审核状态后再修改');
		}

	}


	/*
	 * 公司信息
	 */
	public function profile()
	{
//		$this->test_output();
		$company=array();
		if(session('company_auth.aid'))
		{
			$company=Db::name('company')->alias("a")->join(config('database.prefix').'auth_group_access b','a.company_id =b.uid')
				->join(config('database.prefix').'auth_group c','b.group_id = c.id')
				->where(array('a.company_id'=>session('company_auth.aid')))->find();

//			$news_count=Db::name('News')->where(array('news_auto'=>session('company_auth.member_id')))->count();
//			$company['news_count']=$news_count;
		}

//		$this->p($company,1);
		$this->assign('company', $company);
		return $this->fetch();
	}
	/*
	 * 公司头像
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
		$company=Db::name('company')->where(array('company_id'=>session('company_auth.aid')))->find();
		$company['company_avatar']=$imgurl;
		$rst=Db::name('company')->where(array('company_id'=>session('company_auth.aid')))->update($company);
		if($rst!==false){
			session('company_avatar',$imgurl);
			$this->success ('头像更新成功',url('company/profile'));
		}else{
			$this->error ('头像更新失败',url('company/profile'));
		}
	}


	/*
    *  回款详情
    * shulan
    */
	public function company_back_payment_detail()
	{
		$company_id = input('company_id');
		$customer_id_arr = db('customer')->where(array('company_id' => $company_id))->column('n_id,customer_title');

		if(!$customer_id_arr)
		{
			$this->error ('该公司暂无回款清单');
		}

		if(is_array($customer_id_arr)&&$customer_id_arr)
		{
			foreach($customer_id_arr as $customer_id => $customer_title)
			{
				$back_payment_arr[] = Db::name('customer_payment_back')->where(array('customer_id'=>$customer_id))->order('back_payment_id')->select();
				$customer_info_arr[] = Db::name('customer')->column('n_id,customer_title');
				$customer_name[] = $customer_title;
			}
		}

		$per_payment = $this->_get_customer();

		foreach($back_payment_arr as $key => $back_payment)
		{
			if(!$back_payment)
			{
				$str[$key] = '<h4 style="text-align: center;color: red;">对应的项目 : '.$customer_name[$key].'属于未签约项目，没有回款清单</h4>';
				continue;
			}
			$str[$key] = '<h4 style="text-align: center;">项目名 : '.$customer_name[$key].'</h4>';

			$str[$key] .= '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
									<th width="15%">项目名称</th>
									<th>序号</th>
									<th>期数</th>
									<th>签约金额(元)</th>
									<th>回款时间</th>
									<th>回款情况</th>
									<th>实收金额(元)</th>
									<th width="30%">备注</th>
								</tr>
								</thead>

								<tbody>';

			foreach($back_payment as $row)
			{
				if(!$row['actual_amount_of_money'])
				{
					$actual_amount_of_money = '<td style="color: red;">未收</td>';
				}
				else
				{
					$actual_amount_of_money = '<td>已收</td>';
				}

				if($row['is_period'])
				{
					$is_period = '<td>第'.$row['is_period'].'期</td>';
				}
				else
				{
					$is_period = '<td style="color: red;">未分期</td>';
				}

				$str[$key] .= '<tr>
						<td>'.$customer_info_arr[$key][$row['customer_id']].'</td>
						<td>'.$row['number_of_periods'].'</td>
						'.$is_period.'
						<td>'.$row['amount_of_money'].$per_payment[$row['customer_id']].'</td>
						<td>'.date('Y-m-d',$row['back_payment_time']).'</td>
						'.$actual_amount_of_money.'
						<td>'.$row['actual_amount_of_money'].'</td>
						<td>'.$row['remark'].'</td>
					</tr>';
			}

			$str[$key] .= '</tbody>
							</table>';
		}

		$sl_data['content'] = implode('',$str);
		$sl_data['code']=1;
		return json($sl_data);
	}


	/*
    *  回款详情
    * shulan
    */
	public function company_detail()
	{
		$company_id = input('company_id');
		$customer_id_arr = db('company')->find($company_id);


		$industry = Db::name('industry_category')->column('industry_category_id,industry_category_name');

		$str = '<div class="col-xs-12 col-sm-9" style="margin: 0 auto;">
						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司编号</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_number'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司名</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_name'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司别名</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_alias'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">所属行业</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$industry[$customer_id_arr['industry_category']].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司地址</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_address'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司法人</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_legal_person'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司电话</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_phone'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">公司邮箱</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['company_mail'].'</span>
									</div>
							</div>
						</div>


					<hr>';

		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}



	/**
	 * 根据 项目id获得 项目名
	 *
	 * @param $customer_id
	 * @return mixed
	 */
	private function _get_customer()
	{
		$customer = Db::name('customer')->column('n_id,customer_title');
		$this->assign('customer_info',$customer);
//
//		$customer = Db::name('customer')->column('n_id,customer_collection_period');
//		foreach($customer as $key => $val)
//		{
//			switch($val)
//			{
//				case '11':
//					$per_payment[$key] = '/月';
//					break;
//				case '10':
//					$per_payment[$key] = '/二年';
//					break;
//				case '11':
//					$per_payment[$key] = '/月';
//					break;
//				case '8':
//					$per_payment[$key] = '/年';
//					break;
//				case '7':
//					$per_payment[$key] = '/半年';
//					break;
//				case '6':
//					$per_payment[$key] = '/季';
//					break;
//				case '4':
//					$per_payment[$key] = '/一次付清';
//					break;
//			}
//		}
//		$this->assign('per_payment',$per_payment);
//		return $per_payment;
	}


	public function testdb_1()
	{
		if(request()->isPost())
		{
			$company_info=input("post.");
			$company = trim($company_info['company_name']);

			$is_has = Db::name('company')
				->where('company_name','like','%'.$company.'%')
				->whereOr('company_name',$company)
				->find();

			if(($is_has))
			{
				exit("1");
			}
			else
			{
				exit(0);
			}
		}else{
			exit(1);
		}
	}

	/**
	 * 根据公司名 获得公司id
	 *
	 * @param $company_name
	 * @return mixed
	 */
	private function _get_company_id_by_company_name($company_name)
	{
		return db('company')->where('company_name',trim($company_name))->value('company_id');
	}

}