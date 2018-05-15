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

class Datain extends Base
{
	/**
	 *
	 * http://tp.demo/admin/datain
	 *
	 * 导入测试数据
	 */
	public function index()
	{

//		exit;
		$this->get_data();
		echo '导入测试数据成功！';
	}


	private function get_data()
	{
		for($i = 1;$i < 100;$i++)
		{
			$rand_1 = rand(4,5);

			$name = '测试企业'.$i;

			$service_items = rand(1,19);

			$credit = rand(1,4);

			$status = 6;

			$customer_collection_period = 11;

			$customer_contract_cycle = 3;            //合同周期

			$customer_payment_type = 1;


			$customer_cpprice = 600*$rand_1;

			$customer_contract_time = time() - 3600*24*30*$rand_1 - 3600*24*16;

			$customer_execution_time = $customer_contract_time + 3600*24*1;
			$customer_execution_time_1 = date('Y-m-d',$customer_execution_time);

			$customer_expiration_date = $this->_get_date_from_date($customer_execution_time_1,6);


			$sl_data=array(
				'customer_title'=>$name,
				'customer_auto'=>1,                     //添加者

				'customer_service_items' => $service_items,   //服务项目
				'customer_credit_rating' => $credit,   //信用等级

				'customer_waiter_1' => rand(1,12),          //商务对接人
				'customer_waiter_2' => rand(1,12),          //技术对接人
				'statement_of_settlement' => '转账',
				'account' => rand(1,2),
				'first_party_tel' => '13512585869',
				'signatory' => '甲方签署人',
				'company_id' => rand(39,83),

				'customer_status' => $status,                 //项目状态
				'customer_collection_period' => $customer_collection_period,       //回款周期

				'customer_contract_time' => $customer_contract_time,                  //签约时间
				'customer_execution_time' => $customer_execution_time,                  //执行时间
				'customer_expiration_date' => $customer_expiration_date,                  //到期时间

				'customer_cpprice' => $customer_cpprice,                         //签约总金额

				'customer_contract_cycle' => $customer_contract_cycle,             //合同周期


				'customer_payment_type' => $customer_payment_type,

				'customer_img'=>'',//封面图片路径
				'customer_open'=>input('customer_open',1),
				'customer_content'=>htmlspecialchars_decode($name),

				'customer_time'=>time(),
				'listorder'=>input('listorder',50,'intval'),

				'contract_number' => 'DUOLAN_'.$i,
			);


			//附加字段
			$customer_extra['showdate'] = time();
			$sl_data['customer_extra']=json_encode($customer_extra);

			customerModel::create($sl_data);
//			$continue=input('continue',0,'intval');

			//上一次插入数据
			$last_id = Db::name('customer')->getLastInsID();

			//存入交接人详情表        waiter_id    add_time
//			Db::name('customer_waiter_re')->insert(array('customer_id' => $last_id,'waiter_id' => $duijieren,'add_time' => time()));
			//回款类型处理：  过滤 99
			$this->do_payment_back($last_id,$customer_cpprice,$customer_payment_type,$customer_collection_period,$customer_contract_cycle,$customer_execution_time,$service_items,'add');
			$this->_payment_back_part($last_id);
		}
	}


	/**
	 * 针对项目，部分还款
	 *
	 * @param $customer_id
	 */
	private function _payment_back_part($customer_id)
	{
		$customer_payment_back_info = DB('customer_payment_back')->where('customer_id',$customer_id)->select();

		foreach($customer_payment_back_info as $row)
		{

			if($row['back_payment_time'] > time())
			{
				continue;
			}

			if(rand(1,7) > 4)
			{
				continue;
			}

			$update_data['back_payment_id'] = $row['back_payment_id'];
			$update_data['actual_amount_of_money'] = $row['amount_of_money'];
			$update_data['remark'] = '已经按期还款了';

			db('customer_payment_back')->update($update_data);
			$this->_actual_total_amount($update_data['back_payment_id']);   //更新总金额
		}
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

		//上传图片部分
		$img_one='';
		$file = request()->file('pic_one');
		if($file)
		{
			if(config('storage.storage_open')){
				//七牛
				$upload = \Qiniu::instance();
				$info = $upload->upload();
				$error = $upload->getError();
				if ($info)
				{
					if($file)
					{
						//单图
						$img_one= config('storage.domain').$info[0]['key'];
					}
				}else{
					$this->error($error,url('admin/customer/customer_list'));//否则就是上传错误，显示错误原因
				}
			}
			else
			{
				$validate = config('upload_validate');
				//单图
				if ($file)
				{
					$info = $file[0]->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . date('Y-m-d'));
					if ($info) {
						$img_url = config('upload_path'). '/' . date('Y-m-d') . '/' . $info->getFilename();
						//写入数据库
						$data['uptime'] = time();
						$data['filesize'] = $info->getSize();
						$data['path'] = $img_url;
						Db::name('plug_files')->insert($data);
						$img_one = $img_url;
					} else {
						$this->error($file->getError(), url('admin/customer/customer_list'));//否则就是上传错误，显示错误原因
					}
				}
			}
		}

		$sl_data=array(
			'customer_title'=>input('customer_title'),
			'customer_auto'=>session('admin_auth.member_id'),                     //添加者

			'customer_service_items' => input('customer_service_items'),   //服务项目
			'customer_credit_rating' => input('customer_credit_rating'),   //信用等级
			'customer_waiter' => input('customer_waiter'),                 //对接人
			'customer_status' => input('customer_status'),                 //项目状态
			'customer_collection_period' => input('customer_collection_period'),       //回款周期


			'customer_waiter' => input('customer_waiter'),                 //对接人
			'customer_status' => input('customer_status'),                 //项目状态

			'customer_contract_time' => strtotime(input('customer_contract_time')),                  //签约时间
			'customer_execution_time' => strtotime(input('customer_execution_time')),                  //执行时间
			'customer_expiration_date' => strtotime(input('customer_expiration_date')),                  //到期时间

			'customer_cpprice' => input('customer_cpprice'),                         //签约总金额

			'customer_contract_cycle' => input('customer_contract_cycle_id'),             //合同周期


			'customer_payment_type' => input('customer_payment_type_id'),

			'customer_img'=>$img_one,//封面图片路径
			'customer_open'=>input('customer_open',1),
			'customer_content'=>htmlspecialchars_decode(input('customer_content')),

			'customer_time'=>time(),
			'listorder'=>input('listorder',50,'intval'),
		);

		//附加字段
		$showtime=input('showdate','');
		$customer_extra['showdate']=($showtime=='')?time():strtotime($showtime);
		$sl_data['customer_extra']=json_encode($customer_extra);

		customerModel::create($sl_data);
		$continue=input('continue',0,'intval');


		//上一次插入数据
		$last_id = Db::name('customer')->getLastInsID();

		//存入交接人详情表        waiter_id    add_time
		Db::name('customer_waiter_re')->insert(array('customer_id' => $last_id,'waiter_id' => input('customer_waiter'),'add_time' => time()));

		//回款类型处理：  过滤 99
		$this->do_payment_back($last_id,input('customer_cpprice'),input('customer_payment_type_id'),input('customer_collection_period'),input('customer_contract_cycle_id'),input('customer_execution_time'),input('customer_service_items'),'add');

		if($continue)
		{
			$this->success('项目添加成功,继续发布',url('admin/customer/customer_add'));
		}
		else
		{
			$this->success('项目添加成功,返回列表页',url('admin/customer/customer_list'));
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
	private function do_payment_back($customer_id,$customer_cpprice,$payment_type_id,$customer_collection_period,$customer_contract_cycle_id,$customer_execution_time,$service_items,$param = 'add')
	{
		$come_month_num = $this->_get_come_month_num($customer_collection_period);               //回款周期  每期月数
		$cycle_month_num = $this->_get_cycle_month_num($customer_contract_cycle_id);              //合同周期  月数

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

//							$this->p($data,1);
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
	 * 根据执行日期，增加的月数，获得当期的回款时间
	 *
	 * @param $date
	 * @param $add_month
	 * @return int
	 */
	private function _get_date_from_date($date,$add_month)
	{
		if(!strpos($date,'-'))
		{
			$date = date('Y-m-d',$date);
		}

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
		}
		return $result;
	}



	/**
	 * 根据  back_payment_id   修改实收总金额
	 * @param int $back_payment_id
	 */
	private function _actual_total_amount($back_payment_id)
	{
		$customer_id = Db::name('customer_payment_back')->where(array('back_payment_id' => $back_payment_id))->column('customer_id');
		$actual_total_amount = Db::name('customer_payment_back')->where(array('customer_id' => $customer_id[0]))->sum('actual_amount_of_money');
		Db::name('customer_payment_back')->where(array('customer_id' => $customer_id[0]))->setField('actual_total_amount',$actual_total_amount);
	}

}