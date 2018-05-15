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
use think\log;

/**
 * 客户
 *
 * Class Cust
 * @package app\admin\controller
 */
class Cust extends Base
{
	/**
	 * 客户列表
	 */
	public function cust_list()
	{
		$this->get_official();
		$this->_get_select();
		$this->_get_cust_level();

		$cust_jibie = Db::name('cust_jibie')->column('cust_jibie_id,cust_jibie_name');
		$this->assign('cust_jibie',$cust_jibie);

		//针对客户级别的检索
		$map = [];
		$c_jibie=input('c_jibie');
		if($c_jibie)
		{
			$map['cust_jibie'] = [
				'eq',trim($c_jibie)
			];
		}

		$search_name=input('search_name');
		$this->assign('search_name',$search_name);
		$map1 = [];
		$map2 = [];
		if($search_name)
		{
			$map1['cust_contact']= array('like',"%".$search_name."%");


			//针对公司的检索
			$company_id = Db::name('company')->where(['company_name' => ['like','%'.$search_name.'%']])->column('company_id');
			$company_id = implode(',',$company_id);

			$map2['company_id'] = ['in',$company_id];
		}

		/**
		 * 普通组的人
		 */
		if($this->_get_admin_group_id() == 3)
		{
			$cust_belong = Db::name('admin')->where('admin_id',$this->_get_admin_id())->value('admin_realname');
//			$map['cust_auto'] = $this->_get_admin_id();
			$map['cust_belong'] = $cust_belong;
		}

		$cust_list=Db::name('cust')->where($map)->
		where($map1)->whereOr($map2)->                     //加一个检索
		order('id desc')->paginate(10,false,['query'=>get_query()]);
		$page = $cust_list->render();

		//当前所属组：
		$group_id = $this->_get_admin_group_id();
		$admin_id = $this->_get_admin_id();

//		$this->p($cust_list,1);
//		$cust_list_now_month[$key]['official_website'] = $this->company_official_website[$val['company_id']];
		foreach($cust_list as $key => $row)
		{
			if(isset($this->company_official_website[$row['company_id']]))
			{
				$row['official_website'] = $this->company_official_website[$row['company_id']];
			}
			else
			{
				$row['official_website'] = '';
			}
			//系统管理员 和  超级管理员
			if($group_id != 3)
			{
				$row['is_operation'] = true;
			}
			else
			{
				//是否是自己创建的
				if($admin_id == $row['cust_auto'])
				{
					$row['is_operation'] = true;
				}
				else
				{
					$row['is_operation'] = null;
				}
			}
			$cust_list[$key] = $row;
		}
		$this->assign('cust_list',$cust_list);
		$this->assign('page',$page);

		return $this->fetch();
	}


	private function _admin_cust($id)
	{
		$auto = db('cust')->where('id',$id)->value('cust_auto');

		//当前所属组：
		$group_id = $this->_get_admin_group_id();
		$admin_id = $this->_get_admin_id();

		if($auto == $admin_id)
		{
			return true;
		}

		if($group_id != 3)
		{
			return true;
		}

		return false;
	}

	public function cust_open_up()
	{
		$this->get_official();
		$cc = new \echart\Echart();
		$cust_list=Db::name('cust')->order('cust_add_time desc,cust_level asc')->select();

		foreach($cust_list as $key => $rows)
		{
			$rows['month'] = trim(date('Y-m',$rows['cust_add_time']));
			$xAxis[] = trim(date('Y-m',$rows['cust_add_time']));
		}

		if(isset($xAxis))
		{
			$xAxis = array_unique($xAxis);
		}
		//无数据
		else
		{
			return $this->fetch('index/index_2');
		}

		//图二    近四个月数据
		$data_wdj_1 = $this->_get_serises_four();                    //季度回款情况
		$this->assign('pdo_44',$cc->_cust_four_ylb('pdo_44',$data_wdj_1['data_need']));
		$this->assign('data_two_table',$data_wdj_1['data']);


		//图一，全部总览
		$data_one = $this->_get_echart_one($cust_list,$xAxis);
		$this->assign('data_one_table',$data_one['data']);
		$this->assign('pdo_22',$cc->common_style("pdo_22",$data_one['data_need'],"客户开拓总览","（单位：个）"));

		/**
		 * 当月客户开拓详情
		 */
		$now_year = date('Y');
		$now_month = date('m');
		if($now_month == 12)
		{
			$start_time = $now_year.'-'.$now_month;
			$end_time = ($now_year+1).'-1';
		}
		else
		{
			$start_time = $now_year.'-'.$now_month;
			$end_time = ($now_year).'-'.($now_month + 1);
		}
		$start_time = strtotime($start_time);
		$end_time = strtotime($end_time);

		$map_company['is_open'] = ['neq',1];
		$company_info = Db::name('company')->where($map_company)->column('company_id,company_name');
		$company_id_arr = array_keys($company_info);

		$map_c_list['company_id'] = ['in',implode(',',$company_id_arr)];
		$cust_list_now_month = Db::name('cust')->where($map_c_list)->
		where('cust_add_time','between',"$start_time,$end_time")->
		field('id,cust_contact,company_id,cust_level,cust_add_time,cust_belong,cust_mobile,cust_postal_code,cust_auto,cust_update_time')->
		order('cust_update_time desc')->select();

//		$this->p($cust_list_now_month,1);

		$cust_info = Db::name('cust_level')->column('cust_level_id,cust_level_name');
		$waiter_info = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$admin_info = Db::name('admin')->column('admin_id,admin_realname');

		foreach($cust_list_now_month as $key=>$val)
		{
			if(isset($this->company_official_website[$val['company_id']]))
			{
				$cust_list_now_month[$key]['official_website'] = $this->company_official_website[$val['company_id']];
			}
			else
			{
				$cust_list_now_month[$key]['official_website'] = '';
			}
			if(isset($company_info[$cust_list_now_month[$key]['company_id']])&&$company_info_name = $company_info[$cust_list_now_month[$key]['company_id']])
			{
				$cust_list_now_month[$key]['company_id'] = $this->_get_real($company_info[$cust_list_now_month[$key]['company_id']]);
			}

			$cust_list_now_month[$key]['cust_belong1'] = $this->_get_real($waiter_info[$cust_list_now_month[$key]['cust_belong']]);
			$cust_list_now_month[$key]['cust_level'] = $this->_get_real($cust_info[$cust_list_now_month[$key]['cust_level']]);


			$cust_list_now_month[$key]['cust_auto'] = $this->_get_real($admin_info[$cust_list_now_month[$key]['cust_auto']]);

			/**
			 * 如果是整型
			 */
			if((int)trim($cust_list_now_month[$key]['cust_auto']) != 0)
			{
				$cust_list_now_month[$key]['cust_auto'] = Db::name('customer_waiter')->where('customer_waiter_id',$cust_list_now_month[$key]['cust_auto'])->value('customer_waiter_name');
			}
		}

//		$this->p($cust_list_now_month,1);
		$this->assign('cust_list',$cust_list_now_month);
		return $this->fetch();
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

	/**
	 *
	 * 当数据未空或不存在时返回为空
	 *
	 * @param $str
	 * @return mixed
	 */
	private function _get_real($str)
	{
		if(isset($str)&&$str)
		{
			return $str;
		}
		else
		{
			'';
		}
	}


	/**
	 * 客户开拓第一个图
	 */
	private function _get_echart_one($cust_list,$xAxis)
	{
		//		设置颜色
		foreach($xAxis as $color)
		{
			$color_1[$color] = '#FBCE0F';
			$color_2[$color] = '#DA70D6';
			$color_3[$color] = '#6495ED';
			$color_4[$color] = '#FBCE0F';
			$yAxis[] = array('unit' => "个");
		}

		$legend = array('主动开拓','营销客户','合计');
		$aa = $this->_get_serises1($cust_list,$xAxis,$legend);
		return $aa;
	}


	/**
	 * 获得数据 ，每个月的，主动开拓 和 营销客户 的数据
	 *
	 * @param $payment_month_money
	 * @param $xAxis
	 * @param $legend
	 * @return array
	 */
	private function _get_serises1($payment_month_money,$xAxis,$legend)
	{
		$data_1 = array();
		$data_2 = array();


		//初始化数据
		foreach($xAxis as $row)
		{
			$data_1[$row] = 0;
			$data_2[$row] = 0;
			$data_3[$row] = 0;
			$data_4[$row] = 0;
		}

		foreach($payment_month_money as $val)
		{
			switch($val['cust_level'])
			{
				//主动开拓
				case 1:
					foreach($xAxis as $month)
					{
						if(!isset($data_1[$month]))
						{
							$data_1[$month] = 0;
						}
						if(trim(date('Y-m',$val['cust_add_time'])) == trim($month))
						{
							$data_1[$month] += 1;
						}
					}
					break;

				//营销部
				case 2:
					foreach($xAxis as $month)
					{
						if(!isset($data_2[$month]))
						{
							$data_2[$month] = 0;
						}
						if(trim(date('Y-m',$val['cust_add_time'])) == trim($month))
						{
							$data_2[$month] += 1;
						}
					}
					break;
			}
		}

		$data_3 = array();
		foreach($data_1 as $key => $row)
		{
			$data_3[$key] = $row + $data_2[$key];
		}

//		设置颜色
		foreach($xAxis as $color)
		{
			$color_1[$color] = '#FBCE0F';
			$color_2[$color] = '#DA70D6';
			$color_3[$color] = '#6495ED';
			$yAxis[] = array('unit' => "个");
		}

		$series_1 = array(
			'name' => '主动开拓',
			'type' => "bar_top",
			'data' => $this->_1arr_str($data_1),
			'color' => $this->_1arr_str($color_1),
		);

		$series_2 = array(
			'name' => '营销客户',
			'type' => "bar_top",
			'data' => $this->_1arr_str($data_2),
			'color' => $this->_1arr_str($color_2),
		);

		$series_3 = array(
			'name' => '合计',
			'type' => "bar_top",
			'data' => $this->_1arr_str($data_3),
			'color' => $this->_1arr_str($color_3),
		);

		$data_need_1 = array(
			'legend' => $this->_1arr_str($legend),
			'xAxis' => $this->_1arr_str($xAxis),
			'yAxis' => $yAxis,
			'series' => array($series_1,$series_2,$series_3),
		);

//		$this->p($xAxis);
//		$this->p($data_1,1);

		return array('data_need' =>$data_need_1,'data' => array($data_1,$data_2,$data_3));
	}


	/**
	 * 获得集合数据   （近四月）
	 *
	 * @return array
	 */
	private function _get_serises_four()
	{
		//获取本季 时间轴
		$now_year = date('Y');
		$now_month = date('m');
		if(in_array($now_month,array('1','2','3')))
		{
			$start_1 = ($now_year-1).'-'.($now_month + 12 -3);
			$end_1 = $now_year.'-'.($now_month+1);
		}
		elseif($now_month == 12)
		{
			$start_1 = ($now_year).'-'.($now_month - 3);
			$end_1 = ($now_year+1).'-1';
		}
		else
		{
			$start_1 = ($now_year).'-'.($now_month - 3);
			$end_1 = $now_year.'-'.($now_month+1);
		}
		$start = strtotime($start_1);
		$end = strtotime($end_1) - 3600*24;


		$payment_month_money = Db::name('cust')->
		where('cust_add_time','between',"$start,$end")->
		field('cust_add_time,cust_level')->order('cust_add_time')->select();

		foreach($payment_month_money as $key => $rows)
		{
			$rows['month'] = trim(date('Y-m',$rows['cust_add_time']));
			$xAxis[] = trim(date('Y-m',$rows['cust_add_time']));
			$payment_month_money[$key] = $rows;
		}

		//获得x轴
		if(isset($xAxis))
		{
			$xAxis = array_unique($xAxis);
		}


		$data_1 = array();
		$data_2 = array();
		foreach($payment_month_money as $val)
		{
			foreach($xAxis as $month)
			{
				//主动开拓
				if(!isset($data_1[$month]))
				{
					$data_1[$month] = 0;
				}

				//营销客户
				if(!isset($data_2[$month]))
				{
					$data_2[$month] = 0;
				}

				if(trim($val['month']) == trim($month))
				{
					if($val['cust_level'] == 1)
					{
						$data_1[$month] += 1;
					}
					else
					{
						$data_2[$month] += 1;
					}

				}
			}
		}

		foreach($data_2 as $key => $row)
		{
			$data_4[$key] = round($data_2[$key] + $data_1[$key]);
		}

		$name = ['主动开拓','营销客户','总计'];
		$data = [
			implode(',',$data_1),
			implode(',',$data_2),
			implode(',',$data_4),
		];
		$xAxis = "'".implode("','",$xAxis)."'";

		$text = '近四月客户开拓详情('.$start_1.'-'.$end_1.')';
		$data_need = array(
			'text' => $text,
			'xAxis' => $xAxis,
			'data' => $data,
			'name' => $name,
		);

		return array('data_need' =>$data_need,'data' => array($data_1,$data_2,$data_4));
	}


	/**
	 * 获得下拉信息
	 */
	private function _get_cust_open_up()
	{
		$map['is_open'] = ['neq',1];

		$cust_level_info = db('cust_level')->column('cust_level_id,cust_level_name');
		$company_info = db('company')->where($map)->column('company_id,company_name');
		$customer_waiter_info = db('customer_waiter')->column('customer_waiter_id,customer_waiter_name');

		$this->assign('cust_level_info',$cust_level_info);
		$this->assign('company_info',$company_info);
		$this->assign('customer_waiter_info',$customer_waiter_info);


		$cust_jibie = Db::name('cust_jibie')->column('cust_jibie_id,cust_jibie_name');
		$this->assign('cust_jibie',$cust_jibie);


		//行业类别
		$industry_category = Db::name('industry_category')->field('industry_category_id,industry_category_name')->select();
		$this->assign('industry_category',$industry_category);

		foreach($industry_category as $industry_category_unit)
		{
			$industry_category_need[$industry_category_unit['industry_category_id']] = $industry_category_unit['industry_category_name'];
		}
		$this->assign('industry_category_need',$industry_category_need);


		$company_info_str = db('company')->where($map)->column('company_id,company_name');
		$this->assign('company_info_str',implode(',',$company_info_str));
	}


	/**
	 * 获取添加跨度
	 *
	 * @param $time
	 * @return string
	 */
	private function _get_time_span($time)
	{
		$three_day = 3*24*3600;
		$week_day = 7*24*3600;
		$month_day = 30*24*3600;
		$three_month_day = 3*30*24*3600;

		$now_time = time();
		$param = $now_time - $time;

		if(($param > $three_month_day))
		{
			return '三个月以前';
		}
		elseif(($param > $month_day)&&($param < $three_month_day))
		{
			return '近三月内';
		}
		elseif(($param > $week_day)&&($param < $month_day))
		{
			return '近一月内';
		}
		elseif(($param > $three_day)&&($param < $week_day))
		{
			return '近一周内';
		}
		else
		{
			return '近三天内';
		}
	}

	/**
	 * 管理员开启/禁止
	 */
	public function cust_state()
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
			$this->error('客户不存在',url('admin/Comapany/comapany_list'));
		}
		$status=Db::name('cust')->where('cust_id',$id)->value('is_open');//判断当前状态情况
		if($status==1)
		{
			$statedata = array('is_open'=>0);
			Db::name('cust')->where('cust_id',$id)->setField($statedata);
			$this->success(0);
		}else{
			$statedata = array('is_open'=>1);
			Db::name('cust')->where('cust_id',$id)->setField($statedata);
			$this->success(1);
		}
	}


	/**
	 * 获取行业类别相关知识    以及公司相关信息
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

		$map['is_open'] = ['neq',1];
		$company_info = Db::name('company')->where($map)->field('company_id,company_name')->select();
		$this->assign('company_info',$company_info);

		$customer_waiter_info = db('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$this->assign('customer_waiter_info',$customer_waiter_info);
	}

	/**
	 * 获得客户级别
	 */
	private function _get_cust_level()
	{
		$cust_level_info = db('cust_level')->column('cust_level_id,cust_level_name,cust_level_explain');
		$this->assign('cust_level_info',$cust_level_info);
	}

	/**
	 * 客户级别修改
	 */
	public function cust_level_info()
	{
		$cust_level_info=input('cust_level_info');           //客户级别id号
		$n_id=input('n_id');          //项目id

		if(!$this->_admin_cust($n_id))
		{
			$this->error('您没权限修改');
		}
		$data = db('cust')->find($n_id);

		if($data)
		{
			$rst=db('cust')->where('id',$n_id)->update(['cust_level'=>$cust_level_info]);
			if($rst!==false)
			{
				$this->success('客户类型更新成功');
			}
			else
			{
				$this->error('客户类型更新失败');
			}
		}
		else
		{
			$this->error('客户不存在');
		}
	}

	/**
	 * 客户添加
	 */
	public function cust_add()
	{
		$this->_get_cust_open_up();
		return $this->fetch();
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
	 * 客户添加操作
	 */
	public function cust_runadd()
	{
		$data=input('post.');
//		log::error($data);exit;


		//如果是奇数，则公司已存在
		if($data['is_company']%2)
		{
			unset($data['is_company']);
			unset($data['company_name']);
			unset($data['industry_category']);
			unset($data['official_website']);
		}
		//如果是偶数，则添加了公司
		else
		{

			//执行添加公司
			$company['company_name'] = $data['company_name'];
			$company['industry_category'] = $data['industry_category'];
			$company['official_website'] = $data['official_website'];
			$company['company_create_time'] = time();

			//是否需要审核，若不需审核
			if($this->_is_company_need_audit())
			{
				$company['is_open'] = 0;
			}


			if(!$company['company_name'])
			{
				$this->error('请输入公司名',url('cust/cust_add'));
			}

			if(!$company['industry_category'])
			{
				$this->error('请输入对应行业',url('cust/cust_add'));
			}

			$company_id = Db::name('company')->insertGetId($company);

			if($company_id)
			{
				$data['company_id'] = $data['company_name'];
				unset($data['is_company']);
				unset($data['company_name']);
				unset($data['industry_category']);
				unset($data['official_website']);
			}
			else
			{
				$this->error('客户添加失败',url('cust/cust_list'));
			}

		}

//		log::error($data);


		//获得  公司名   服务项目
		$company_id = $this->_get_company_id_by_company_name($data['company_id']);
		if(!isset($company_id)||!$company_id){ $this->error('您输入的该公司不存在,请先添加该公司',url('admin/company/company_add'));}

		$data['company_id'] = $company_id;

		$data['cust_add_time'] = $data['cust_update_time'] = time();
		$data['cust_auto'] = $this->_get_admin_id();          //添加者

		Db::name('cust')->insert($data);

		$cust_id = db('cust')->getLastInsID();
		if($cust_id)
		{
			$this->success('客户添加成功',url('cust/cust_list'));
		}
		else
		{
			$this->error('客户添加失败',url('cust/cust_list'));
		}
	}

	/**
	 * 客户修改
	 */
	public function cust_edit()
	{
		$cust_list=Db::name('cust')->find(input('id'));

		$this->_get_cust_open_up();
		$this->assign('cust_list',$cust_list);
		return $this->fetch();
	}

	/**
	 * 客户修改操作
	 */
	public function cust_runedit()
	{
		$data=input('post.');
		$rst = db('cust')->update($data);
		if($rst!==false)
		{
			$this->success('客户修改成功',url('admin/cust/cust_list'));

		}else{
			$this->error('客户修改失败',url('admin/cust/cust_list'));
		}
	}
	/**
	 * 客户删除
	 */
	public function cust_del()
	{
		$cust_id=input('id');
		if (empty($cust_id))
		{
			$this->error('客户ID不存在',url('cust/cust_list'));
		}

		//对应客户ID
		$rst=Db::name('cust')->where('id',$cust_id)->delete();
		if($rst!==false)
		{
			$this->success('客户删除成功',url('cust/cust_list'));
		}else{
			$this->error('客户删除失败',url('cust/cust_list'));
		}
	}

	/**
	 * 客户所属公司修改
	 */
	public function cust_company_from()
	{
		$cust_company_from=input('cust_company_from');           //客户所属公司id号
		$n_id=input('n_id');          //客户id

		if(!$this->_admin_cust($n_id))
		{
			$this->error('您没权限修改');
		}

		$data = db('cust')->find($n_id);
		if($data)
		{
			$rst=db('cust')->where('id',$n_id)->update(['company_id'=>$cust_company_from]);
			if($rst!==false)
			{
				$this->success('客户所属公司更新成功');
			}
			else
			{
				$this->error('客户所属公司更新失败');
			}
		}
		else
		{
			$this->error('客户不存在');
		}
	}


	/**
	 * 行业类别修改
	 */
	public function industry_category_doing3()
	{
		$industry_category_doing3=input('industry_category_doing3');           //行业类别id号
		$n_id=input('n_id');          //项目id

		$data = db('cust')->find($n_id);

		if($data['is_open'] == 1)
		{
			if($data)
			{
				$rst=db('cust')->where('cust_id',$n_id)->update(['industry_category'=>$industry_category_doing3]);
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
			$this->error('该客户信息已审核通过，请调整客户审核状态后再修改');
		}

	}


	/*
	 * 客户信息
	 */
	public function profile()
	{
//		$this->test_output();
		$cust=array();
		if(session('cust_auth.aid'))
		{
			$cust=Db::name('cust')->alias("a")->join(config('database.prefix').'auth_group_access b','a.cust_id =b.uid')
				->join(config('database.prefix').'auth_group c','b.group_id = c.id')
				->where(array('a.cust_id'=>session('cust_auth.aid')))->find();

//			$news_count=Db::name('News')->where(array('news_auto'=>session('cust_auth.member_id')))->count();
//			$cust['news_count']=$news_count;
		}

//		$this->p($cust,1);
		$this->assign('cust', $cust);
		return $this->fetch();
	}
	/*
	 * 客户头像
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
		$cust=Db::name('cust')->where(array('cust_id'=>session('cust_auth.aid')))->find();
		$cust['cust_avatar']=$imgurl;
		$rst=Db::name('cust')->where(array('cust_id'=>session('cust_auth.aid')))->update($cust);
		if($rst!==false){
			session('cust_avatar',$imgurl);
			$this->success ('头像更新成功',url('cust/profile'));
		}else{
			$this->error ('头像更新失败',url('cust/profile'));
		}
	}


	/*
    *  回款详情
    * shulan
    */
	public function cust_back_payment_detail()
	{
		$cust_id = input('cust_id');
		$customer_id_arr = db('customer')->where(array('cust_id' => $cust_id))->column('n_id,customer_title');

		if(!$customer_id_arr)
		{
			$this->error ('该客户暂无回款清单');
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
				if($row['actual_amount_of_money'])
				{
					$actual_amount_of_money = '<td style="color: red;">已收</td>';
				}
				else
				{
					$actual_amount_of_money = '<td>未收</td>';
				}

				$str[$key] .= '<tr>
						<td>'.$customer_info_arr[$key][$row['customer_id']].'</td>
						<td>第'.$row['number_of_periods'].'期</td>
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
	public function cust_detail()
	{
		$cust_id = input('cust_id');
		$customer_id_arr = db('cust')->find($cust_id);


		$industry = Db::name('industry_category')->column('industry_category_id,industry_category_name');

		$str = '<div class="col-xs-12 col-sm-9" style="margin: 0 auto;">
						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户编号</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_number'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户名</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_contact'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户别名</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_alias'].'</span>
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
								<div class="profile-info-name">客户地址</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_address'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户法人</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_legal_person'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户电话</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_phone'].'</span>
									</div>
							</div>
						</div>

						<div class="profile-user-info profile-user-info-striped">
							<div class="profile-info-row">
								<div class="profile-info-name">客户邮箱</div>
								<div class="profile-info-value">
									<span class="editable" id="username">'.$customer_id_arr['cust_mail'].'</span>
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
	 */
	private function _get_customer()
	{
		$customer = Db::name('customer')->column('n_id,customer_title');
		$this->assign('customer_info',$customer);
	}


	/**
	 * 数组格式变成'a','b','c'样式(一维数组变字符串)
	 *
	 * @param $arr
	 * @return string
	 */
	private function _1arr_str($arr)
	{
		if($arr == array())
		{
			return '';
		}
		$str = "'";
		foreach($arr as $rows)
		{
			$str.= $rows."','";
		}
		//return '"'.substr($str,0,-2).'"';
		return substr($str,0,-2);
	}


	/**
	 * 修改  沟通详情表
	 *
	 * @return \think\response\Json
	 */
	public function cust_communication_details()
	{
		$cust_follow_id=input('back_payment_id');
		$data = Db::name('cust_follow')->where('cust_id',$cust_follow_id)->order('communicate_time desc')->select();

		if($data&&count($data)>=1)
		{
			$str = '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
									<th>沟通时间</th>
									<th>沟通方式</th>
									<th>沟通结果</th>
								</tr>
								</thead>
								<tbody>';
			foreach($data as $row)
			{
				$str .= '<tr>
						<td>'.date('Y-m-d',$row['communicate_time']).'</td>
						<td>'.$row['communicate_type'].'</td>
						<td>'.$row['communicate_res'].'</td>
					</tr>';
			}

			$str .= '
								</tbody>
							</table>';
		}
		else
		{
			$str = '';
		}

		$sl_data['cust_follow_id'] = $cust_follow_id;
		$sl_data['code']=1;
		$sl_data['content'] = $str;

//		log::error($sl_data);
		return json($sl_data);
	}

	/**
	 * 修改  沟通详情表
	 *
	 * @return \think\response\Json
	 */
	public function cust_communication_details_2()
	{
		$cust_follow_id=input('back_payment_id');
		$month = $cust_follow_id;

		$arr = explode('-',$month);
		$month = $arr[1];
		$year = $arr[0];
		if($month == 12)
		{
			$start_time = $year.'-'.$month;
			$end_time = ($year+1).'-1';
		}
		else
		{
			$start_time = $year.'-'.$month;
			$end_time = ($year).'-'.($month + 1);
		}

		$start_time = strtotime($start_time);
		$end_time = strtotime($end_time);


		$data = Db::name('cust')->
		where('cust_add_time','between',"$start_time,$end_time")->
		field('cust_contact,company_id,cust_level,cust_add_time,cust_belong,cust_mobile,cust_postal_code,cust_auto,cust_update_time')->
		order('cust_update_time desc')->paginate(2000,false,['query'=>get_query()]);
		$page = $data->render();

		$company_info = Db::name('company')->column('company_id,company_name');
		$cust_info = Db::name('cust_level')->column('cust_level_id,cust_level_name');
		$waiter_info = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$admin_info = Db::name('admin')->column('admin_id,admin_realname');

		if($data&&count($data)>=1)
		{
			$str = '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
									<th>客户</th>
									<th>所在公司</th>
									<th>客户类型</th>
									<th>跟进人</th>
									<th>手机号</th>
									<th>邮箱</th>
									<th>创建者</th>
									<th>创建时间</th>
									<th>最近一次跟进时间</th>
								</tr>
								</thead>
								<tbody>';
			foreach($data as $row)
			{
				if(isset($company_info[$row['company_id']]))
				{
					$company_info_1 = $company_info[$row['company_id']];
				}
				else
				{
					$company_info_1 = '<span style="color: red">该公司已不存在</span>';
				}

				$str .= '<tr>
<td>'.$row['cust_contact'].'</td>
<td>'.$company_info_1.'</td>
<td>'.$cust_info[$row['cust_level']].'</td>
<td>'.$waiter_info[$row['cust_belong']].'</td>
<td>'.$row['cust_mobile'].'</td>
<td>'.$row['cust_postal_code'].'</td>
<td>'.$waiter_info[$admin_info[$row['cust_auto']]].'</td>
<td>'.date('Y-m-d',$row['cust_add_time']).'</td>
<td>'.date('Y-m-d',$row['cust_update_time']).'</td>
					</tr>';
			}

			$str .='<tr>
							<td height="50" colspan="14" style="padding-right: 60%;">'.$page.'</td>
						</tr>';
			$str .= '
								</tbody>
							</table>';
		}
		else
		{
			$str = '';
		}

		$sl_data['cust_follow_id'] = $cust_follow_id;
		$sl_data['code']=1;
		$sl_data['content'] = $str;

		return json($sl_data);
	}

	private function _get_month_cust($data)
	{
		$arr = explode('-',$data);
		$month = $arr[1];
		$year = $arr[0];
		if($month == 12)
		{
			$start_time = $month.'-'.$month;
			$end_time = ($year+1).'-1';
		}
		else
		{
			$start_time = $month.'-'.$month;
			$end_time = ($year).'-'.($month + 1);
		}
		$start_time = strtotime($start_time);
		$end_time = strtotime($end_time);

		$cust_list_now_month = Db::name('cust')->
		where('cust_add_time','between',"$start_time,$end_time")->
		field('cust_contact,company_id,cust_level,cust_add_time,cust_belong,cust_mobile,cust_postal_code,cust_auto,cust_update_time')->
		order('cust_update_time desc')->select();
		return $cust_list_now_month;
	}

	/*
    * 修改  沟通详情表
    * shulan
    */
	public function cust_communication_rundetails()
	{

		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/cust/cust_list'));
		}
		else
		{
			$data=input('post.');
			$data['communicate_time'] = strtotime($data['communicate_time']);
			Db::name('cust_follow')->insert($data);

//			修改客户修改时间
			$cust['cust_update_time'] = $data['communicate_time'];
			Db::name('cust')->where('id',$data['cust_id'])->update($cust);

			$this->success('服务项目添加成功',url('admin/cust/cust_list'));
		}
	}


	/**
	 * 修改  沟通详情表
	 *
	 * @return \think\response\Json
	 */
	public function cust_communication_details_21()
	{
		$cust_follow_id=input('back_payment_id');
		$data = Db::name('cust_follow')->where('cust_id',$cust_follow_id)->order('communicate_time desc')->select();
		$data_1 = Db::name('cust')->where('id',$cust_follow_id)->value('cust_belong');
		$name = Db::name('customer_waiter')->where('customer_waiter_id',$data_1)->value('customer_waiter_name');

		if($data&&count($data)>=1)
		{
			$str = '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
									<th>沟通时间</th>
									<th>沟通方式</th>
									<th>沟通结果</th>
									<th>沟通人</th>
								</tr>
								</thead>
								<tbody>';
			foreach($data as $row)
			{
				$str .= '<tr>
						<td>'.date('Y-m-d',$row['communicate_time']).'</td>
						<td>'.$row['communicate_type'].'</td>
						<td>'.$row['communicate_res'].'</td>
						<td>'.$name.'</td>
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
						<td colspan="4">暂无沟通详情</td>
					</tr></tbody>
							</table>';
		}

		$sl_data['cust_follow_id'] = $cust_follow_id;
		$sl_data['code']=1;
		$sl_data['content'] = $str;

		return json($sl_data);
	}




	/////////////////////////////////////////////////////////////////////////////////////////////跟进人  所跟进的项目列表展示
	/**
	 * 所跟进的 客户 列表展示
	 *
	 * @return \think\response\Json
	 */
	public function cust_cust_belong()
	{
		$cust_belong = input('back_payment_id');

		//公司基础字段
		$company_info = Db::name('company')->column('company_id,company_name');
		$waiter = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');

		$cust_info = Db::name('cust')->where('cust_belong',$cust_belong)->field('cust_contact,company_id,cust_mobile,cust_add_time,cust_update_time,cust_belong')->select();
		foreach($cust_info as &$row)
		{
			$row['company_name'] = $company_info[$row['company_id']];
			$row['waiter_name'] = $waiter[$row['cust_belong']];
		}
		unset($row);


		if($cust_info&&count($cust_info)>=1)
		{
			$str = '
		<table class="table table-striped table-bordered table-hover" id="dynamic-table">
								<thead>
								<tr>
								<th colspan="6">当前跟进的客户总数为：'.count($cust_info).'个</th>
								</tr>
								<tr>
									<th>跟进人</th>
									<th>公司名</th>
									<th>客户联系人</th>
									<th>客户手机号</th>
									<th>创建时间</th>
									<th>最近一次跟进时间</th>
								</tr>
								</thead>
								<tbody>';
			foreach($cust_info as $row)
			{
				$str .= '<tr>
						<td>'.$row['waiter_name'].'</td>
						<td>'.$row['company_name'].'</td>
						<td>'.$row['cust_contact'].'</td>
						<td>'.$row['cust_mobile'].'</td>
						<td>'.date('Y-m-d',$row['cust_add_time']).'</td>
						<td>'.date('Y-m-d',$row['cust_update_time']).'</td>
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
						<td colspan="4">暂无跟进客户</td>
					</tr></tbody>
							</table>';
		}

		$sl_data['cust_follow_id'] = $cust_belong;
		$sl_data['code']=1;
		$sl_data['content'] = $str;

		return json($sl_data);
	}

}