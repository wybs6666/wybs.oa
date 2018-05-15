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

class Import extends Base
{
	const IMPORT_TABLE = 'import_table.txt';

	/**
	 * http://tp.demo/admin/import/company/index
	 * http://tp.demo/admin/import/company/company
	 * http://tp.demo/admin/import/industry_category
	 *
	 * http://tp.demo/admin/import/customer_status
	 * http://tp.demo/admin/import/filter_waiter

	 */

	/**
	 * 管理员列表
	 */
	public function index()
	{
		exit;
//		echo 'aaa';exit;
		$arr = file(self::IMPORT_TABLE);
		foreach($arr as $val)
		{
			$need_data = explode('$$',$val);

			$import_data = array(
			'company_code' => $need_data[0],
			'company_name' => $need_data[1],
			'industry_category' => $need_data[2],
			'service_items' => $need_data[3],
			'customer_status' => $need_data[4],
			'customer_waiter_1' => $need_data[5],
			'service_items' => $need_data[6],
			);

//			$this->p($import_data,1);
			Db::name('import_table')->insert($import_data);
		}

		$this->p($arr,1);
	}

	/**
	 * 录入公司信息
	 */
	public function company()
	{
exit;
		Db::name('company')->where('company_id','<','39')->delete();
		exit;
		$company_data = Db::name('import_table')->select();
//		$this->p($company_data,1);

		foreach($company_data as $row)
		{
			if(!trim($row['company_name']))
			{
				continue;
			}
			else
			{
				$industry_category_id = db('industry_category')->where(array('industry_category_name' => $row['industry_category']))->column('industry_category_id');

//				$this->p($industry_category_id);
				$need_data = array(
					'company_name' => $row['company_name'],
					'company_number' => $row['company_code'],
					'industry_category' => $industry_category_id[0],
				);
//				$this->p($need_data);
				Db::name('company')->insert($need_data);

			}

		}
	}

	/**
	 * 录入行业类别表
	 */
	public function industry_category()
	{
		exit;
		$company_data = Db::name('import_table')->select();

		$filter = 'aaaa';
		foreach($company_data as $row)
		{
			if(!trim($row['industry_category']))
			{
				continue;
			}
			else
			{

				//过滤
				if(strpos($filter,$row['industry_category']))
				{
					continue;
				}

				$need_data = array(
					'industry_category_name' => $row['industry_category'],
					'industry_category_order' => '99',
					'industry_category_explain' => $row['industry_category'],
				);
				Db::name('industry_category')->insert($need_data);

				$filter .= $row['industry_category'];
			}

		}
	}

	/**
	 * 合作状态
	 */
	public function customer_status()
	{
		exit;
		$company_data = Db::name('import_table')->where('id','>',34)->select();

		$filter = 'aaaa';
		foreach($company_data as $row)
		{
			if(!trim($row['service_items']))
			{
				continue;
			}
			else
			{

				//过滤
				if(strpos($filter,trim($row['service_items'])))
				{
					continue;
				}

				if(strpos($row['service_items'],'，'))
				{
					$aa = explode('，',$row['service_items']);
						foreach($aa as $row)
						{
							$need_str[] = $row;
						}
				}
				else
				{
					$need_str[] = $row['service_items'];
				}

//				$need_data = array(
//					'customer_waiter_name' => $row['service_items'],
//					'customer_waiter_order' => '999',
//					'waiter_type' => 2,
//				);

//				Db::name('customer_waiter')->insert($need_data);

//				$filter .= trim($row['service_items']);
			}

		}

		$need_str = array_unique($need_str);

		foreach($need_str as $row)
		{
			$data = array(
				'service_items_name' => $row,
				'service_items_order' => 999,
			);
			Db::name('service_items')->insert($data);
		}


	}


	/**
	 * 对接人处理
	 */
	public function filter_waiter()
	{
		exit;
		$data = Db::name('customer_waiter')->select();
//$this->p($data);exit;
		$aa = 'aaaa';
		foreach($data as $row)
		{
			if(strpos($aa,$row['customer_waiter_name']))
			{
				Db::name('customer_waiter')->where(array('customer_waiter_id' => $row['customer_waiter_id']))->delete();
				continue;
			}
			$aa .= $row['customer_waiter_name'];
		}
echo count($data);
echo '<br>';
		$data = Db::name('customer_waiter')->select();
		echo count($data);echo '<br>';
		$this->p($data,1);
	}
}