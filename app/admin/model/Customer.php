<?php
// +----------------------------------------------------------------------
// | SHULAN
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: GZL [数蓝]
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;

/**
 * 文章模型
 * @package app\admin\model
 */
class Customer extends Model
{
//	protected $insert = ['customer_hits' => 200];
	public function user()
	{
		return $this->belongsTo('MemberList','member_list_id');
	}
	public function menu()
	{
		return $this->belongsTo('Menu','id');
	}
}
