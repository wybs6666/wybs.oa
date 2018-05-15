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
use app\admin\model\Customer as CustomerModel;
use think\log;

class Customer extends Base
{


	/**
	 * 项目列表
	 */
	public function customer_list()
	{
		$cust_jibie = Db::name('customer_status')->column('customer_status_id,customer_status_name');
		$this->assign('cust_jibie',$cust_jibie);

		//针对客户级别的检索
		$map_new = [];
		$c_jibie=input('c_jibie');
		if($c_jibie)
		{
			$map_new['customer_status'] = [
				'eq',trim($c_jibie)
			];
		}

		$this->get_official();
		$admin_id = $this->_get_admin_id();

		$this->_get_customer_select_info(true);

		$this->_get_export_data();

		$company_info = Db::name('company')->column('company_id,company_name');

//		echo $this->_get_admin_id().'<br>';
//		echo $this->_get_admin_group_id();exit;

		$keytype=input('keytype','customer_title');
		$key=input('key');
		$opentype_check=input('opentype_check','');


		//查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
		$sldate=input('reservation','');
		$arr = explode(" - ",$sldate);


//		/db区间查询
		if(count($arr)==2)
		{
			$arrdateone=strtotime($arr[0]);
			$arrdatetwo=strtotime($arr[1].' 23:55:55');
			$map['customer_time'] = array(array('egt',$arrdateone),array('elt',$arrdatetwo),'AND');
		}

//		/db区间查询

		if($this->_get_admin_group_id() == 3)
		{
			$member_id = Db::name('admin')->where('admin_id',$admin_id)->value('admin_realname');
			//商务对接人可以看见自己的回款项目
			if(trim($member_id) !='杨文林')
			{
				$map['customer_waiter_1'] = (int)trim($member_id);
			}

			$map['customer_open']= 1;
			$this->assign('is_export',2);
		}
		else
		{
			$this->assign('is_export',1);
		}

		//map架构查询条件数组
		$map['customer_back']= 0;

		$map = array_merge($map,$map_new);

		/**
		 * 普通组的人
		 */
		if($this->_get_admin_group_id() == 3)
		{
//			$map['customer_auto'] = $this->_get_admin_id();
		}

		if(!empty($key))
		{
			if($keytype=='customer_title')
			{
				$map[$keytype]= array('like',"%".$key."%");
			}
			elseif($keytype=='customer_author')
			{
				$map['member_list_username']= array('like',"%".$key."%");
			}
			else
			{
				$map[$keytype]= $key;
			}
		}

		if ($opentype_check!='')
		{
			$map['customer_open']= array('eq',$opentype_check);
		}

		$customer_model=new customerModel;

		$customer=$customer_model->alias("a")->field('a.*')
			->where($map)->order('customer_status desc,customer_time desc')->paginate(7,false,['query'=>get_query()]);

		$show = $customer->render();
		$show=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$show);


		//		加快捷检索   S
		$show = $customer->render();
		//		加快捷检索   E


		$this->assign('page',$show);

		$this->assign('opentype_check',$opentype_check);
		$this->assign('keytype',$keytype);
		$this->assign('keyy',$key);
		$this->assign('sldate',$sldate);

		//获取时间跨度
		$month_data = $this->get_time_section();
		$need_month_data = $month_data['need_data'];
		unset($need_month_data[0]);
		unset($need_month_data[1]);
		unset($need_month_data[3]);
		unset($need_month_data[4]);

		$this->assign('need_month_data',$need_month_data);

		$nowdate = date('Ym',time());
		foreach($customer as $key=>$val)
		{
			$payment_info = db('customer_payment_back')->where('customer_id',$val['n_id'])->select();
			$amount_of_money_1 = 0;
			$actual_amount_of_money_1 = 0;

			$iii = 1;
			foreach($payment_info as $row_11)
			{
				//当月有还款
				if($nowdate == date('Ym',$row_11['back_payment_time']))
				{
					$amount_of_money_1 += $row_11['amount_of_money'];
					$actual_amount_of_money_1 += $row_11['actual_amount_of_money'];

					$iii++;
				}
			}
			$customer[$key]['amount_of_money'] = $amount_of_money_1;
			$customer[$key]['actual_amount_of_money'] = $actual_amount_of_money_1;

			$customer[$key]['payment_info'] = $payment_info;
			$customer[$key]['company_info'] = $company_info[$customer[$key]['company_id']];

			$customer[$key]['customer_status_name'] = $cust_jibie[$customer[$key]['customer_status']];

			if(isset($this->company_official_website[$customer[$key]['company_id']]))
			{
				$customer[$key]['official_website'] = $this->company_official_website[$customer[$key]['company_id']];
			}
			else
			{
				$customer[$key]['official_website'] = '';
			}

			if($val['customer_status'] != 6)
			{
				$customer[$key]['total_amount'] = 0;
				$customer[$key]['total_number_of_periods'] = 0;
//				$customer[$key]['amount_of_money_five'] = 0;
				continue;
			}

			if($payment_info)
			{
				$customer[$key]['total_amount'] = 0;
				$customer[$key]['total_number_of_periods'] = 0;

				foreach($payment_info as $row)
				{
					if($row['number_of_periods'] == 1)
					{
						$total_amount = $row['total_amount'];        //总金额
						$total_number_of_periods = $row['total_number_of_periods'];    //总期数

						$customer[$key]['total_amount'] = $total_amount;
						$customer[$key]['total_number_of_periods'] = $total_number_of_periods;

						if($row['amount_of_money'])
						{
//							$customer[$key]['amount_of_money'] = $row['amount_of_money'];
//							$customer[$key]['total_number_of_periods'] = $row['total_number_of_periods'];
						}
						else
						{
							$customer[$key]['amount_of_money'] = 0;
							$customer[$key]['total_number_of_periods'] = 0;
						}
					}
				}
			}
			else
			{
				$customer[$key]['total_amount'] = 0;
				$customer[$key]['total_number_of_periods'] = 0;
				$customer[$key]['amount_of_money_five'] = 0;
			}
		}

//		$this->p($customer_status);
//		$this->p($customer,1);
		$this->assign('customer',$customer);

		$this->assign('month_name',array(
			'11' => '月',
			'8' => '年',
			'6' => '季度',
			'7' => '半年',
			'4' => '',
			'12' => '',
			'2' => '未知',
		));

		if(request()->isAjax())
		{
			return $this->fetch('ajax_customer_list');
		}
		else
		{
			return $this->fetch();
		}
	}

	/*
	 * 数据导出功能
	 * shulan
	 */
	public function excel_runexport()
	{
		$data = $this->_get_export_data($_POST);

		//获得        $field
		$field_1 = array_flip($data[0]);
		$get_field = implode(',',$field_1);                                 //需要数据库查询的字段
		$field = explode(',',$get_field);

		//获得        $field_titles
		$field_titles = explode(',',implode(',',$data[0]));

		//获得        $file_name
		$file_name = date('Ymd',time()).'项目一览表';

		//获得 $data_need   （根据$field 取得)
		$data_need = $this->_get_export_data3($this->_get_export_data2(),$field);

		export2excel_new($file_name,$field,$field_titles,$data_need);

		export2excel_new();
	}


	/**
	 * 获得时间间隔
	 *
	 * @return array
	 */
	function get_time_section()
	{
		$arr_month_name = array(
			1 => '一月',
			2 => '二月',
			3 => '三月',
			4 => '四月',
			5 => '五月',
			6 => '六月',
			7 => '七月',
			8 => '八月',
			9 => '九月',
			10 => '十月',
			11 => '十一月',
			12 => '十二月',
		);

		$now = date('Y-m',time());
		$arr = explode('-',$now);
		switch($arr[1])
		{
			case 3:
			case 4:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
				$need_time[] = $arr[0].'-'.($arr[1] - 2);
				$need_time[] = $arr[0].'-'.($arr[1] - 1);
				$need_time[] = $arr[0].'-'.($arr[1]);
				$need_time[] = $arr[0].'-'.($arr[1] + 1);
				$need_time[] = $arr[0].'-'.($arr[1] + 2);
				break;
			case 1:
				$need_time[] = ($arr[0]-1).'-11';
				$need_time[] = ($arr[0]-1).'-12';
				$need_time[] = $arr[0].'-'.($arr[1]);
				$need_time[] = $arr[0].'-'.($arr[1] + 1);
				$need_time[] = $arr[0].'-'.($arr[1] + 2);
				break;
			case 2:
				$need_time[] = ($arr[0]-1).'-12';
				$need_time[] = $arr[0].'-'.($arr[1] - 1);
				$need_time[] = $arr[0].'-'.($arr[1]);
				$need_time[] = $arr[0].'-'.($arr[1] + 1);
				$need_time[] = $arr[0].'-'.($arr[1] + 2);
				break;
			case 11:
				$need_time[] = $arr[0].'-'.($arr[1] - 2);
				$need_time[] = $arr[0].'-'.($arr[1] - 1);
				$need_time[] = $arr[0].'-'.($arr[1]);
				$need_time[] = $arr[0].'-'.($arr[1] + 1);
				$need_time[] = ($arr[0]+1).'-1';
				break;
			case 12:
				$need_time[] = $arr[0].'-'.($arr[1] - 2);
				$need_time[] = $arr[0].'-'.($arr[1] - 1);
				$need_time[] = $arr[0].'-'.($arr[1]);
				$need_time[] = ($arr[0]+1).'-1';
				$need_time[] = ($arr[0]+1).'-2';
				break;
		}

		foreach($need_time as $val)
		{
			$month_1 = explode('-',$val);
			$month_1[1] = intval($month_1[1]);
			$need_data[] = array('month' => $val,'data' => $arr_month_name[$month_1[1]]);
		}
		return array('need_time' => $need_time,'need_data' => $need_data);
	}

	/**
	 *
	 * 根据合同周期id号，取得合同周期的月数
	 *
	 * @param $customer_contract_cycle_id
	 * @return mixed
	 */
	private function get_cycle_month($customer_contract_cycle_id)
	{
		$result = Db::name('customer_contract_cycle')->where(array('customer_contract_cycle_id' => $customer_contract_cycle_id))->find();
		return $result['equivalent_months'];
	}

	/**
	 * 合成到期时间
	 */
	public function cmposite_expiration_date()
	{

//		合同周期
		$customer_contract_cycle_id=input('customer_contract_cycle_id');
		$add_month = $this->get_cycle_month($customer_contract_cycle_id);


//		执行日期
		$customer_execution_time=input('customer_execution_time');
		//得到  年月日：$time_former[0]/$time_former[1]/$time_former[2]
		$time_former = explode('-',$customer_execution_time);

		//相加后的月数
		$now_month = $add_month + $time_former[1];

		//满一年
		if(($now_month >= 13) && ($now_month < 25))
		{
			$year = $time_former[0]+1;
			$month = $now_month - 12;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		elseif($now_month >= 25)
		{
			$year = $time_former[0]+2;
			$month = $now_month - 24;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		else
		{
			$need_data = $time_former[0].'-'.$now_month.'-'.$time_former[2];
		}
		$sl_data['need_data']=$need_data;

		return json($sl_data);
	}

	/**
	 * 添加显示
	 */
	public function customer_add()
	{
		/**
		 * 下拉框集合
		 */
		$this->_get_customer_select_info();

		$customer_service_items = Db::name('service_items')->where('level',1)->select();
		foreach($customer_service_items as $key => $row)
		{
			$row['child'] = Db::name('service_items')->where('pid',$row['service_items_id'])->select();
			$customer_service_items[$key] = $row;
		}
		$this->assign('customer_service_items_two_level',$customer_service_items);

//		$this->p($customer_service_items,1);
		return $this->fetch();
	}

	/**
	 * 添加操作
	 */
	public function customer_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/customer/customer_list'));
		}

		//取得所有数据
		$data=input('post.');

		//获得  公司名   服务项目
		$company_id = $this->_get_company_id_by_company_name($data['company_id']);
		if(!isset($company_id)||!$company_id){ $this->error('您输入的该公司不存在,请添加该公司',url('admin/company/company_add'));}

		//若该公司处于审核中，则该项目也是审核中
		$status=Db::name('company')->where('company_id',$company_id)->value('is_open');//判断当前状态情况
		if($status)
		{
			$data['customer_open'] = 0;   //审核中
		}

		$customer_service_items = $data['select2'];   //服务项目

		$data['customer_auto'] = $this->_get_admin_id();                     //添加者
		$data['customer_time'] = time();

		//签约客户
		if($data['customer_status'] == 6)
		{
			$data['customer_contract_cycle'] = $data['customer_contract_cycle_id'];
			$is_fenqi = $data['customer_collection_period_id_1'];
			unset($data['customer_collection_period_id_1']);
			unset($data['customer_contract_cycle_id']);

			//获得截止日期
			$data['customer_expiration_date'] = $this->_get_expiration_date($data['customer_contract_cycle'],$data['customer_execution_time']);


			//分期项目
			if($is_fenqi == 1)
			{

				$money = $data['customer_payment_info_money'];
				unset($data['customer_payment_info_money']);
				unset($data['select1']);
				unset($data['select2']);
				$date = input('customer_payment_info_date');
				unset($data['customer_payment_info_date']);

				$data['company_id'] = $company_id;
				$data['customer_service_items'] = $customer_service_items;

				$money_arr = explode('|',$money);
				$money_arr = array_sum($money_arr);
				$data['customer_cpprice'] = $money_arr;                                                //签约总金额

				$data['customer_contract_time'] = strtotime($data['customer_contract_time']);
				$data['customer_execution_time'] = strtotime($data['customer_execution_time']);

				//存入客户表
				Db::name('customer')->insert($data);


				//上一次插入数据
				$last_id = Db::name('customer')->getLastInsID();

				$this->_insert_part_data($money,$date,$customer_service_items,$last_id,$data['customer_collection_period'],$data['customer_contract_cycle'],$data['customer_execution_time']);                   //存入回款列表
			}
			//不分期
			else
			{
				unset($data['customer_payment_info_money']);
				unset($data['select1']);
				unset($data['select2']);
				unset($data['customer_payment_info_date']);

				$data['company_id'] = $company_id;
				$data['customer_service_items'] = $customer_service_items;

				$data['customer_contract_time'] = strtotime($data['customer_contract_time']);
				$data['customer_execution_time'] = strtotime($data['customer_execution_time']);

				//存入客户表
				Db::name('customer')->insert($data);

				//上一次插入数据
				$last_id = Db::name('customer')->getLastInsID();

				//存入交接人详情表        waiter_id    add_time
//				Db::name('customer_waiter_re')->insert(array('customer_id' => $last_id,'waiter_id' => input('customer_waiter_1'),'add_time' => time()));
//				Db::name('customer_waiter_re')->insert(array('customer_id' => $last_id,'waiter_id' => input('customer_waiter_2'),'add_time' => time()));

				$this->do_payment_back_1($last_id,input('customer_cpprice'),$data['customer_collection_period'],$data['customer_contract_cycle'],$data['customer_execution_time'],$data['customer_service_items']);
			}
		}
		//未签约客户
		else
		{
			$this->error('未签约客户暂不支持录入',url('admin/customer/customer_list'));
		}
		$this->success('项目添加成功,返回列表页',url('admin/customer/customer_list'));

	}


	/**
	 * 更新回款详情表（第二次处理）
	 *
	 * @param $money_id_arr
	 * @param $customer_id
	 * @param $customer_title
	 */
	private function _insert_money_date2($money_id_arr,$customer_id,$customer_title)
	{
		$data['customer_id'] = $customer_id;
		$data['customer_title'] = $customer_title;


		foreach($money_id_arr as $row)
		{

			$data['back_payment_id'] = $row;
			$aa = Db::name('customer_payment_back')->update($data);

		}
	}

	/**
	 *  自定义 录入 项目回款表
	 *
	 * @param $money
	 * @param $date
	 * @param $service_items
	 * @return array
	 */
	private function _insert_money_date($money,$date,$service_items)
	{
		$money = explode('|',$money);
		$date = explode('|',$date);
		$money_num = count($money);
		if( $money_num!= count($date))
		{
			//如果不是一一对应
			$this->error('自定义类型下的回款和日期不是一一对应,请重新录入',url('admin/customer/customer_add'));
		}
		else
		{
			$data['total_number_of_periods'] = $money_num;            //总期数
			$data['actual_amount_of_money'] = 0;                     //实际金额
			$data['actual_total_amount'] = 0;                     //实际总金额
			$data['service_items'] = $service_items;                     //项目类型
			$data['total_amount'] = array_sum($money);                  //理论总金额

			for($i = 0; $i < $money_num;$i++)
			{
				$data['number_of_periods'] = $i+1;
				$data['amount_of_money'] = trim($money[$i]);
				$data['back_payment_time'] = strtotime(trim($date[$i]));

				Db::name('customer_payment_back')->insert($data);
				//上一次插入数据
				$last_id[] = Db::name('customer_payment_back')->getLastInsID();

				unset($data['number_of_periods']);
				unset($data['amount_of_money']);
				unset($data['back_payment_time']);
			}

			return $last_id;
		}
	}


	/**
	 * 分期数据录入
	 *
	 * @param $money
	 * @param $date
	 * @param $service_items
	 * @param $last_id
	 * @param $customer_collection_period
	 * @param $customer_contract_cycle_id
	 * @param $customer_execution_time
	 * @return array
	 */
	private function _insert_part_data($money,$date,$service_items,$last_id,$customer_collection_period,$customer_contract_cycle_id,$customer_execution_time)
	{
		$customer_execution_time = date('Y-m-d',$customer_execution_time);

		$come_month_num = $this->_get_come_month_num($customer_collection_period);               //回款周期  每期月数
		$cycle_month_num = $this->_get_cycle_month_num($customer_contract_cycle_id);              //合同周期  月数
		$data['customer_id'] = $last_id;


		//一次付的
		if($come_month_num == 99)
		{
			$money = explode('|',$money);
			$date = explode('|',$date);
			$money_num = count($money);
			if( $money_num != count($date))
			{
				//如果不是一一对应
				$this->error('自定义类型下的回款和日期不是一一对应,请重新录入',url('admin/customer/customer_add'));
			}
			else
			{
				$data['total_number_of_periods'] = 1;            //总期数
				$data['actual_amount_of_money'] = 0;                     //实际金额
				$data['actual_total_amount'] = 0;                     //实际总金额
				$data['service_items'] = $service_items;                     //项目类型
				$data['total_amount'] = array_sum($money);                  //理论总金额
				$data['number_of_periods'] = 1;

				for($i = 0; $i < $money_num;$i++)
				{
					$data['amount_of_money'] = trim($money[$i]);
					$data['back_payment_time'] = strtotime(trim($date[$i]));

					$data['is_period'] = $i+1;      //分期后的期数

					Db::name('customer_payment_back')->insert($data);

					unset($data['amount_of_money']);
					unset($data['back_payment_time']);
				}
			}
		}
		//非一次付的
		else
		{
			//不能整除
			if($cycle_month_num % $come_month_num)
			{

			}
			else
			{
				//整除，出多少个月份： 出多少期
				$num = $cycle_month_num/$come_month_num;          //出多少期

				$money = explode('|',$money);
				$date = explode('|',$date);
				$money_num = count($money);

				for($i = 1; $i <= $num;$i++)
				{
					if( $money_num != count($date))
					{
						//如果不是一一对应
						$this->error('自定义类型下的回款和日期不是一一对应,请重新录入',url('admin/customer/customer_add'));
					}
					else
					{
						$data['total_number_of_periods'] = $num;            //总期数
						$data['actual_amount_of_money'] = 0;                     //实际金额
						$data['actual_total_amount'] = 0;                     //实际总金额
						$data['service_items'] = $service_items;                     //项目类型
						$data['total_amount'] = array_sum($money)*$num;                  //理论总金额
						$data['number_of_periods'] = $i;                             // 以前的期数

						for($ii = 0; $ii < $money_num;$ii++)
						{
							$data['amount_of_money'] = trim($money[$ii]);

							//第一个月的 时间
							if($i == 1)
							{
								$data['back_payment_time'] = strtotime(trim($date[$ii]));
							}
							else
							{
								$data['back_payment_time'] = $this->_get_real_time_by(strtotime(trim($date[$ii])),$come_month_num,$i-1);
							}

							$data['is_period'] = $ii+1;      //分期后的期数

							Db::name('customer_payment_back')->insert($data);

							unset($data['amount_of_money']);
							unset($data['back_payment_time']);
						}
					}
				}
			}
		}
	}


	/**
	 * 列入：   月付，分期   ，时其他月的预付款和尾款 真实时间
	 *
	 * @param $time
	 * @param $month
	 * @param int $param
	 * @return int
	 */
	private function _get_real_time_by($time,$month,$param = 1)
	{
		$data = date('Y-m-d',$time);
		$data = explode('-',$data);
		$month = $month * $param;

		$add_month = $data[1] + $month;
		if($add_month > 12)
		{
			$real_time = ($data[0]+1).'-'.($add_month - 12).'-'.$data[2];
		}
		else
		{
			$real_time = ($data[0]).'-'.($add_month).'-'.$data[2];
		}
		return strtotime($real_time);
	}


	/**
	 * 编辑显示
	 */
	public function customer_edit()
	{
		/**
		 * 项目id
		 */
		$n_id = input('n_id');

		if (empty($n_id))
		{
			$this->error('参数错误',url('admin/customer/customer_list'));
		}

		/**
		 * 获得showdata               优先级是customer_extra
		 */
		$customer_list=customerModel::get($n_id);
		$customer_extra=json_decode($customer_list['customer_extra'],true);
		$customer_extra['showdate']=($customer_extra['showdate']=='')?$customer_list['customer_time']:$customer_extra['showdate'];

		/**
		 * 下拉框集合
		 */
		$this->_get_customer_select_info();

		$this->assign('customer_extra',$customer_extra);
		$this->assign('customer_list',$customer_list);
		return $this->fetch();
	}


	/**
	 * 编辑操作
	 */
	public function customer_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/customer/customer_list'));
		}

		$data=input('post.');

		$rst=customerModel::update($data);

		//存入交接人详情表        waiter_id    add_time
		Db::name('customer_waiter_re')->insert(array('customer_id' => input('n_id'),'waiter_id' => input('customer_waiter_1'),'add_time' => time()));
		Db::name('customer_waiter_re')->insert(array('customer_id' => input('n_id'),'waiter_id' => input('customer_waiter_2'),'add_time' => time()));

		if($rst!==false)
		{
			//回款类型处理：  过滤 99
//			$this->do_payment_back(input('n_id'),input('customer_cpprice'),$payment_type_id,input('customer_collection_period'),input('customer_contract_cycle_id'),input('customer_execution_time'),input('customer_service_items'),'edit');

			$this->success('项目修改成功,返回列表页',url('admin/customer/customer_list'));
		}else{
			$this->error('项目修改失败',url('admin/customer/customer_list'));
		}
	}

	/**
	 *
	 * 回款类型:
	 * 1                等分
	 * 2                建站类型
	 *
	 * @param $customer_id                                   项目id
	 * @param $customer_cpprice                              签约总金额
	 * @param $payment_type_id                               回款类型
	 * @param $customer_collection_period                    回款周期
	 * @param $customer_contract_cycle_id                    合同周期
	 * @param $customer_execution_time                       执行时间
	 * @param string $param
	 * @param string $service_items
	 */
	private function do_payment_back($customer_id,$customer_cpprice,$customer_collection_period,$customer_contract_cycle_id,$customer_execution_time,$service_items,$param = 'add')
	{
		$come_month_num = $this->_get_come_month_num($customer_collection_period);               //回款周期  每期月数
		$cycle_month_num = $this->_get_cycle_month_num($customer_contract_cycle_id);              //合同周期  月数
//		log::error('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

		//一次付的
		if($come_month_num == 99)
		{
			$come_time = $this->_get_date_from_date($customer_execution_time,$cycle_month_num);
			$data = array(
				'customer_id' => $customer_id,                          //项目id
				'number_of_periods' => 1,                               //第几期
				'amount_of_money' => $customer_cpprice,                 //该期金额
				'back_payment_time' => $come_time,                      //理论回款时间
				'total_amount' => $customer_cpprice,                    //理论总金额
				'total_number_of_periods' => 1,                         //理论总期数
				'service_items' => $service_items,                       //项目类型
			);

			Db::name('customer_payment_back')->insert($data);
		}
		//非一次付的
		else
		{
			switch($payment_type_id)
			{
//				等分
				case 1:

					//不能整除
					if($cycle_month_num % $come_month_num)
					{

					}
					else
					{
						//整除，出多少个月份： 出多少期
						$num = $cycle_month_num/$come_month_num;          //出多少期
						$money_unit = $customer_cpprice/$num;             //每期的钱

						$data['total_number_of_periods'] = $num;
						$data['total_amount'] = $customer_cpprice;
						$data['customer_id'] = $customer_id;
						$data['amount_of_money'] = $money_unit;
						$data['service_items'] = $service_items;

						$come_time = '';
						for($i = 1; $i <= $num;$i++)
						{
							if($i == 1)
							{
								$come_time = $this->_get_date_from_date($customer_execution_time,$come_month_num);    //理论回款时间
							}
							else
							{
								$come_time = $this->_get_date_from_date(date('Y-m-d',$come_time),$come_month_num);
							}
							$data['back_payment_time'] = $come_time;
							$data['number_of_periods'] = $i;

							Db::name('customer_payment_back')->insert($data);

							unset($data['back_payment_time']);
							unset($data['number_of_periods']);
						}

					}
					break;

				//网站建设
				case 2:
					break;
			}

		}

	}



	/**
	 *
	 * 未分期的数据存入:
	 * 1                等分
	 * 2                建站类型
	 *
	 * @param $customer_id                                   项目id
	 * @param $customer_cpprice                              签约总金额
	 * @param $customer_collection_period                    回款周期
	 * @param $customer_contract_cycle_id                    合同周期
	 * @param $customer_execution_time                       执行时间
	 * @param string $param
	 * @param string $service_items
	 */
	private function do_payment_back_1($customer_id,$customer_cpprice,$customer_collection_period,$customer_contract_cycle_id,$customer_execution_time,$service_items)
	{
		$customer_execution_time_str = $customer_execution_time;
		$customer_execution_time = date('Y-m-d',$customer_execution_time);
		$come_month_num = $this->_get_come_month_num($customer_collection_period);               //回款周期
		$cycle_month_num = $this->_get_cycle_month_num($customer_contract_cycle_id);              //合同周期

		//一次付的
		if($come_month_num == 99)
		{
//			$come_time = $this->_get_date_from_date($customer_execution_time,$cycle_month_num);
			$data = array(
				'customer_id' => $customer_id,                          //项目id
				'number_of_periods' => 1,                               //第几期
				'amount_of_money' => $customer_cpprice,                 //该期金额
//				'back_payment_time' => $come_time,                      //理论回款时间
				'back_payment_time' => $customer_execution_time_str,                      //理论回款时间
				'total_amount' => $customer_cpprice,                    //理论总金额
				'total_number_of_periods' => 1,                         //理论总期数
				'service_items' => $service_items,                       //项目类型
			);

			Db::name('customer_payment_back')->insert($data);
		}
		//非一次付的
		else
		{
			//不能整除
			if($cycle_month_num % $come_month_num)
			{

			}
			else
			{
				//整除，出多少个月份： 出多少期
				$num = $cycle_month_num/$come_month_num;          //出多少期
				$money_unit = $customer_cpprice;             //每期的钱

				$data['total_number_of_periods'] = $num;
				$data['total_amount'] = $customer_cpprice*$num;
				$data['customer_id'] = $customer_id;
				$data['amount_of_money'] = $money_unit;
				$data['service_items'] = $service_items;

				//不分期
				$data['is_period'] = 0;

				$come_time = '';
				for($i = 1; $i <= $num;$i++)
				{
					if($i == 1)
					{
//						$come_time = $this->_get_date_from_date($customer_execution_time,$come_month_num);    //理论回款时间
						$come_time = $customer_execution_time_str;
					}
					else
					{
						$come_time = $this->_get_date_from_date(date('Y-m-d',$come_time),$come_month_num);
					}
					$data['back_payment_time'] = $come_time;
					$data['number_of_periods'] = $i;

					Db::name('customer_payment_back')->insert($data);

					unset($data['back_payment_time']);
					unset($data['number_of_periods']);
				}

			}

		}

	}


	/**
	 * 根据执行日期，增加的月数，获得当期的回款时间
	 *
	 * @param $date
	 * @param $add_month
	 * @return int
	 */
	private function _get_date_from_date($date,$add_month)
	{
//		$date = date('Y-m-d',$str);

		//得到  年月日：$time_former[0]/$time_former[1]/$time_former[2]
		$time_former = explode('-',$date);

		//相加后的月数
		$now_month = $add_month + $time_former[1];

		//满一年
		if(($now_month >= 13) && ($now_month < 25))
		{
			$year = $time_former[0]+1;
			$month = $now_month - 12;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		elseif($now_month >= 25)
		{
			$year = $time_former[0]+2;
			$month = $now_month - 24;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		else
		{
			$need_data = $time_former[0].'-'.$now_month.'-'.$time_former[2];
		}
		return strtotime($need_data);
	}

	/**
	 * 	 * 4    一次付     一次付        99   表示一次付
	 * 6    季付          3
	 * 7    半年付        6
	 * 8    年付          12
	 * 10   二年付        24
	 * 11    月付         1
	 * @param $customer_collection_period
	 * @return int
	 */
	private function _get_come_month_num($customer_collection_period)
	{
		switch($customer_collection_period)
		{
			case 4:
			case 12:
				$result = 99;
				break;
			case 6:
				$result = 3;
				break;
			case 7:
				$result = 6;
				break;
			case 8:
				$result = 12;
				break;
			case 10:
				$result = 24;
				break;
			case 11:
				$result = 1;
				break;
		}
		return $result;
	}

	/**
	 *  *  * 合同周期：    contract_cycle
	 * 1   一个月       1
	 * 2   一季度       3
	 * 3   半年         6
	 * 4   一年         12
	 * 5   二年         24
	 * 6   三年         36
	 *
	 * @param $customer_contract_cycle_id
	 * @param $customer_contract_cycle_id
	 * @return int
	 */
	private function _get_cycle_month_num($customer_contract_cycle_id)
	{
		switch($customer_contract_cycle_id)
		{
			case 1:
				$result = 1;
				break;
			case 2:
				$result = 3;
				break;
			case 3:
				$result = 6;
				break;
			case 4:
				$result = 12;
				break;
			case 5:
				$result = 24;
				break;
			case 6:
				$result = 36;
				break;
			case 8:
				$result = 4;
				break;
		}
		return $result;
	}

	/**
	 * 项目排序
	 */
	public function customer_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/customer/customer_list'));
		}else{
			$list=[];
			foreach (input('post.') as $n_id => $customer_order){
				$list[]=['n_id'=>$n_id,'listorder'=>$customer_order];
			}
			$customer_model=new customerModel;
			$customer_model->saveAll($list);
			$this->success('排序更新成功',url('admin/customer/customer_list'));
		}
	}

	/**
	 * 删除至回收站(单个)
	 */
	public function customer_del()
	{
		$p=input('p');
		$customer_model=new customerModel;
		$rst=$customer_model->where(array('n_id'=>input('n_id')))->setField('customer_back',1);//转入回收站
		if($rst!==false){
			$this->success('项目已转入回收站',url('admin/customer/customer_list',array('p' => $p)));
		}else{
			$this -> error("删除项目失败！",url('admin/customer/customer_list',array('p'=>$p)));
		}
	}

	/**
	 * 删除至回收站(全选)
	 */
	public function customer_alldel()
	{
		$p = input('p');
		$ids = input('n_id/a');
		if(empty($ids)){
			$this -> error("请选择删除项目",url('admin/customer/customer_list',array('p'=>$p)));//判断是否选择了项目ID
		}
		if(is_array($ids)){//判断获取项目ID的形式是否数组
			$where = 'n_id in('.implode(',',$ids).')';
		}else{
			$where = 'n_id='.$ids;
		}
		$customer_model=new customerModel;
		$rst=$customer_model->where($where)->setField('customer_back',1);//转入回收站
		if($rst!==false){
			$this->success("成功把项目移至回收站！",url('admin/customer/customer_list',array('p'=>$p)));
		}else{
			$this -> error("删除项目失败！",url('admin/customer/customer_list',array('p'=>$p)));
		}
	}

	/**
	 * 回收站列表
	 */
	public function customer_back()
	{
		$this->_get_customer_select_info(true);
		$keytype=input('keytype','customer_title');
		$key=input('key');

		$opentype_check=input('opentype_check','');

		//查询：时间格式过滤 格式 2015-11-12 - 2015-11-18
		$sldate=input('reservation','');
		$arr = explode(" - ",$sldate);
		if(count($arr)==2){
			$arrdateone=strtotime($arr[0]);
			$arrdatetwo=strtotime($arr[1].' 23:55:55');
			$map['customer_time'] = array(array('egt',$arrdateone),array('elt',$arrdatetwo),'AND');
		}

		//map架构查询条件数组
		$map['customer_back']= 1;
		if(!empty($key)){
			$map[$keytype]= array('like',"%".$key."%");
		}
		if ($opentype_check!=''){
			$map['customer_open']= array('eq',$opentype_check);
		}

		$customer_model=new customerModel;

		$customer=$customer_model->alias("a")->field('a.*')
			->where(array('a.customer_back' => 1))->order('customer_status desc,customer_time desc')->paginate(12,false,['query'=>get_query()]);

		$show = $customer->render();
		$show=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$show);
		$this->assign('page',$show);

		//项目属性数据
		$this->assign('opentype_check',$opentype_check);
		$this->assign('keytype',$keytype);
		$this->assign('keyy',$key);
		$this->assign('sldate',$sldate);
		$this->assign('customer',$customer);

		if(request()->isAjax())
		{
			return $this->fetch('ajax_customer_back');
		}
		else
		{
			return $this->fetch();
		}
	}
	/**
	 * 还原项目
	 */
	public function customer_back_open()
	{
		$p=input('p');
		$customer_model=new customerModel;
		$rst=$customer_model->where('n_id',input('n_id'))->setField('customer_back',0);//转入正常
		if($rst!==false){
			$this->success('项目还原成功',url('admin/customer/customer_back',array('p' => $p)));
		}else{
			$this -> error("项目还原失败！",url('admin/customer/customer_back',array('p' => $p)));
		}
	}
	/**
	 * 彻底删除(单个)
	 */
	public function customer_back_del()
	{
		$n_id=input('n_id');

		$aa = Db::name('customer_payment_back')->where('customer_id',$n_id)->select();
		$p = input('p');
		$customer_model=new customerModel;
		if (empty($n_id))
		{
			$this->error('参数错误',url('admin/customer/customer_back',array('p' => $p)));
		}
		else
		{
			$rst=$customer_model->where('n_id',input('n_id'))->delete();
			if($rst!==false)
			{
				$this->success('项目彻底删除成功',url('admin/customer/customer_back',array('p' => $p)));
			}
			else
			{
				$this -> error("项目彻底删除失败！",url('admin/customer/customer_back',array('p' => $p)));
			}
		}
	}
	/**
	 * 彻底删除(全选)
	 */
	public function customer_back_alldel()
	{
		$p = input('p');
		$ids = input('n_id/a');
		if(empty($ids)){
			$this -> error("请选择删除项目",url('admin/customer/customer_back',array('p'=>$p)));//判断是否选择了项目ID
		}
		if(is_array($ids)){//判断获取项目ID的形式是否数组
			$where = 'n_id in('.implode(',',$ids).')';
		}else{
			$where = 'n_id='.$ids;
		}
		$customer_model=new customerModel;
		$rst=$customer_model->where($where)->delete();
		if($rst!==false){
			$this->success("成功把项目删除，不可还原！",url('admin/customer/customer_back',array('p'=>$p)));
		}else{
			$this -> error("项目彻底删除失败！",url('admin/customer/customer_back',array('p' => $p)));
		}
	}

	/**
	 * 项目排序
	 */
	public function customer_explain()
	{
		return $this->fetch();
	}

	/**
	 * 项目下拉框合集
	 *
	 * @param bool|false $param
	 */
	private function _get_customer_select_info($param = false)
	{
		//服务项目：
//		$customer_service_items = Db::name('service_items')->select();
		$customer_service_items = Db::name('service_items')->where('level',2)->select();
		$this->assign('customer_service_items',$customer_service_items);

		//信用等级：
		$customer_credit_rating = Db::name('credit_rating')->select();
		$this->assign('customer_credit_rating',$customer_credit_rating);

		//对接人：
		$customer_waiter = Db::name('customer_waiter')->select();
		$this->assign('customer_waiter',$customer_waiter);

//商务对接人
		$customer_waiter_1 = Db::name('customer_waiter')
//			->where(array('waiter_type' => 1))
			->select();

		$this->assign('customer_waiter_1',$customer_waiter_1);

//技术对接人
		$customer_waiter_2 = Db::name('customer_waiter')
//			->where(array('waiter_type' => 2))
			->select();
		$this->assign('customer_waiter_2',$customer_waiter_2);

		//项目状态：
		$customer_status = Db::name('customer_status')->select();
		$this->assign('customer_status',$customer_status);

		//回款周期：
		$customer_collection_period = Db::name('collection_period')->select();
		$this->assign('customer_collection_period',$customer_collection_period);

		//合同周期：
		$customer_contract_cycle = Db::name('customer_contract_cycle')->select();
		$this->assign('customer_contract_cycle',$customer_contract_cycle);

		//回款类型：
		$customer_payment_type = Db::name('customer_payment_type')->where(array('is_custom' => 2))->select();
		$this->assign('customer_payment_type',$customer_payment_type);

		//创建者：
		$customer_creator_info = Db::name('admin')->column('admin_id,admin_username');
		$this->assign('customer_creator_info',$customer_creator_info);

		//公司名：
		$company_info = Db::name('company')
//			->where('is_open',0)
			->field('company_id,company_name')->select();
		$this->assign('company_info',$company_info);

		//行业类别
		$industry_category = Db::name('industry_category')->field('industry_category_id,industry_category_name')->select();
		$this->assign('industry_category',$industry_category);

		$company_info_1 = array_column($company_info,'company_name');
		$this->assign('company_info_str',implode(',',$company_info_1));

//		$this->p($industry_category,1);

		/**
		 * 得到  $arr_need['arr_id] => arr_val
		 */
		if($param)
		{
//			若没有公司
			if($company_info)
			{
				foreach($company_info as $company_info_unit)
				{
					$company_info_need[$company_info_unit['company_id']] = $company_info_unit['company_name'];
				}
			}

			foreach($customer_service_items as $customer_service_items_unit)
			{
				$customer_service_items_need[$customer_service_items_unit['service_items_id']] = $customer_service_items_unit['service_items_name'];
			}

			foreach($customer_credit_rating as $customer_credit_rating_unti)
			{
				$customer_credit_rating_need[$customer_credit_rating_unti['credit_rating_id']] = $customer_credit_rating_unti['credit_rating_name'];
			}

			foreach($customer_waiter as $customer_waiter_unti)
			{
				$customer_waiter_need[$customer_waiter_unti['customer_waiter_id']] = $customer_waiter_unti['customer_waiter_name'];
			}
			foreach($customer_status as $customer_status_unti)
			{
				$customer_status_need[$customer_status_unti['customer_status_id']] = $customer_status_unti['customer_status_name'];
			}

			foreach($customer_collection_period as $customer_collection_period_unit)
			{
				$customer_collection_period_need[$customer_collection_period_unit['collection_period_id']] = $customer_collection_period_unit['collection_period_name'];
			}

			foreach($customer_contract_cycle as $customer_contract_cycle_unit)
			{
				$customer_contract_cycle_need[$customer_contract_cycle_unit['customer_contract_cycle_id']] = $customer_contract_cycle_unit['customer_contract_cycle_name'];
			}

			foreach($customer_payment_type as $customer_payment_type_unit)
			{
				$customer_payment_type_need[$customer_payment_type_unit['customer_payment_type_id']] = $customer_payment_type_unit['customer_payment_type_name'];
			}

			if(!isset($customer_credit_rating_need)||!$customer_credit_rating_need){ $customer_credit_rating_need = array(); }

			$this->assign('customer_service_items_need',$customer_service_items_need);
			$this->assign('customer_credit_rating_need',$customer_credit_rating_need);
			$this->assign('customer_waiter_need',$customer_waiter_need);
			$this->assign('customer_status_need',$customer_status_need);
			$this->assign('customer_collection_period_need',$customer_collection_period_need);
			$this->assign('customer_contract_cycle_need',$customer_contract_cycle_need);
			$this->assign('customer_payment_type_need',$customer_payment_type_need);

			if($company_info)
			{
				$this->assign('company_info_need',$company_info_need);
			}
		}

	}



	/**************************************************************************************************************************************\
	 *
	 * 需判断权限：
	 *
	 * 下拉修改区域
	 */

	/**
	 * 是否拥有修改权限
	 *
	 * @param int $n_id
	 * @return bool
	 */
	private  function _is_modify_oauth($n_id = 2)
	{

		//是否是未审核项目，若是未审核项目，则不能审核过
		$status=Db::name('customer')->where('n_id',$n_id)->value('customer_open');//判断当前状态情况
		if($status == 0)
		{
			$this->error('该项目处于审核状态');
		}


		//以下是管理员权限验证，是否有权限
		$admin_info = $_SESSION['think']['admin_auth'];
		$admin_id = $admin_info['aid'];                           //管理员id

		//获取管理员所在管理员组： 1超级组、2系统组、3普通组
		$admin_group = db('auth_group_access')->where('uid',$admin_id )->column('group_id');

		switch($admin_group[0])
		{
			//超级管理员
			case 1:
				return true;
				break;
			//系统管理员
			case 2:
				return true;
				break;

			//普通管理员
			case 3:

//				是否为自己创建的项目
				$customer_auto = db('customer')->where('n_id',$n_id )->column('customer_auto');

				//自己创建的项目
				if($admin_id == $customer_auto[0])
				{
					return true;
				}
				else
				{
					$this->error('没有权限修改');
				}
				break;
		}
	}


	/**
	 * 商务技术员修改
	 */
	public function customer_waiter_1()
	{
		$customer_waiter_1=input('customer_waiter_1');           //商务部技术人员id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_waiter_1'=>$customer_waiter_1]);
			if($rst!==false)
			{
				$this->success('商务技术人更新成功');
			}
			else
			{
				$this->error('商务技术人更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}

	/**
	 * 技术对接员修改
	 */
	public function customer_waiter_2()
	{
		$customer_waiter_2=input('customer_waiter_2');           //商务部技术人员id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_waiter_2'=>$customer_waiter_2]);
			if($rst!==false)
			{
				$this->success('技术对接人更新成功');
			}
			else
			{
				$this->error('技术对接人更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}

	/**
	 * 信用等级
	 */
	public function customer_credit_rating()
	{
		$customer_credit_rating=input('customer_credit_rating');           //商务部技术人id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);
		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_credit_rating'=>$customer_credit_rating]);
			if($rst!==false)
			{
				$this->success('信用等级更新成功');
			}
			else
			{
				$this->error('信用等级更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}


	/**
	 * 项目状态
	 */
	public function customer_status_doing()
	{

		if($this->_get_admin_group_id() == 3)
		{
			$this->error('项目状态不能随便更改，请联系超级管理员');
			exit;
		}


		$customer_collection_period=input('customer_status_doing');           //商务部技术人id号
		$n_id=input('n_id');          //项目id

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_collection_period'=>$customer_collection_period]);
			if($rst!==false)
			{
				$this->success('项目状态更新成功');
			}
			else
			{
				$this->error('项目状态更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}



	/**
	 * 回款周期
	 */
	public function customer_collection_period()
	{
		$this->error('回款周期不能随便更改，请联系超级管理员');
		exit;

		$customer_collection_period=input('customer_collection_period');           //商务部技术人id号
		$n_id=input('n_id');          //项目id

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_collection_period'=>$customer_collection_period]);
			if($rst!==false)
			{
				$this->success('回款周期更新成功');
			}
			else
			{
				$this->error('回款周期更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}


	/**
	 * 合同周期修改
	 */
	public function customer_contract_cycle()
	{
		$this->error('合同周期不能随便更改，请联系超级管理员');
		exit;
		$customer_contract_cycle=input('customer_contract_cycle');           //合同周期id号
		$n_id=input('n_id');          //项目id

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_contract_cycle'=>$customer_contract_cycle]);
			if($rst!==false)
			{
				$this->success('合同周期更新成功');
			}
			else
			{
				$this->error('合同周期更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}


	/**
	 * 回款类型修改
	 */
	public function customer_payment_type()
	{
		$this->error('回款类型不能随便更改，请联系超级管理员');
		exit;

		$customer_payment_type=input('customer_payment_type');           //回款类型id号
		$n_id=input('n_id');          //项目id

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_payment_type'=>$customer_payment_type]);
			if($rst!==false)
			{
				$this->success('回款类型更新成功');
			}
			else
			{
				$this->error('回款类型更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}

	/**
	 * 服务项目修改
	 */
	public function customer_service_items()
	{
		$customer_service_items=input('customer_service_items');           //服务项目id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['customer_service_items'=>$customer_service_items]);
			if($rst!==false)
			{
				$this->success('服务项目更新成功');
			}
			else
			{
				$this->error('服务项目更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}



	/**
	 * 公司名修改
	 */
	public function customer_company1_name1()
	{
		$customer_company1_name1=input('customer_company1_name1');           //公司名id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['company_id'=>$customer_company1_name1]);
			if($rst!==false)
			{
				$this->success('公司名更新成功');
			}
			else
			{
				$this->error('公司名更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}

	/**
	 * 账户修改
	 */
	public function customer_acount1_doing1()
	{
		$customer_acount1_doing1=input('customer_acount1_doing1');           //账户id号
		$n_id=input('n_id');          //项目id

		$this->_is_modify_oauth($n_id);

		$data = db('customer')->find($n_id);

		if($data)
		{
			$rst=db('customer')->where('n_id',$n_id)->update(['account'=>$customer_acount1_doing1]);
			if($rst!==false)
			{
				$this->success('账户更新成功');
			}
			else
			{
				$this->error('账户更新失败');
			}
		}
		else
		{
			$this->error('项目不存在');
		}
	}


	/**
	 * 返回 标识数据
	 *
	 * @return array
	 */
	private function _get_export_data($param = false)
	{
		$data_1 = array(
			'contract_number' => '项目编号',
			'customer_title' => '项目名',
			'company_id' => '公司名',
			'customer_service_items' => '服务项目',
			'customer_cpprice' => '签约总金额',

			'amount_of_money' => '签约金额/期',
			'total_number_of_periods' => '期数',
			'amount_of_money_1' => '当月应回(元)',
			'actual_amount_of_money' => '当月回款情况',
			'account' => '账户',

			'customer_payment_type' => '回款类型',
//			'customer_credit_rating' => '信用等级',
			'customer_waiter_1' => '商务对接人',
			'customer_waiter_2' => '技术对接人',

			'customer_status' => '项目状态',
			'customer_collection_period' => '回款周期',
			'customer_contract_cycle' => '合同周期',
			'customer_contract_time' => '签约时间',

			'customer_execution_time' => '执行时间',
			'customer_expiration_date' => '合同到期时间',
			'statement_of_settlement' => '结算方式说明',
		);


		/**
		 * 获取有条件的数值
		 */
		if($param)
		{
			foreach ($data_1 as $key => $val)
			{
				if(in_array($key,$param))
				{
					$data_3[$key] = $val;
				}
			}
		}


		$data_2 = array(
			'contract_number' => '项目编号',
			'customer_title' => '项目名',

			'company_id' => '公司名',
			'customer_service_items' => '服务项目',
			'customer_cpprice' => '签约总金额',
			'account' => '账户',

			'customer_payment_type' => '回款类型',
//			'customer_credit_rating' => '信用等级',
			'customer_waiter_1' => '商务对接人',
			'customer_waiter_2' => '技术对接人',

			'customer_status' => '项目状态',
			'customer_collection_period' => '回款周期',
			'customer_contract_cycle' => '合同周期',
			'customer_contract_time' => '签约时间',

			'customer_execution_time' => '执行时间',
			'customer_expiration_date' => '合同到期时间',
			'statement_of_settlement' => '结算方式说明',
		);

		$this->assign('export_data',$data_1);

		if($param)
		{
			$data_4 = $data_3;
		}
		else
		{
			$data_4 = $data_1;
		}

		return array($data_4,$data_2);
	}


	/**
	 * 获取总的数据
	 *
	 * @return mixed
	 */
	private function _get_export_data2()
	{
		$company_info = db('company')->column('company_id,company_name');
		$service_items = db('service_items')->column('service_items_id,service_items_name');

		$credit_rating = db('credit_rating')->column('credit_rating_id,credit_rating_name');
		$customer_waiter = db('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$customer_status = db('customer_status')->column('customer_status_id,customer_status_name');

		$customer_contract_cycle = db('customer_contract_cycle')->column('customer_contract_cycle_id,customer_contract_cycle_name');
		$customer_collection_period = db('collection_period')->column('collection_period_id,collection_period_name');

		$customer_payment_type = db('customer_payment_type')->column('customer_payment_type_id,customer_payment_type_name');
		$account = array(
			1 => '多蓝',
			2 => '树蓝',
			3 => '暂不定义'
		);

		$field = 'n_id,contract_number,customer_title,company_id,customer_service_items,customer_cpprice,account,customer_payment_type,customer_waiter_1,customer_waiter_2,customer_status,customer_collection_period,customer_contract_cycle,customer_contract_time,customer_execution_time,customer_expiration_date,statement_of_settlement';
		$data = db('customer')->column($field);

		$now_month = date('Ym');

//		'amount_of_money' => '签约金额/期',
//			'number_of_periods' => '期数',
//			'amount_of_money_1' => '当月应回(元)',
//			'actual_amount_of_money' => '当月回款情况',

		foreach($data as $key => $row)
		{
			$customer_payment_back = db('customer_payment_back')
				->where(array('customer_id' => $row['n_id']))
				->column('back_payment_id,amount_of_money,total_number_of_periods,actual_amount_of_money,back_payment_time,number_of_periods');
//->select();
//			foreach($customer_payment_back as $aaaa)
//			{
//				write2($aaaa);
//			}
//			$this->p($customer_payment_back,1);
			//初始值
			$amount_of_money = '无';
			$total_number_of_periods = '无';
			$actual_amount_of_money = '无';
			$amount_of_money_1 = '无';

			//如果存在回款
			if($customer_payment_back)
			{
				foreach($customer_payment_back as $val)
				{

					if($val['number_of_periods'] == 1)
					{
						$amount_of_money = $val['amount_of_money'];
						$total_number_of_periods = $val['total_number_of_periods'];
					}

					//当月应还
					if(date('Ym',$val['back_payment_time']) == $now_month)
					{
						$amount_of_money_1 = $val['amount_of_money'];
						$actual_amount_of_money = $val['actual_amount_of_money'];
					}
				}
			}

			//对$row的数据进行处理:
			foreach($row as $key_1 => $val_1)
			{
				//公司
				if($key_1 == 'company_id')
				{
					$row[$key_1] = $company_info[$val_1];
				}

				//服务项目
				if($key_1 == 'customer_service_items')
				{
					if(!isset($service_items[$val_1]))
					{
						$row[$key_1] = 'aaaaa';continue;
					}
					$row[$key_1] = $service_items[$val_1];
				}

				//账户
				if($key_1 == 'account')
				{
					$row[$key_1] = $account[$val_1];
				}

				//回款类型
				if($key_1 == 'customer_payment_type')
				{
					$row[$key_1] = $customer_payment_type[$val_1];
				}

				//信用等级
				if($key_1 == 'customer_credit_rating')
				{
					$row[$key_1] = $credit_rating[$val_1];
				}

				//商务对接人
				if($key_1 == 'customer_waiter_1')
				{
					$row[$key_1] = $customer_waiter[$val_1];
				}

				//技术对接人
				if($key_1 == 'customer_waiter_2')
				{
					$row[$key_1] = $customer_waiter[$val_1];
				}

				//项目状态
				if($key_1 == 'customer_status')
				{
					$row[$key_1] = $customer_status[$val_1];
				}

				//合同周期
				if($key_1 == 'customer_contract_cycle')
				{
					$row[$key_1] = $customer_contract_cycle[$val_1];
				}

				//回款周期
				if($key_1 == 'customer_collection_period')
				{
					$row[$key_1] = $customer_collection_period[$val_1];
				}

				//签约时间
				if($key_1 == 'customer_contract_time')
				{
					if($val_1)
					{
						$row[$key_1] = date('Y-m-d',$val_1);
					}
					else
					{
						$row[$key_1] = '无';
					}
				}

				//执行时间
				if($key_1 == 'customer_execution_time')
				{
					if($val_1)
					{
						$row[$key_1] = date('Y-m-d',$val_1);
					}
					else
					{
						$row[$key_1] = '无';
					}
				}

				//合同到期时间
				if($key_1 == 'customer_expiration_date')
				{
					if($val_1)
					{
						$row[$key_1] = date('Y-m-d',$val_1);
					}
					else
					{
						$row[$key_1] = '无';
					}
				}
			}

			$row['amount_of_money'] = $amount_of_money;
			$row['total_number_of_periods'] = $total_number_of_periods;
			$row['amount_of_money_1'] = $amount_of_money_1;
			$row['actual_amount_of_money'] = $actual_amount_of_money;

			$need[$key] = $row;
		}
		return $need;
	}

	/**
	 * 取得所需数据
	 *
	 * @param $data
	 * @param $field
	 * @return mixed
	 */
	private function _get_export_data3($data,$field)
	{
		foreach($data as $key => $val)
		{
			foreach($val as $k => $v)
			{
				if(in_array($k,$field))
				{
					$need_data[$k] = $v;
				}
			}

			$need[$key] = $need_data;
		}

		return $need;
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
	 * 合成到期时间
	 *
	 * @param $customer_contract_cycle_id
	 * @param $customer_execution_time
	 * @return string
	 */
	private function _get_expiration_date($customer_contract_cycle_id,$customer_execution_time)
	{
//		合同周期
		$add_month = $this->get_cycle_month($customer_contract_cycle_id);

//		执行日期
		//得到  年月日：$time_former[0]/$time_former[1]/$time_former[2]
		$time_former = explode('-',$customer_execution_time);

		//相加后的月数
		$now_month = $add_month + $time_former[1];

		//满一年
		if(($now_month >= 13) && ($now_month < 25))
		{
			$year = $time_former[0]+1;
			$month = $now_month - 12;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		elseif($now_month >= 25)
		{
			$year = $time_former[0]+2;
			$month = $now_month - 24;
			$need_data = $year.'-'.$month.'-'.$time_former[2];
		}
		else
		{
			$need_data = $time_former[0].'-'.$now_month.'-'.$time_former[2];
		}

		$need_data = strtotime($need_data);
		return $need_data;
	}

	/**
	 * 管理员开启/禁止
	 */
	public function customer_state()
	{
		/**
		 * 普通会员无权修改
		 */
		if($this->_get_admin_group_id() == 3)
		{
			$this->error('无权修改',url('admin/Customer/customer_list'));
		}

//		如果该项目对应的公司处于审核中，则不能修改


		$id=input('x');
		$need_data = explode('-',$id);
		$id = $need_data[0];
		$company_id = $need_data[1];

		$company_status=Db::name('company')->where('company_id',$company_id)->value('is_open');//判断当前状态情况
		if($company_status)
		{
			$this->error('项目对应的公司信息还未审核',url('admin/Customer/customer_list'));
		}


		if (empty($id))
		{
			$this->error('项目不存在',url('admin/Customer/customer_list'));
		}
		$status=Db::name('customer')->where('n_id',$id)->value('customer_open');//判断当前状态情况
		if($status==1)
		{
			$statedata = array('customer_open'=>0);
			Db::name('customer')->where('n_id',$id)->setField($statedata);
			$this->success(0);
		}else{
			$statedata = array('customer_open'=>1);
			Db::name('customer')->where('n_id',$id)->setField($statedata);
			$this->success(1);
		}
	}




	//////////////////////////////////////////////////////////////////////////////////// 修改回款清单
	/**
	 * 编辑显示
	 */
	public function customer_edit_1()
	{
		/**
		 * 项目id
		 */
		$n_id = input('n_id');

		if (empty($n_id))
		{
			$this->error('参数错误',url('admin/customer/customer_list'));
		}

		//根据回款id  获得所有清单 列表内容
		$need = Db::name('customer_payment_back')->where('customer_id',$n_id)->field('is_period,number_of_periods,amount_of_money')->select();

//		$this->p($need,1);

//		[1] => Array
//	(
//		[is_period] => 1
//            [number_of_periods] => 1
//            [amount_of_money] => 1000000

		if($need)
		{
			foreach($need as $row)
			{
				$aa[$row['number_of_periods']][] = $row;
			}
		}
		else
		{
			$this->error('无回款清单',url('admin/customer/customer_list'));
		}

//		$this->p($aa);exit;



		/**
		 * 获得showdata               优先级是customer_extra
		 */
		$customer_list=customerModel::get($n_id);
		$customer_extra=json_decode($customer_list['customer_extra'],true);
		$customer_extra['showdate']=($customer_extra['showdate']=='')?$customer_list['customer_time']:$customer_extra['showdate'];

		/**
		 * 下拉框集合
		 */
		$this->_get_customer_select_info();

		$this->assign('customer_extra',$customer_extra);
		$this->assign('customer_list',$customer_list);
		$this->assign('aa',$aa);
		return $this->fetch();
	}



	/**
	 * 编辑操作
	 */
	public function customer_runedit_1()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/customer/customer_list'));
		}

		$data=input('post.');
		$amount_of_money = $data['amount_of_money'];
		$customer_id = $data['n_id'];
		$total = 0;
		if($amount_of_money)
		{
			foreach($amount_of_money as $k => $v)
			{
				$map['number_of_periods'] = $k;
				foreach($v as $k_1 => $v_1)
				{
					$map['is_period'] = $k_1;
					$total += $v_1;
				}
			}

			//验证总数有没有变化
			$map['customer_id'] = $customer_id;
			$total_va = Db::name('customer_payment_back')->where($map)->value('total_amount');

			if($total != $total_va)
			{
				$this->error('修改失败，金额总数变了',url('admin/customer/customer_list'));
			}

			foreach($amount_of_money as $k => $v)
			{

				$map['customer_id'] = $customer_id;
				$map['number_of_periods'] = $k;

				foreach($v as $key  => $val)
				{
					$map['is_period'] = $key;

					Db::name('customer_payment_back')->where($map)->update(['amount_of_money' => $val]);
				}
			}
			$this->success('应回款项修改成功,返回列表页',url('admin/customer/customer_list'));
		}

		$this->error('该项目无回款清单',url('admin/customer/customer_list'));

	}


}