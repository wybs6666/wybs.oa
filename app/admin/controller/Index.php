<?php
namespace app\admin\controller;

use think\Db;
use think\Cache;
use think\log;
//use think\helper\Time;
//use app\admin\model\News as NewsModel;
//use app\admin\model\MemberList;


//use Hisune\EchartsPHP\ECharts;
//use \Hisune\EchartsPHP\Doc\IDE\Series;
//use Hisune\EchartsPHP\Doc\IDE\XAxis;
//use Hisune\EchartsPHP\Doc\IDE\YAxis;

class Index extends Base
{
	/**
	 * 服务项目 对接到 部门
	 *
	 * @param $service_item_id             服务项目id      一级部门
	 * @param $partment_id                 部门id
	 */
	private function _do_partment($service_item_id,$partment_id)
	{
		$child_service_item_id = Db::name('service_items')->where('pid',$service_item_id)->column('service_items_id');
		array_push($child_service_item_id,$service_item_id);
		$child_service_item_id =  implode(',',$child_service_item_id);

		$data = array(
			'from_department' => $partment_id
		);
		db('service_items')->where('service_items_id','in',$child_service_item_id)->setField($data);
		exit;
	}

	/**
     * 后台首页
     */
	public function index()
	{
		if($this->_get_admin_group_id() == 3)
		{
			return $this->fetch('index_2');
		}
		$cc = new \echart\Echart();
		$legend = array('技术部','营销部','电商部','品牌部');
		$payment_month_money = Db::name('customer_payment_back')->field('amount_of_money,service_items,back_payment_time,actual_amount_of_money')->order('back_payment_time')->select();

		foreach($payment_month_money as $key => $rows)
		{
			$rows['month'] = trim(date('Y-m',$rows['back_payment_time']));
			$xAxis[] = trim(date('Y-m',$rows['back_payment_time']));
			$payment_month_money[$key] = $rows;
		}

		if(isset($xAxis))
		{
			$xAxis = array_unique($xAxis);
		}
		//无数据
		else
		{
			return $this->fetch('index_2');
		}

//		设置颜色
		foreach($xAxis as $color)
		{
			$color_1[$color] = '#6f5fba';
			$color_2[$color] = '#5793f3';
			$color_3[$color] = '#d14a61';
			$color_4[$color] = '#675bba';
			$yAxis[] = array('unit' => "元");
		}

		$data_need_1 = $this->_get_serises1($payment_month_money,$xAxis,$legend,'amount_of_money');
		$data_need_2 = $this->_get_serises1($payment_month_money,$xAxis,$legend);

		$ceshi = $cc->common_style("pdo_22",$data_need_1['data_need'],"各部门签约金额月度统计","元");
		$ceshi_2 = $cc->common_style("pdo_11",$data_need_2['data_need'],"各部门回款金额月度统计","元");

		//统计table
		$this->_tj_table_alipay($data_need_1['data_need']);
		$aa = $this->_tj_table_alipay4($data_need_2['data_need']);

		if(!$aa)
		{
			return $this->fetch('index_2');
		}

//		当前月份：
		$now_time = date('Y-m',time());

//		根据日期和数据获得温度计图：
		$data_wdj = $this->_get_wdj_photo($now_time,$data_need_1,$data_need_2);
		$data_wdj_1 = $this->_get_serises_four($xAxis,$legend,'season');                    //季度回款情况1
		$data_wdj_2 = $this->_get_serises3($payment_month_money,$xAxis);                    //季度回款情况

		$cc = new \echart\Echart();
		$wo = $this->get_data1();

//		$this->p($wo,1);
		$pdo_1 = $cc->bzt_echart1('pdo_1',$wo);
		$pdo_2 = $cc->bzt_echart2('pdo_2',$wo);
		$pdo_3 = $cc->bzt_echart3('pdo_3',$wo);
		$this->_tj_table_pdo_1_2_3($wo);


		$this->assign('pdo_1',$pdo_1);
		$this->assign('pdo_2',$pdo_2);
		$this->assign('pdo_3',$pdo_3);
//exit;

		$pdo_33 = $cc->hkl_ylb('pdo_33',$data_wdj);
		$this->_tj_table_alipay1($data_wdj);

		//去掉  script 头尾标签
//		$find = array("<script>","</script>");
//		$replace = '';
//		$pdo_33 = str_replace($find,$replace,$pdo_33);

		$this->assign('pdo_11',$ceshi_2);
		$this->assign('pdo_22',$ceshi);
		$this->assign('pdo_33',$pdo_33);

		$this->assign('pdo_44',$cc->hkl_ylb('pdo_44',$data_wdj_1));

//		$this->p($data_wdj_1,1);
		$this->_tj_table_alipay2($data_wdj_1);

		$data_wdj_2 = $this->_tj_table_alipay3($data_wdj_2);
		$this->assign('pdo_55',$cc->hkl_ylb_all('pdo_55',$data_wdj_2));

		$data_wdj_3 = $this->_tj_company($data_wdj_2);

		$data_wdj_3 = $this->_tj_table_alipay8($data_wdj_3);
//		$this->p($data_wdj_3,1);
		$this->assign('pdo_66',$cc->hkl_ylb_all_1('pdo_66',$data_wdj_3));

		$this->_get_customer();
		$this->_get_service_item();
//		exit;
		$date_condition = $this->get_date();
		$back_payment=Db::name('customer_payment_back')
			->where('back_payment_time','between',"$date_condition[start],$date_condition[end]")
			->order('back_payment_time asc')->select();

		$this->assign('back_payment',$back_payment);

        return $this->fetch();
	}

	/**
	 * 三个饼图的数据（table）
	 *
	 * @param $we
	 */
	private function _tj_table_pdo_1_2_3($we)
	{
//		$this->p($we,1);
		foreach($we['series'] as $row)
		{
//			$data_unit = $row['data'];
//			echo $data_unit.'<br>';
		}
//		exit;
	}

	private function _get_select_info()
	{
		$company_info = Db::name('company')->column('company_id,company_name');
		$service_items_info = Db::name('customer_payment_back')->select('back_payment_id,service_items');

		$this->assign('company_info',$company_info);
		$this->assign('service_items_info',$service_items_info);
	}

	/**
	 * table统计  alipay8
	 *
	 * @param $data
	 */
	private function _tj_table_alipay8($data)
	{
		$sum = explode(',',$data['data'][0]);
		$num = explode(',',$data['data'][1]);
		$percent = explode(',',$data['data'][2]);

		foreach($sum as $key=>$val)
		{
			$num_2[$key] = $val - $num[$key];            //欠款
		}

		$this->assign('alipay8_sum',$sum);
		$this->assign('alipay8_num',$num);
		$this->assign('alipay8_percent',$percent);

		$data['data'][3] = $data['data'][2];
		$data['data'][2] = implode(',',$num_2);

		$data['name'][3] = $data['name'][2];
		$data['name'][2] = '欠款金额';

		return $data;
	}

	/**
	 * table统计  alipay8
	 *
	 * @param $data
	 */
	private function _tj_table_alipay3($data)
	{
		$sum = explode(',',$data['data'][0]);
		$num = explode(',',$data['data'][1]);
		$percent = explode(',',$data['data'][2]);

		foreach($sum as $key=>$val)
		{
			$num_2[$key] = $val - $num[$key];            //欠款
		}

		$month = $data['xAxis'];
		$month = str_replace("'",'',$month);
		$month = explode(',',$month);
		$this->assign('alipay3_sum',$this->do_array($sum));
		$this->assign('alipay3_num',$this->do_array($num));
		$this->assign('alipay3_num_2',$this->do_array($num_2));
		$this->assign('alipay3_percent',$this->do_array($percent));
		$this->assign('alipay3_month',$this->do_array($month));


//		$this->assign('alipay3_sum',$sum);
//		$this->assign('alipay3_num',$num);
//		$this->assign('alipay3_num_2',$num_2);
//		$this->assign('alipay3_percent',$percent);
//		$this->assign('alipay3_month',$month);

		$data['data'][3] = $data['data'][2];
		$data['data'][2] = implode(',',$num_2);

		$data['name'][3] = $data['name'][2];
		$data['name'][2] = '欠款金额';

		return $data;
	}

	/**
	 * 公司回款情况图
	 *
	 * @param $data
	 * @return array
	 */
	private function _tj_company($data)
	{
		$now_month = date('Y-m',time());

		//月份
		$month_str = $data['xAxis'];
		$month_str = str_replace("'",'',$month_str);
		$month_arr = explode(",",$month_str);

//		签约
		$month_str = $data['data'][0];
		$month_str = str_replace("'",'',$month_str);
		$data_1 = explode(",",$month_str);

//		回款
		$month_str = $data['data'][1];
		$month_str = str_replace("'",'',$month_str);
		$data_2 = explode(",",$month_str);

		//当月
		foreach($month_arr as $key=>$val)
		{
			if($now_month == $val)
			{
				$month_key = $key;
				$now_month_data_1 = $data_1[$key];
				$now_month_data_2 = $data_2[$key];


				if($now_month_data_1 === 0)
				{
					$now_month_data_3 = 0;continue;
				}
				$now_month_data_3 = number_format($now_month_data_2/$now_month_data_1,4)*100;
			}
		}

		if(isset($month_key))
		{
			//近三月
			$three_month_key = $this->_key_get_key($month_key,3);
			$three_month_data_1 = '';
			$three_month_data_2 = '';
			foreach($three_month_key as $three_key)
			{
				$three_month_data_1 += $data_1[$three_key];
				$three_month_data_2 += $data_2[$three_key];
			}
			$three_month_data_3 = number_format($three_month_data_2/$three_month_data_1,4)*100;

			//近六个月
			$six_month_key = $this->_key_get_key($month_key,6);
			$six_month_data_1 = '';
			$six_month_data_2 = '';
			foreach($six_month_key as $six_key)
			{
				$six_month_data_1 += $data_1[$six_key];
				$six_month_data_2 += $data_2[$six_key];
			}
			$six_month_data_3 = number_format($six_month_data_2/$six_month_data_1,4)*100;
		}
		else
		{
//			当月
			$now_month_data_1 = 0;
			$now_month_data_2 = 0;
			$now_month_data_3 = 0;

//			近三月
			$three_month_data_1 = 0;
			$three_month_data_2 = 0;
			$three_month_data_3 = 0;

//			近六月
			$six_month_data_1 = 0;
			$six_month_data_2 = 0;
			$six_month_data_3 = 0;
		}

		//全部
		$all_data_1 = '';
		$all_data_2 = '';
		foreach($month_arr as $month_unit => $val11)
		{
			$all_data_1 += $data_1[$month_unit];
			$all_data_2 += $data_2[$month_unit];
		}
		$all_data_3 = number_format($all_data_2/$all_data_1,4)*100;

		$need_data = array(
			'text' => '公司回款统计图',
			'xAxis' => "'当月','近三月','近六月','全部'",
			'data' => array(
				$now_month_data_1.','.$three_month_data_1.','.$six_month_data_1.','.$all_data_1,
				$now_month_data_2.','.$three_month_data_2.','.$six_month_data_2.','.$all_data_2,
				$now_month_data_3.','.$three_month_data_3.','.$six_month_data_3.','.$all_data_3,
			),
			'name' => array(
				'签约金额','回款金额','回款率',
			),
		);
		return $need_data;
	}

	/**
	 * 根据键获得适合的键
	 *
	 * @param $key
	 * @param $param
	 * @return array
	 */
	private function _key_get_key($key,$param = 3)
	{
		switch($param)
		{
			case 3:
				if($key>1)
				{
					$result = array($key-2,$key-1,$key);
				}
				elseif($key == 1)
				{
					$result = array(0,1);
				}
				else
				{
					$result = array(0);
				}
				break;
			case 6:
				if($key>4)
				{
					$result = array($key-5,$key-4,$key-3,$key-2,$key-1,$key);
				}
				elseif($key == 0)
				{
					$result = array(0);
				}
				elseif($key == 1)
				{
					$result = array(0,1);
				}
				elseif($key == 2)
				{
					$result = array(0,1,2);
				}
				elseif($key == 3)
				{
					$result = array(0,1,2,3);
				}
				elseif($key == 4)
				{
					$result = array(0,1,2,3,4);
				}
				break;
		}
		return $result;
	}

	/**
	 * table  统计
	 * @param $data
	 */
	private function _tj_table_alipay2($data)
	{
		$alipay2_1 = explode(',',$data['data'][0]);
		$alipay2_2 = explode(',',$data['data'][1]);
		$alipay2_3 = explode(',',$data['data'][3]);

		$alipay2_4 = array(
			array_sum($alipay2_1),
			array_sum($alipay2_2),
		);
		if($alipay2_4[0] == 0)
		{
			$alipay2_4[2] = 0;
		}
		else
		{
			$alipay2_4[2] = number_format($alipay2_4[1]/$alipay2_4[0],4)*100;
		}

		$this->assign('alipay2_1',$this->do_array($alipay2_1));     //签约金额
		$this->assign('alipay2_2',$this->do_array($alipay2_2));     //回款金额
		$this->assign('alipay2_3',$this->do_array($alipay2_3));     //回款率
		$this->assign('alipay2_4',$this->do_array($alipay2_4));     //回款率

		$month = str_replace("'",'',$data['xAxis']);

		$month_need = explode(',',$month);

		$this->assign('alipay2_month',$this->do_array($month_need));
	}


	public function do_array($arr)
	{
		if(is_array($arr))
		{
			$leng = count($arr);

			for($i = 0;$i < $leng;$i++)
			{
				$need[$i] = $arr[$leng-1-$i];
			}
		}

		return $need;
	}

	/**
	 * table  统计
	 * @param $data
	 */
	private function _tj_table_alipay1($data)
	{
		$alipay1_1 = explode(',',$data['data'][0]);
		$alipay1_2 = explode(',',$data['data'][1]);
		$alipay1_3 = explode(',',$data['data'][3]);

		$this->assign('alipay1_1',$alipay1_1);
		$this->assign('alipay1_2',$alipay1_2);
		$this->assign('alipay1_3',$alipay1_3);
	}

	/**
	 * 各部门签约金额月度统计表（table数据）
	 *
	 * @param $data
	 */
	private function _tj_table_alipay($data)
	{
		//技术部：
		$data_str_1 = $data['series'][0]['data'];
		$data_str_1 = str_replace("'",'',$data_str_1);
		$alipay_num_1 =  array_sum(explode(',',$data_str_1));

		//营销部：
		$data_str_2 = $data['series'][1]['data'];
		$data_str_2 = str_replace("'",'',$data_str_2);
		$alipay_num_2 =  array_sum(explode(',',$data_str_2));

		//电商部：
		$data_str_3 = $data['series'][2]['data'];
		$data_str_3 = str_replace("'",'',$data_str_3);
		$alipay_num_3 =  array_sum(explode(',',$data_str_3));

		//品牌部：
		$data_str_4 = $data['series'][3]['data'];
		$data_str_4 = str_replace("'",'',$data_str_4);
//		$this->p($data_str_4,1);
		$alipay_num_4 =  array_sum(explode(',',$data_str_4));

		$alipay_num_sum = $alipay_num_1+$alipay_num_2+$alipay_num_3+$alipay_num_4;

		//占比
		$alipay_num_11 = number_format($alipay_num_1/$alipay_num_sum,4)*100;
		$alipay_num_22 = number_format($alipay_num_2/$alipay_num_sum,4)*100;
		$alipay_num_33 = number_format($alipay_num_3/$alipay_num_sum,4)*100;
		$alipay_num_44 = number_format($alipay_num_4/$alipay_num_sum,4)*100;

		$this->assign('alipay_num_1',$alipay_num_1);
		$this->assign('alipay_num_2',$alipay_num_2);
		$this->assign('alipay_num_3',$alipay_num_3);
		$this->assign('alipay_num_4',$alipay_num_4);

		$this->assign('alipay_num_11',$alipay_num_11);
		$this->assign('alipay_num_22',$alipay_num_22);
		$this->assign('alipay_num_33',$alipay_num_33);
		$this->assign('alipay_num_44',$alipay_num_44);

		$this->assign('alipay_num_sum',$alipay_num_sum);
	}


	/**
	 * 各部门已回款金额月度统计表（table数据）
	 *
	 * @param $data
	 * @return bool
	 */
	private function _tj_table_alipay4($data)
	{
		//技术部：
		$data_str_1 = $data['series'][0]['data'];
		$data_str_1 = str_replace("'",'',$data_str_1);
		$alipay4_num_1 =  array_sum(explode(',',$data_str_1));

		//营销部：
		$data_str_2 = $data['series'][1]['data'];
		$data_str_2 = str_replace("'",'',$data_str_2);
		$alipay4_num_2 =  array_sum(explode(',',$data_str_2));

		//电商部：
		$data_str_3 = $data['series'][2]['data'];
		$data_str_3 = str_replace("'",'',$data_str_3);
		$alipay4_num_3 =  array_sum(explode(',',$data_str_3));

		//品牌部：
		$data_str_4 = $data['series'][3]['data'];
		$data_str_4 = str_replace("'",'',$data_str_4);
		$alipay4_num_4 =  array_sum(explode(',',$data_str_4));

		$alipay4_num_sum = $alipay4_num_1+$alipay4_num_2+$alipay4_num_3+$alipay4_num_4;


		if(!$alipay4_num_4)
		{
			return false;
		}


		//占比
		$alipay4_num_11 = number_format($alipay4_num_1/$alipay4_num_sum,4)*100;
		$alipay4_num_22 = number_format($alipay4_num_2/$alipay4_num_sum,4)*100;
		$alipay4_num_33 = number_format($alipay4_num_3/$alipay4_num_sum,4)*100;
		$alipay4_num_44 = number_format($alipay4_num_4/$alipay4_num_sum,4)*100;

		$this->assign('alipay4_num_1',$alipay4_num_1);
		$this->assign('alipay4_num_2',$alipay4_num_2);
		$this->assign('alipay4_num_3',$alipay4_num_3);
		$this->assign('alipay4_num_4',$alipay4_num_4);

		$this->assign('alipay4_num_11',$alipay4_num_11);
		$this->assign('alipay4_num_22',$alipay4_num_22);
		$this->assign('alipay4_num_33',$alipay4_num_33);
		$this->assign('alipay4_num_44',$alipay4_num_44);

		$this->assign('alipay4_num_sum',$alipay4_num_sum);

		return true;
	}


	/**
	 * 获得温度计图所需数据
	 *
	 * @param $now_time
	 * @param $data_need_1
	 * @param $data_need_2
	 * @return mixed
	 */
	private function _get_wdj_photo($now_time,$data_need_1,$data_need_2)
	{
		$now_data_1 = array_column($data_need_1['data'],$now_time);

		$data_need_1_size = count($data_need_1['data']);
		if(!$now_data_1)
		{
			for($i = 0;$i < $data_need_1_size;$i++)
			{
				$now_data_1[$i] = 0;
			}
		}


		$now_data_2 = array_column($data_need_2['data'],$now_time);
		$data_need_2_size = count($data_need_2['data']);;
		if(!$now_data_2)
		{
			for($i = 0;$i < $data_need_2_size;$i++)
			{
				$now_data_2[$i] = 0;
			}
		}

		foreach($now_data_2 as $key => $val)
		{
			if($val === 0)
			{
				$now_data_3[$key] = 0;continue;
			}

			if(trim($now_data_1[$key]))
			{
				$now_data_3[$key] = round($now_data_2[$key]/$now_data_1[$key]*100,2);
			}
			else
			{
				$now_data_3[$key] = 0;
			}


			$now_data_4[$key] = $now_data_1[$key] - $now_data_2[$key];
		}

		$data_wdj['text'] = '本月回款详情('.$now_time.')';
		$data_wdj['xAxis'] = "'技术部','营销部','电商部','品牌部'";
		$data_wdj['data'] = array(
			implode(',',$now_data_1),
			implode(',',$now_data_2),
			implode(',',$now_data_4),
			implode(',',$now_data_3),
		);
		$data_wdj['name'] = ['签约金额','回款金额','欠款金额','回款率'];
		return $data_wdj;
	}


	/**
	 * 获得集合数据
	 *
	 * @param $payment_month_money
	 * @param $xAxis
	 * @param $legend
	 * @param string $param
	 * @return array
	 */
	private function _get_serises1($payment_month_money,$xAxis,$legend,$param = 'actual_amount_of_money')
	{
		$data_1 = array();

		//初始化数据
		foreach($xAxis as $row)
		{
			$data_1[$row] = 0;
			$data_2[$row] = 0;
			$data_3[$row] = 0;
			$data_4[$row] = 0;
		}
		//服务项目 和 部门的关系
		$service_items_from_department = $this->_service_items_from_department();

		foreach($payment_month_money as $val)
		{
			$from_department = $service_items_from_department[$val['service_items']];
			switch($from_department)
			{
				//技术部
				case 1:
					foreach($xAxis as $month)
					{
						//签约金额
						if(!isset($data_1[$month]))
						{
							$data_1[$month] = 0;
						}
						if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
						{
							$data_1[$month] += $val[$param];
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
						if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
						{
							$data_2[$month] += $val[$param];
						}
					}
					break;

				//电商部
				case 3:
					foreach($xAxis as $month)
					{
						if(!isset($data_3[$month]))
						{
							$data_3[$month] = 0;
						}
						if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
						{
							$data_3[$month] += $val[$param];
						}
					}
					break;

				//品牌部
				case 4:
					foreach($xAxis as $month)
					{
						if(!isset($data_4[$month]))
						{
							$data_4[$month] = 0;
						}
						if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
						{
							$data_4[$month] += $val[$param];
						}
					}
					break;
			}
		}

//		设置颜色
		foreach($xAxis as $color)
		{
			$color_1[$color] = '#6f5fba';
			$color_2[$color] = '#5793f3';
			$color_3[$color] = '#d14a61';
			$color_4[$color] = '#675bba';
			$yAxis[] = array('unit' => "元");
		}

		$series_1 = array(
			'name' => '技术部',
			'type' => "bar",
			'data' => $this->_1arr_str($data_1),
			'color' => $this->_1arr_str($color_1),
		);

		$series_2 = array(
			'name' => '营销部',
			'type' => "bar",
			'data' => $this->_1arr_str($data_2),
			'color' => $this->_1arr_str($color_2),
		);

		$series_3 = array(
			'name' => '电商部',
			'type' => "bar",
			'data' => $this->_1arr_str($data_3),
			'color' => $this->_1arr_str($color_3),
		);

		$series_4 = array(
			'name' => '品牌部',
			'type' => "bar",
			'data' => $this->_1arr_str($data_4),
			'color' => $this->_1arr_str($color_4),
		);



		$data_need_1 = array(
			'legend' => $this->_1arr_str($legend),
			'xAxis' => $this->_1arr_str($xAxis),
			'yAxis' => $yAxis,
			'series' => array($series_1,$series_2,$series_3,$series_4),
		);


		return array('data_need' =>$data_need_1,'data' => array($data_1,$data_2,$data_3,$data_4));

//		$ceshi = $cc->common_style("pdo_22",$data_need_1,"各部门《签约金额》月度统计表","元");
	}

	/**
	 * 服务项目 和 部门的关系
	 *
	 * @return mixed
	 */
	private function _service_items_from_department()
	{
		$service_items_from_department = DB('service_items')->column('service_items_id,from_department,pid');
		foreach($service_items_from_department as $row)
		{
			//当为顶级时
			if($row['pid'] == 0)
			{
				$need_data[$row['service_items_id']] = $row['from_department'];
			}
			else
			{
				$need_data[$row['service_items_id']] = $need_data[$row['pid']];
			}
		}
		return $need_data;
	}



	/**
	 * 获得集合数据   （近三月）
	 *
	 * @param $payment_month_money
	 * @param $xAxis
	 * @param $legend
	 * @param string $param
	 * @return array
	 */
	private function _get_serises2($xAxis1,$legend,$param = 'season')
	{
		//获取本季 时间轴
		$now_year = date('Y');
		$now_month = date('m');
		if(in_array($now_month,array('1','2')))
		{
			$start_1 = ($now_year-1).'-'.($now_month + 12 -2);
			$end_1 = $now_year.'-'.($now_month+1);
		}
		else
		{
			$start_1 = ($now_year).'-'.($now_month - 2);
			$end_1 = $now_year.'-'.($now_month+1);
		}
		$start = strtotime($start_1);
		$end = strtotime($end_1);


		//本季
		if($param == 'season')
		{
			$payment_month_money = Db::name('customer_payment_back')->
			where('back_payment_time','between',"$start,$end")->
			field('amount_of_money,back_payment_time,actual_amount_of_money')->order('back_payment_time')->select();
		}
//echo Db::name('customer_payment_back')->getLastSql();exit;
//		$this->p($payment_month_money,1);
		foreach($payment_month_money as $key => $rows)
		{
			$rows['month'] = trim(date('Y-m',$rows['back_payment_time']));
			$xAxis[] = trim(date('Y-m',$rows['back_payment_time']));
			$payment_month_money[$key] = $rows;
		}


		//获得x轴
		if(isset($xAxis))
		{
			$xAxis = array_unique($xAxis);
		}

		$data_1 = array();
		$data_2 = array();
//		$this->p($xAxis);
		foreach($payment_month_money as $val)
		{
			foreach($xAxis as $month)
			{
				//回款金额
				if(!isset($data_1[$month]))
				{
					$data_1[$month] = 0;
				}

				//签约金额
				if(!isset($data_2[$month]))
				{
					$data_2[$month] = 0;
				}

				if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
				{
					$data_1[$month] += $val['actual_amount_of_money'];
					$data_2[$month] += $val['amount_of_money'];
				}
			}
		}

		$data_3 = array();
		foreach($data_2 as $key => $row)
		{
			$data_3[$key] = round($data_1[$key]/$data_2[$key]*100,2);
			$data_1[$key] = round($data_1[$key],2);
			$data_2[$key] = round($data_2[$key],2);
		}

//		$this->p($data_3);     //回款率
//		$this->p($data_2);     //签约金额
//		$this->p($data_1);     //回款金额

		$name = ['签约金额','回款金额','回款率'];
		$data = [
			implode(',',$data_2),
			implode(',',$data_1),
			implode(',',$data_3),
		];
		$xAxis = "'".implode("','",$xAxis)."'";

		$text = '近四月回款详情('.$start_1.'-'.$end_1.')';
		return array(
			'text' => $text,
			'xAxis' => $xAxis,
			'data' => $data,
			'name' => $name,
		);
	}

	/**
	 * 获得集合数据   （近四月）
	 *
	 * @param $xAxis1
	 * @param $legend
	 * @param string $param
	 * @return array
	 */
	private function _get_serises_four($xAxis1,$legend,$param = 'season')
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
			$start_1 = $now_year.'-9';
			$end_1 = ($now_year + 1).'-1';
		}
		else
		{
			$start_1 = ($now_year).'-'.($now_month - 3);
			$end_1 = $now_year.'-'.($now_month+1);
		}
		$start = strtotime($start_1);
		$end = strtotime($end_1) - 3600*24;

		//本季
		if($param == 'season')
		{
			$payment_month_money = Db::name('customer_payment_back')->
			where('back_payment_time','between',"$start,$end")->
			field('amount_of_money,back_payment_time,actual_amount_of_money')->order('back_payment_time')->select();
		}
		foreach($payment_month_money as $key => $rows)
		{
			$rows['month'] = trim(date('Y-m',$rows['back_payment_time']));
			$xAxis[] = trim(date('Y-m',$rows['back_payment_time']));
			$payment_month_money[$key] = $rows;
		}


		//获得x轴
		if(isset($xAxis))
		{
			$xAxis = array_unique($xAxis);
		}
		else
		{
			$xAxis = null;
		}




		$data_1 = array();
		$data_2 = array();
		foreach($payment_month_money as $val)
		{
			foreach($xAxis as $month)
			{
				//回款金额
				if(!isset($data_1[$month]))
				{
					$data_1[$month] = 0;
				}

				//签约金额
				if(!isset($data_2[$month]))
				{
					$data_2[$month] = 0;
				}

				if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
				{
					$data_1[$month] += $val['actual_amount_of_money'];
					$data_2[$month] += $val['amount_of_money'];
				}
			}
		}

		$data_3 = array();
		$data_4 = array();
		foreach($data_2 as $key => $row)
		{
			$data_3[$key] = round($data_1[$key]/$data_2[$key]*100,2);
			$data_1[$key] = round($data_1[$key],2);
			$data_2[$key] = round($data_2[$key],2);
			$data_4[$key] = round($data_2[$key] - $data_1[$key],2);
		}

//		$this->p($data_3);     //回款率
//		$this->p($data_2);     //签约金额
//		$this->p($data_1);     //回款金额

		$name = ['签约金额','回款金额','欠款金额','回款率'];
		$data = [
			implode(',',$data_2),
			implode(',',$data_1),
			implode(',',$data_4),
			implode(',',$data_3),
		];

		if($xAxis)
		{
			$xAxis = "'".implode("','",$xAxis)."'";
		}


		$text = '近四月回款详情('.$start_1.'-'.$end_1.')';
		return array(
			'text' => $text,
			'xAxis' => $xAxis,
			'data' => $data,
			'name' => $name,
		);
	}


	/**
	 * 获得集合数据   （全部）
	 *
	 * @param $payment_month_money
	 * @param $xAxis
	 * @return array
	 */
	private function _get_serises3($payment_month_money,$xAxis)
	{
		$data_1 = array();
		$data_2 = array();
		foreach($payment_month_money as $val)
		{
			foreach($xAxis as $month)
			{
				//回款金额
				if(!isset($data_1[$month]))
				{
					$data_1[$month] = 0;
				}

				//签约金额
				if(!isset($data_2[$month]))
				{
					$data_2[$month] = 0;
				}

				if(trim(date('Y-m',$val['back_payment_time'])) == trim($month))
				{
					$data_1[$month] += $val['actual_amount_of_money'];
					$data_2[$month] += $val['amount_of_money'];
				}
			}
		}
//$this->p($data_2,1);
		$data_3 = array();
		foreach($data_2 as $key => $row)
		{
			if($data_2[$key] == 0)
			{
				$data_3[$key] = 0;
			}
			else
			{
				$data_3[$key] = round($data_1[$key]/$data_2[$key]*100,2);
			}
			$data_1[$key] = round($data_1[$key],2);
			$data_2[$key] = round($data_2[$key],2);
		}
//		$this->p($data_3);     //回款率
//		$this->p($data_2);     //签约金额
//		$this->p($data_1);     //回款金额

		$name = ['签约金额','回款金额','回款率'];
		$data = [
			implode(',',$data_2),
			implode(',',$data_1),
			implode(',',$data_3),
		];
		$xAxis = "'".implode("','",$xAxis)."'";
		$text = '全部回款详情';

		return array(
			'text' => $text,
			'xAxis' => $xAxis,
			'data' => $data,
			'name' => $name,
		);
	}


	private function get_data1()
	{
		$data_default = array(
			"legend" => "'海温距平','线性化趋势','低频滤波值'",
			"xAxis" => "'1997','1998','1999','2000','2001','2002','2003','2004','2005','2006','2007','2008','2009','2010','2011','2012','2013','2014','2015','2016'",
			"yAxis" => array(
			),
			"series" => array(
				array( 'name' => '海温距平','type' => 'symbol_line_top','data' => "'0.0538558','0.323896','0.131661','0.0171606','0.171256','0.0643853','0.274381','0.242218','0.444207','0.425001','0.298802','0.370703','0.152229','0.500467','0.244916','0.374845','0.34475','0.292895','0.246639',''",'color' => "'#000000','#000000','#ff0000','#ff0000','#0000ff','#ff0000','#ff0000','#0000ff','#ff0000','#ff0000','#ff0000','#0000ff',"),
				array( 'name' => '线性化趋势','type' => 'bar','data' => "'0.151554','0.163804','0.176053','0.188303','0.200553','0.212803','0.225053','0.237303','0.249553','0.261803','0.274053','0.286303','0.298553','0.310803','0.323053','0.335303','0.347553','0.359803','0.372053','0.384303'",'color' => "'#000000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0FF0','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000',"),
				array( 'name' => '低频滤波值','type' => 'line',

					'data' => "'0.200652','0.198248','0.15279','0.090166','0.100324','0.150511','0.208561','0.305861','0.384085','0.39592','0.352646','0.291778','0.306264','0.336354','0.349691','0.33135','0.338835','0.294416','',''",

					'color' => "'#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000','#ff0000',"),
			),
		);

		$legend = "'项目量','签约金额总数','实付款总数'";

		$service_items = Db::name('service_items')->column('service_items_id,service_items_name');
		$xAxis = "'".implode("','",$service_items)."'";

		//项目量： $customer_volume          $total_contract_amount              $total_amount paid          #C1232B','#B5C334','#FCCE10
		foreach($service_items as $key => $val)
		{
			$customer_volume[] = Db::name('customer')->where(array('customer_service_items' => $key))->count();
			$total_contract_amount[] = Db::name('customer')->where(array('customer_service_items' => $key))->sum('customer_cpprice');
			$total_amount[] = Db::name('customer_payment_back')->where(array('service_items' => $key))->sum('actual_total_amount');


			$pie_need[] = $val;
			$color_1[] = '#C1232B';
			$color_2[] = '#B5C334';
			$color_3[] = '#FCCE10';
		}

		$customer_volume_data = "'".implode("','",$customer_volume)."'";
		$total_contract_amount_data = "'".implode("','",$total_contract_amount)."'";
		$total_amount_data = "'".implode("','",$total_amount)."'";
		$color_str_1 = "'".implode("','",$color_1)."'";
		$color_str_2 = "'".implode("','",$color_2)."'";
		$color_str_3 = "'".implode("','",$color_3)."'";

//		签约金额总数

		$series = [
			['name' => '项目量','type' => 'symbol_line_top','data' => $customer_volume_data,'color' => $color_str_1],
			['name' => '签约金额总数','type' => 'bar','data' => $total_contract_amount_data,'color' => $color_str_2],
			['name' => '实付款总数','type' => 'line','data' => $total_amount_data,'color' => $color_str_3]
		];
		$data_need = array(
			"legend" => $legend,
			"xAxis" => $xAxis,
			"yAxis" => array(
			),
			"series" => $series,


			'customer_volume' => $customer_volume,
			'total_contract_amount' => $total_contract_amount,
			'total_amount' => $total_amount,
			'pie_need' => $pie_need,
		);
//	$this->p($data_default);
//	$this->p($data_need,1);
		return $data_need;
	}


	/**
	 * 分部门获得图表信息
	 *
	 * 技术部     seo  aso    网建开发    1,4,5
	营销        sem                       2
	电商                                  7
	品牌部      新媒体运营                6
	 *
	 *
	 *  7	电商

	6	新媒体运营

	5	网建开发

	4	aso

	2	sem

	1	seo
	 *
	 * @return array
	 */
	private function get_data2()
	{

		$legend = "'项目量','签约金额总数','实付款总数'";

		$xAxis = "'技术部','营销部','电商部','品牌部'";


		$pie_need[0] = '技术部';$pie_need[1] = '营销部';$pie_need[2] = '电商部';$pie_need[3] = '品牌部';
		$color_1 = array('#C1232B','#C1232B','#C1232B','#C1232B');
		$color_2 = array('#B5C334','#B5C334','#B5C334','#B5C334');
		$color_3 = array('#FCCE10','#FCCE10','#FCCE10','#FCCE10');

		//技术部                where('id','in','1,3,8');
		$customer_volume[0] = Db::name('customer')->where('customer_service_items','in','1,4,5')->count();
		$total_contract_amount[0] = Db::name('customer')->where('customer_service_items','in','1,4,5')->sum('customer_cpprice');
		$total_amount[0] = Db::name('customer_payment_back')->where('service_items','in','1,4,5')->sum('actual_total_amount');


		//营销部                where(array('id' => 2));
		$customer_volume[1] = Db::name('customer')->where(array('customer_service_items' => 2))->count();
		$total_contract_amount[1] = Db::name('customer')->where(array('customer_service_items' => 2))->sum('customer_cpprice');
		$total_amount[1] = Db::name('customer_payment_back')->where(array('service_items' => 2))->sum('actual_total_amount');


		//电商部                where(array('id' => 7));
		$customer_volume[2] = Db::name('customer')->where(array('customer_service_items' => 7))->count();
		$total_contract_amount[2] = Db::name('customer')->where(array('customer_service_items' => 7))->sum('customer_cpprice');
		$total_amount[2] = Db::name('customer_payment_back')->where(array('service_items' => 7))->sum('actual_total_amount');


		//品牌部               where(array('id' => 6));
		$customer_volume[3] = Db::name('customer')->where(array('customer_service_items' => 6))->count();
		$total_contract_amount[3] = Db::name('customer')->where(array('customer_service_items' => 6))->sum('customer_cpprice');
		$total_amount[3] = Db::name('customer_payment_back')->where(array('service_items' => 6))->sum('actual_total_amount');


		//总计：
		$customer_volume_total = Db::name('customer')->count();
		$total_contract_amount_total = Db::name('customer')->sum('customer_cpprice');
		$total_amount_total = Db::name('customer_payment_back')->sum('actual_total_amount');

//		$this->p($customer_volume_total,1);

		$customer_volume_data = "'".implode("','",$customer_volume)."'";
		$total_contract_amount_data = "'".implode("','",$total_contract_amount)."'";
		$total_amount_data = "'".implode("','",$total_amount)."'";
		$color_str_1 = "'".implode("','",$color_1)."'";
		$color_str_2 = "'".implode("','",$color_2)."'";
		$color_str_3 = "'".implode("','",$color_3)."'";

//		签约金额总数

		$series = [
			['name' => '项目量','type' => 'symbol_line_top','data' => $customer_volume_data,'color' => $color_str_1],
			['name' => '签约金额总数','type' => 'bar','data' => $total_contract_amount_data,'color' => $color_str_2],
			['name' => '实付款总数','type' => 'line','data' => $total_amount_data,'color' => $color_str_3]
		];
		$data_need = array(
			"legend" => $legend,
			"xAxis" => $xAxis,
			"yAxis" => array(
			),
			"series" => $series,


			'customer_volume' => $customer_volume,
			'total_contract_amount' => $total_contract_amount,
			'total_amount' => $total_amount,
			'pie_need' => $pie_need,
		);
//	$this->p($data_default);
//	$this->p($data_need,1);
		return $data_need;
	}


    /**
     * 后台多语言切换
     */
	public function lang()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确');
		}else{
			$lang=input('lang_s');
			session('login_http_referer',$_SERVER["HTTP_REFERER"]);
			switch ($lang) {
				case 'cn':
					cookie('think_var', 'zh-cn');
				break;
				case 'en':
					cookie('think_var', 'en-us');
				break;
				//其它语言
				default:
					cookie('think_var', 'zh-cn');
			}
			Cache::clear();
			$this->success('切换成功',session('login_http_referer'));
		}
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

		if($customer)
		{
			$customer = Db::name('customer')->column('n_id,customer_collection_period');
			foreach($customer as $key => $val)
			{
				switch($val)
				{
					case '11':
						$per_payment[$key] = '/月';
						break;
					case '10':
						$per_payment[$key] = '/二年';
						break;
					case '11':
						$per_payment[$key] = '/月';
						break;
					case '8':
						$per_payment[$key] = '/年';
						break;
					case '7':
						$per_payment[$key] = '/半年';
						break;
					case '6':
						$per_payment[$key] = '/季';
						break;
					case '4':
						$per_payment[$key] = '/一次付清';
						break;
				}
			}
		}
		else
		{
			$per_payment = array();
		}

		$this->assign('per_payment',$per_payment);
		return $per_payment;
	}


	/**
	 * 根据 id获得 获得项目类型
	 *
	 * @param $customer_id
	 * @return mixed
	 */
	private function _get_service_item()
	{
		$customer = Db::name('customer')->column('n_id,customer_service_items');
		$this->assign('customer_info_service_items',$customer);

		$service_items = Db::name('service_items')->column('service_items_id,service_items_name');
		$this->assign('service_items_arr',$service_items);
	}


//

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



	/**
	 * 获取当月时间跨度   时间戳跨度，一个月的跨度
	 *
	 * @return array
	 */
	private function get_date()
	{
		$aa = date('Y-m');
		$bb = explode('-',$aa);
		if($bb[1] != 12)
		{
			$cc = $bb[0].'-'.($bb[1]+1);
		}
		else
		{
			$cc = ($bb[0]+1).'-1';
		}
		return ['start' => strtotime($aa),'end' => (strtotime($cc) - 3600*24)];
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
}