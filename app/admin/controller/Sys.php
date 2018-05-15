<?php
// +----------------------------------------------------------------------
// | SHULAN
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------
// | Author: GZL [数蓝]
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\AuthRule;
use app\admin\model\Options;
use think\Db;
use think\Cache;
use think\log;

class Sys extends Base
{
    /**
     * 站点设置
     */
	public function sys()
	{
		//主题
		$tpls=Options::themes();
		$this->assign('templates',$tpls);
		$sys=Options::get_options('site_options',$this->lang);

		if(!isset($sys['site_co_name']))
		{
			Cache::clear();
			$tpls=Options::themes();
			$this->assign('templates',$tpls);
			$sys=Options::get_options('site_options',$this->lang);
		}

		$this->assign('sys',$sys);

		return $this->fetch();
	}


    /**
     * 站点设置保存
     */
	public function runsys()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/sys'));
		}else{
		    //自动更新
			$update_check=input('update_check',0,'intval')?true:false;;
			sys_config_setbykey('update_check',$update_check);
			//极验验证
            $geetest_on=input('geetest_on',0,'intval')?true:false;
            $captcha_id=input('captcha_id','');
            $private_key=input('private_key','');
			if(empty($captcha_id) || empty($private_key)) $geetest_on=false;
            sys_config_setbykey('geetest',['geetest_on'=>$geetest_on,'captcha_id'=>$captcha_id,'private_key'=>$private_key]);
			if($geetest_on) sys_config_setbykey('url_route_on',true);
			//logo图片
			$checkpic=input('checkpic');
			$oldcheckpic=input('oldcheckpic');
			$options=input('post.options/a');
			$img_url='';
			if ($checkpic!=$oldcheckpic){
				$file = request()->file('file0');
				if(!empty($file)){
					if(config('storage.storage_open')){
						//七牛
						$upload = \Qiniu::instance();
						$info = $upload->upload();
						$error = $upload->getError();
						if ($info) {
							$img_url= config('storage.domain').$info[0]['key'];
						}else{
							$this->error($error,url('admin/Sys/sys'));//否则就是上传错误，显示错误原因
						}
					}else{
						//本地
						$validate=config('upload_validate');
						$info = $file->validate($validate)->rule('uniqid')->move(ROOT_PATH . config('upload_path') . DS . date('Y-m-d'));
						if($info) {
							$img_url=config('upload_path'). '/' . date('Y-m-d') . '/' . $info->getFilename();
							//写入数据库
							$data['uptime']=time();
							$data['filesize']=$info->getSize();
							$data['path']=$img_url;
							Db::name('plug_files')->insert($data);
						}else{
							$this->error($file->getError(),url('admin/Sys/sys'));//否则就是上传错误，显示错误原因
						}
					}
					$options['site_logo']=$img_url;
				}
			}else{
				//原有图片
				$options['site_logo']=input('oldcheckpicname');
			}
			//更新
            $rst=Options::set_options($options,'site_options',$this->lang);
			if($rst!==false){
				cache('site_options_'.$this->lang, $options);
				$this->success('站点设置保存成功',url('admin/Sys/sys'));
			}else{
				$this->error('提交参数不正确',url('admin/Sys/sys'));
			}
		}
	}
	/**
	 * 多语言设置显示
	 */
	public function langsys()
	{
		return $this->fetch();
	}
	/**
	 * 多语言设置保存
	 */
	public function runlangsys()
	{
		$lang_switch_on=input('lang_switch_on',0,'intval')?true:false;
		$default_lang=input('default_lang','');
		sys_config_setbykey('lang_switch_on',$lang_switch_on);
		sys_config_setbykey('default_lang',$default_lang);
		cache::clear();
		cookie('think_var', null);
		$this->success('多语言设置成功',url('admin/Sys/langsys'));
	}
	/**
	 * 日志设置
	 */
    public function logsys()
	{
	    $log=config('log');
	    $log['level']=empty($log['level'])?join(',',['log', 'error', 'info', 'sql', 'notice', 'alert', 'debug']):join(',',$log['level']);
        $this->assign('log',$log);
        return $this->fetch();
    }
	/**
	 * 日志设置保存
	 */
    public function runlogsys()
	{
        $log_level=input('log_level/a');
        $log['clear_on']=input('clear_on',0,'intval')?true:false;
        $log['timebf']=input('timebf',2592000,'intval');
        $log['level']=(count($log_level)==7 || empty($log_level))?[]:$log_level;
        sys_config_setbykey('log',$log);
        cache::clear();
        $this->success('日志设置成功',url('admin/Sys/logsys'));
    }
	/**
	 * URL美化
	 */
	public function urlsetsys()
	{
		$routes=Db::name('route')->order('listorder')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
		$show = $routes->render();
		$show=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$show);
		$this->assign('page',$show);
	    $this->assign('routes',$routes);
	    if(request()->isAjax()){
            return $this->fetch('ajax_urlsetsys');
        }else{
            return $this->fetch();
        }
	}
	/*
     * 添加路由规则操作
	 * shulan
     */
	public function route_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/urlsetsys'));
		}
        Db::name('route')->insert(input('post.'));
        $p=input('p',1,'intval');
        if(config('url_route_mode')=='2') Cache::rm('routes');
        $this->success('路由规则添加成功',url('admin/Sys/urlsetsys',array('p'=>$p)),1);

	}
	/*
     * 修改路由规则操作
	 * shulan
     */
	public function route_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/sys'));
		}
        $p=input('p',1,'intval');
        $sl_data=array(
            'id'=>input('id'),
            'full_url'=>input('full_url'),
            'url'=>input('url'),
            'status'=>input('status'),
            'listorder'=>input('listorder'),
        );
        $rst=Db::name('route')->update($sl_data);
        if($rst!==false){
            if(config('url_route_mode')=='2') Cache::rm('routes');
            $this->success('路由规则修改成功',url('admin/Sys/urlsetsys',array('p'=>$p)));
        }else{
            $this->error('路由规则修改失败',url('admin/Sys/urlsetsys',array('p'=>$p)));
        }
	}
	/*
     * 路由规则修改返回值操作
	 * shulan
     */
	public function route_edit()
	{
		$id=input('id');
		$route=Db::name('route')->where(array('id'=>$id))->find();
        $route['code']=1;
		return json($route);
	}
	/*
     * 路由规则排序
	 * shulan
     */
	public function route_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/urlsetsys'));
		}
        $route=Db::name('route');
        foreach (input('post.') as $id => $listorder){
            $route->where(array('id' => $id ))->setField('listorder' , $listorder);
        }
        if(config('url_route_mode')=='2') Cache::rm('routes');
        $this->success('排序更新成功',url('admin/Sys/urlsetsys'));
	}
	/*
     * 路由规则删除操作
	 * shulan
     */
	public function route_del()
	{
		$rst=Db::name('route')->where(array('id'=>input('id')))->delete();
        if($rst!==false){
			$p=input('p',1,'intval');
            if(config('url_route_mode')=='2') Cache::rm('routes');
            $this->success('路由规则删除成功',url('admin/Sys/urlsetsys',array('p'=>$p)));
        }else{
            $this->error('路由规则删除失败',url('admin/Sys/urlsetsys'));
        }
	}
	/*
     * 修改路由规则状态
	 * shulan
     */
	public function route_state()
	{
		$id=input('x');
		if (empty($id)){
			$this->error('规则ID不存在',url('admin/Sys/urlsetsys'));
		}
		$status=Db::name('route')->where(array('id'=>$id))->value('status');//判断当前状态情况
		if($status==1){
			$statedata = array('status'=>0);
			Db::name('route')->where(array('id'=>$id))->setField($statedata);
            if(config('url_route_mode')=='2') Cache::rm('routes');
			$this->success('状态禁止');
		}else{
			$statedata = array('status'=>1);
			Db::name('route')->where(array('id'=>$id))->setField($statedata);
            if(config('url_route_mode')=='2') Cache::rm('routes');
			$this->success('状态开启');
		}
	}

	/**
	 * URL设置显示
	 */
	public function urlsys(){
		return $this->fetch();
	}
	/*
     * 路由规则设置
	 * shulan
     */
	public function runurlsys()
	{
		$route_on=input('route_on',0,'intval')?true:false;
		$route_must=input('route_must',0,'intval')?true:false;;
		$complete_match=input('complete_match',0,'intval')?true:false;;
		$html_suffix=input('html_suffix','');
		$url_route_mode=input('url_route_mode','');
		sys_config_setbykey('url_route_on',$route_on);
		sys_config_setbykey('url_route_must',$route_must);
		sys_config_setbykey('route_complete_match',$complete_match);
		sys_config_setbykey('url_html_suffix',$html_suffix);
		sys_config_setbykey('url_route_mode',$url_route_mode);
        Cache::rm('routes');
		$this->success('URL基本设置成功',url('admin/Sys/urlsetsys#basic'));
	}
	/**
	 * 发送邮件设置显示
	 */
	public function emailsys()
	{
		$sys=Options::get_options('email_options',$this->lang);
		$this->assign('sys',$sys);
		return $this->fetch();
	}
	/**
	 * 发送邮件设置保存
	 */
	public function runemail()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/emailsys'));
		}else{
		    $options=input('post.options/a');
		    $rst=Options::set_options($options,'email_options',$this->lang);
			if($rst!==false){
				cache("email_options",null);
				$this->success('邮箱设置保存成功',url('admin/Sys/emailsys'));
			}else{
				$this->error('提交参数不正确',url('admin/Sys/emailsys'));
			}
		}
	}
	/**
	 * 帐号激活设置显示
	 */
	public function activesys()
	{
        $sys=Options::get_options('active_options',$this->lang);
		$this->assign('sys',$sys);
		return $this->fetch();
	}
	/**
	 * 帐号激活设置保存
	 */
	public function runactive()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/activesys'));
		}else{
			$options=input('post.options/a');
			$options['email_tpl']=htmlspecialchars_decode($options['email_tpl']);
            $rst=Options::set_options($options,'active_options',$this->lang);
			if($rst!==false){
				cache("active_options",null);
				$this->success('帐号激活设置保存成功',url('admin/Sys/activesys'));
			}else{
				$this->error('提交参数不正确',url('admin/Sys/activesys'));
			}
		}
	}
	/**
	 * 支付设置
	 */
	public function paysys()
	{
		$payment=sys_config_get('payment');
		$this->assign('payment',$payment);
		return $this->fetch();

	}
	/**
	 * 支付设置保存
	 */
	public function runpaysys()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/paysys'));
		}else{
		    $config = input('config/a');
			$rst=sys_config_setbyarr(['payment'=>$config]);
			if($rst!==false){
				Cache::clear();
				$this->success('设置保存成功',url('admin/Sys/paysys'));
			}else{
				$this->error('设置保存失败',url('admin/Sys/paysys'));
			}
		}
	}
	/**
	 * 短信设置
	 */
	public function smssys()
	{
		$sms_sys=sys_config_get('think_sdk_sms');
		$this->assign('sms_sys',$sms_sys);
		return $this->fetch();
	}
	/**
	 * 短信设置保存
	 */
	public function runsmssys()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/smssys'));
		}else{
            $data = array(
                'think_sdk_sms' => array(
                    'AccessKeyId' => input('AccessKeyId'),
                    'accessKeySecret' => input('accessKeySecret'),
                    'signName' => input('signName'),
                    'TemplateCode' => input('TemplateCode'),
                    'sms_open' => input('sms_open'),
                )
            );
			$rst=sys_config_setbyarr($data);
			if($rst!==false){
				Cache::clear();
				$this->success('设置保存成功',url('admin/Sys/smssys'));
			}else{
				$this->error('设置保存失败',url('admin/Sys/smssys'));
			}
		}
	}
	/**
	 * 第三方登录设置
	 */
	public function oauthsys()
	{
		$oauth_qq=sys_config_get('think_sdk_qq');
		$oauth_sina=sys_config_get('think_sdk_sina');
		$oauth_weixin=sys_config_get('think_sdk_weixin');
		$oauth_wechat=sys_config_get('think_sdk_wechat');
		$oauth_facebook=sys_config_get('think_sdk_facebook');
		$oauth_google=sys_config_get('think_sdk_google');
		$this->assign('oauth_qq',$oauth_qq);
		$this->assign('oauth_sina',$oauth_sina);
		$this->assign('oauth_wechat',$oauth_wechat);
		$this->assign('oauth_weixin',$oauth_weixin);
		$this->assign('oauth_facebook',$oauth_facebook);
		$this->assign('oauth_google',$oauth_google);
		return $this->fetch();
	}
	/**
	 * 第三方登录设置保存
	 */
	public function runoauthsys()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/oauthsys'));
		}else{
			$host=get_host();
			$data = array(
				'think_sdk_qq' => array(
					'app_key'    => input('qq_appid'),
					'app_secret' => input('qq_appkey'),
					'display' => input('qq_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=qq'),
				),
				'think_sdk_weixin' => array(
					'app_key'    => input('weixin_appid'),
					'app_secret' => input('weixin_appkey'),
					'display' => input('weixin_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=weixin'),
				),
				'think_sdk_wechat' => array(
					'app_key'    => input('wechat_appid'),
					'app_secret' => input('wechat_appkey'),
					'display' => input('wechat_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=wechat'),
				),
				'think_sdk_google' => array(
					'app_key'    => input('google_appid'),
					'app_secret' => input('google_appkey'),
					'display' => input('google_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=google'),
				),
				'think_sdk_facebook' => array(
					'app_key'    => input('facebook_appid'),
					'app_secret' => input('facebook_appkey'),
					'display' => input('facebook_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=facebook'),
				),
				'think_sdk_sina' => array(
					'app_key'    => input('sina_appid'),
					'app_secret' => input('sina_appkey'),
					'display' => input('sina_display',0,'intval')?true:false,
					'callback'   => $host.url('home/Oauth/callback','type=sina'),
				),
			);
			$rst=sys_config_setbyarr($data);
			if($rst){
				Cache::clear();
				$this->success('设置保存成功',url('admin/Sys/oauthsys'));
			}else{
				$this->error('设置保存失败',url('admin/Sys/oauthsys'));
			}
		}
	}
	/**
	 * 云存储设置
	 */
	public function storagesys()
	{
		$storage=config('storage');
		$this->assign('storage',$storage);
		return $this->fetch();
	}
	/**
	 * 云存储设置保存
	 */
	public function runstorage()
	{
		$storage=array(
			'storage_open'=>input('storage_open',0)?true:false,
			'accesskey'=>input('accesskey',''),
			'secretkey'=>input('secretkey',''),
			'bucket'=>input('bucket',''),
			'domain'=>input('domain','')
		);
		$rst=sys_config_setbyarr(array('storage'=>$storage));
		if($rst){
			Cache::clear();
			$this->success('设置保存成功',url('admin/Sys/storagesys'));
		}else{
			$this->error('设置保存失败',url('admin/Sys/storagesys'));
		}
	}

	/////////////////////////////////////////////////////////////////////////   文章来源列表   start
	/*
     * 文章来源列表
     * shulan
     */
	public function source_list()
	{
		$source=Db::name('source')->order('source_order,source_id desc')->paginate(config('paginate.list_rows'));
		$page = $source->render();
		$this->assign('source',$source);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
     * 添加来源操作
     * shulan
     */
	public function source_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/source_list'));
		}else{
			$data=input('post.');
			Db::name('source')->insert($data);
			$this->success('来源添加成功',url('admin/Sys/source_list'));
		}
	}
	/*
     * 来源删除操作
     * shulan
     */
	public function source_del()
	{
		$p=input('p');
		$rst=Db::name('source')->where(array('source_id'=>input('source_id')))->delete();
		if($rst!==false){
			$this->success('来源删除成功',url('admin/Sys/source_list',array('p' => $p)));
		}else{
			$this->error('来源删除失败',url('admin/Sys/source_list',array('p' => $p)));
		}
	}
	/*
     * 来源修改返回值操作
     * shulan
     */
	public function source_edit()
	{
		$source_id=input('source_id');
		$source=Db::name('source')->where(array('source_id'=>$source_id))->find();
		$sl_data['source_id']=$source['source_id'];
		$sl_data['source_name']=$source['source_name'];
		$sl_data['source_order']=$source['source_order'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
     * 修改来源操作
     * shulan
     */
	public function source_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/source_list'));
		}else{
			$sl_data=array(
				'source_id'=>input('source_id'),
				'source_name'=>input('source_name'),
				'source_order'=>input('source_order',999),
			);
			$rst=Db::name('source')->update($sl_data);
			if($rst!==false){
				$this->success('来源修改成功',url('admin/Sys/source_list'));
			}else{
				$this->error('来源修改失败',url('admin/Sys/source_list'));
			}
		}
	}
	/*
     * 来源排序
     * shulan
     */
	public function source_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/source_list'));
		}else{
			foreach (input('post.') as $source_id => $source_order){
				Db::name('source')->where(array('source_id' => $source_id ))->setField('source_order' , $source_order);
			}
			$this->success('排序更新成功',url('admin/Sys/source_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   文章回款周期列表   end


/////////////////////////////////////////////////////////////////////////   回款周期   start
	/*
	 * admin/Sys/collection_period_list
	 *
     * 回款周期列表
     * shulan
     */
	public function collection_period_list()
	{
		$collection_period=Db::name('collection_period')->order('collection_period_order,collection_period_id desc')->paginate(config('paginate.list_rows'));
		$page = $collection_period->render();
		$this->assign('collection_period',$collection_period);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
     * 添加回款周期操作
     * shulan
     */
	public function collection_period_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/collection_period_list'));
		}else{
			$data=input('post.');
			Db::name('collection_period')->insert($data);
			$this->success('回款周期添加成功',url('admin/Sys/collection_period_list'));
		}
	}
	/*
     * 回款周期删除操作
     * shulan
     */
	public function collection_period_del()
	{
		$p=input('p');
		$rst=Db::name('collection_period')->where(array('collection_period_id'=>input('collection_period_id')))->delete();
		if($rst!==false){
			$this->success('回款周期删除成功',url('admin/Sys/collection_period_list',array('p' => $p)));
		}else{
			$this->error('回款周期删除失败',url('admin/Sys/collection_period_list',array('p' => $p)));
		}
	}
	/*
     * 回款周期修改返回值操作
     * shulan
     */
	public function collection_period_edit()
	{
		$collection_period_id=input('collection_period_id');
		$collection_period=Db::name('collection_period')->where(array('collection_period_id'=>$collection_period_id))->find();
		$sl_data['collection_period_id']=$collection_period['collection_period_id'];
		$sl_data['collection_period_name']=$collection_period['collection_period_name'];
		$sl_data['collection_period_order']=$collection_period['collection_period_order'];
		$sl_data['equivalent_months']=$collection_period['equivalent_months'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
     * 修改回款周期操作
     * shulan
     */
	public function collection_period_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/collection_period_list'));
		}else{
			$sl_data=array(
				'collection_period_id'=>input('collection_period_id'),
				'collection_period_name'=>input('collection_period_name'),
				'collection_period_order'=>input('collection_period_order',999),
				'equivalent_months' =>input('equivalent_months'),
			);
			$rst=Db::name('collection_period')->update($sl_data);
			if($rst!==false){
				$this->success('回款周期修改成功',url('admin/Sys/collection_period_list'));
			}else{
				$this->error('回款周期修改失败',url('admin/Sys/collection_period_list'));
			}
		}
	}
	/*
     * 回款周期排序
     * shulan
     */
	public function collection_period_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/collection_period_list'));
		}else{
			foreach (input('post.') as $collection_period_id => $collection_period_order){
				Db::name('collection_period')->where(array('collection_period_id' => $collection_period_id ))->setField('collection_period_order' , $collection_period_order);
			}
			$this->success('排序更新成功',url('admin/Sys/collection_period_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   回款周期   end


/////////////////////////////////////////////////////////////////////////   项目状态   start
	/*
    * admin/Sys/customer_status_list
    *
    * 项目状态列表
    * shulan
    */
	public function customer_status_list()
	{
		$customer_status=Db::name('customer_status')->order('customer_status_order,customer_status_id desc')->paginate(config('paginate.list_rows'));
		$page = $customer_status->render();
		$this->assign('customer_status',$customer_status);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
    * 添加项目状态操作
    * shulan
    */
	public function customer_status_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_status_list'));
		}else{
			$data=input('post.');
			$data['customer_status_order'] = 999;
			Db::name('customer_status')->insert($data);
			$this->success('项目状态添加成功',url('admin/Sys/customer_status_list'));
		}
	}
	/*
    * 项目状态删除操作
    * shulan
    */
	public function customer_status_del()
	{
		$p=input('p');
		$rst=Db::name('customer_status')->where(array('customer_status_id'=>input('customer_status_id')))->delete();
		if($rst!==false){
			$this->success('项目状态删除成功',url('admin/Sys/customer_status_list',array('p' => $p)));
		}else{
			$this->error('项目状态删除失败',url('admin/Sys/customer_status_list',array('p' => $p)));
		}
	}
	/*
    * 项目状态修改返回值操作
    * shulan
    */
	public function customer_status_edit()
	{
		$customer_status_id=input('customer_status_id');
		$customer_status=Db::name('customer_status')->where(array('customer_status_id'=>$customer_status_id))->find();
		$sl_data['customer_status_id']=$customer_status['customer_status_id'];
		$sl_data['customer_status_name']=$customer_status['customer_status_name'];
		$sl_data['customer_status_order']=$customer_status['customer_status_order'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改项目状态操作
    * shulan
    */
	public function customer_status_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/customer_status_list'));
		}
		else
		{
			$sl_data=array(
				'customer_status_id'=>input('customer_status_id'),
				'customer_status_name'=>input('customer_status_name'),
				'customer_status_order'=>input('customer_status_order',999),
			);

			$rst=Db::name('customer_status')->update($sl_data);
			if($rst!==false){
				$this->success('项目状态修改成功',url('admin/Sys/customer_status_list'));
			}else{
				$this->error('项目状态修改失败',url('admin/Sys/customer_status_list'));
			}
		}
	}
	/*
    * 项目状态排序
    * shulan
    */
	public function customer_status_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_status_list'));
		}else{
			foreach (input('post.') as $customer_status_id => $customer_status_order){
				Db::name('customer_status')->where(array('customer_status_id' => $customer_status_id ))->setField('customer_status_order' , $customer_status_order);
			}
			$this->success('排序更新成功',url('admin/Sys/customer_status_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   项目状态   end

	/**
	 * 权限(后台菜单)列表
	 */
	public function admin_rule_list()
	{
		$pid=input('pid',0);
		$level=input('level',0);
		$id_str=input('id','pid');
		$admin_rule=Db::name('auth_rule')->where('pid',$pid)->order('sort')->select();
        $admin_rule_all=Db::name('auth_rule')->order('sort')->select();
		$arr = menu_left($admin_rule,'id','pid','─',$pid,$level,$level*20);
        $arr_all = menu_left($admin_rule_all,'id','pid','─',0,$level,$level*20);
		$this->assign('admin_rule',$arr);
        $this->assign('admin_rule_all',$arr_all);
		$this->assign('pid',$id_str);
		if(request()->isAjax()){
			return $this->fetch('ajax_admin_rule_list');
		}else{
			return $this->fetch();
		}
	}
	/**
	 * 权限(后台菜单)添加
	 */
	public function admin_rule_add()
	{
		$pid=input('pid',0);
		//全部规则
		$admin_rule_all=Db::name('auth_rule')->order('sort')->select();
		$arr = menu_left($admin_rule_all);
		$this->assign('admin_rule',$arr);
		$this->assign('pid',$pid);
		return $this->fetch();
	}
	/**
	 * 权限(后台菜单)添加操作
	 */
	public function admin_rule_runadd()
	{
		if(!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/admin_rule_list'));
		}
		else{
			$pid=Db::name('auth_rule')->where(array('id'=>input('pid')))->field('level')->find();
			$level=$pid['level']+1;
			$name=input('name');
			$name=AuthRule::check_name($name,$level);
			if($name)
			{
				$sldata=array(
					'name'=>$name,
					'title'=>input('title'),
					'status'=>input('status',0,'intval'),
					'sort'=>input('sort',50,'intval'),
					'pid'=>input('pid'),
                    'notcheck'=>input('notcheck',0,'intval'),
					'addtime'=>time(),
					'css'=>input('css',''),
					'level'=>$level,
				);
				Db::name('auth_rule')->insert($sldata);
				Cache::clear();
				$this->success('权限添加成功',url('admin/Sys/admin_rule_list'),1);
			}else{
				$this->error('控制器或方法不存在,或提交格式不规范',url('admin/Sys/admin_rule_list'));
			}
		}
	}
	/**
	 * 权限(后台菜单)显示/隐藏
	 */
	public function admin_rule_state()
	{
		$id=input('x');
		$statusone=Db::name('auth_rule')->where(array('id'=>$id))->value('status');//判断当前状态情况
		if($statusone==1){
			$statedata = array('status'=>0);
			Db::name('auth_rule')->where(array('id'=>$id))->setField($statedata);
			Cache::clear();
			$this->success('状态禁止');
		}else{
			$statedata = array('status'=>1);
			Db::name('auth_rule')->where(array('id'=>$id))->setField($statedata);
			Cache::clear();
			$this->success('状态开启');
		}
	}
    /**
     * 权限(后台菜单)检测/不检测
     */
    public function admin_rule_notcheck()
    {
        $id=input('x');
        $statusone=Db::name('auth_rule')->where(array('id'=>$id))->value('notcheck');//判断当前状态情况
        if($statusone==1){
            $statedata = array('notcheck'=>0);
            Db::name('auth_rule')->where(array('id'=>$id))->setField($statedata);
            Cache::clear();
            $this->success('检测');
        }else{
            $statedata = array('notcheck'=>1);
            Db::name('auth_rule')->where(array('id'=>$id))->setField($statedata);
            Cache::clear();
            $this->success('不检测');
        }
    }
	/**
	 * 权限(后台菜单)排序
	 */
	public function admin_rule_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/admin_rule_list'));
		}else{
			foreach ($_POST as $id => $sort){
				Db::name('auth_rule')->where(array('id' => $id ))->setField('sort' , $sort);
			}
			Cache::clear();
			$this->success('排序更新成功',url('admin/Sys/admin_rule_list'));
		}
	}
	/**
	 * 权限(后台菜单)编辑
	 */
	public function admin_rule_edit()
	{
		//全部规则
		$admin_rule_all=Db::name('auth_rule')->order('sort')->select();
		$arr = menu_left($admin_rule_all);
		$this->assign('admin_rule',$arr);
		//待编辑规则
		$admin_rule=Db::name('auth_rule')->where(array('id'=>input('id')))->find();
		$this->assign('rule',$admin_rule);
		return $this->fetch();
	}
	/**
	 * 权限(后台菜单)通过复制添加
	 */
	public function admin_rule_copy()
	{
		//全部规则
		$admin_rule_all=Db::name('auth_rule')->order('sort')->select();
		$arr = menu_left($admin_rule_all);
		$this->assign('admin_rule',$arr);
		//待编辑规则
		$admin_rule=Db::name('auth_rule')->where(array('id'=>input('id')))->find();
		$this->assign('rule',$admin_rule);
		return $this->fetch();
	}
	/**
	 * 权限(后台菜单)编辑操作
	 */
	public function admin_rule_runedit()
	{
		if(!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/admin_rule_list'));
		}
		else
		{
			$name=input('name');
			$old_pid=input('old_pid');
			$old_level=input('old_level',0,'intval');
			$pid=input('pid');
			$level_diff=0;
			//判断是否更改了pid
			if($pid!=$old_pid)
			{
				$level=Db::name('auth_rule')->where('id',$pid)->value('level')+1;
				$level_diff=($level>$old_level)?($level-$old_level):($old_level-$level);
			}
			else
			{
				$level=$old_level;
			}
			$name=AuthRule::check_name($name,$level);

			if($name)
			{
				$sldata=array(
					'id'=>input('id',1,'intval'),
					'name'=>$name,
					'title'=>input('title'),
					'status'=>input('status',0,'intval'),
                    'notcheck'=>input('notcheck',0,'intval'),
					'pid'=>input('pid',0,'intval'),
					'css'=>input('css'),
					'sort'=>input('sort'),
					'level'=>$level
				);
				$rst=Db::name('auth_rule')->update($sldata);
				if($rst!==false){
					if($pid!=$old_pid){
						//更新子孙级菜单的level
						$auth_rule=Db::name('auth_rule')->order('sort')->select();
						$tree=new \Tree();
						$tree->init($auth_rule,['parentid'=>'pid']);
						$ids=$tree->get_childs($auth_rule,$sldata['id'],true,false);
						if($ids){
							if($level>$old_level){
								Db::name('auth_rule')->where('id','in',$ids)->setInc('level',$level_diff);
							}else{
								Db::name('auth_rule')->where('id','in',$ids)->setDec('level',$level_diff);
							}
						}
					}
					Cache::clear();
					$this->success('权限修改成功',url('admin/Sys/admin_rule_list'));
				}else{
					$this->error('权限修改失败',url('admin/Sys/admin_rule_list'));
				}
			}
			else
			{
				$this->error('控制器或方法不存在,或提交格式不规范',url('admin/Sys/admin_rule_list'));
			}
		}
	}
	/**
	 * 权限(后台菜单)删除
	 */
	public function admin_rule_del()
	{
        $pid=input('id');
        $arr=Db::name('auth_rule')->select();
        $tree=new \Tree();
        $tree->init($arr,['parentid'=>'pid']);
        $arrTree=$tree->get_childs($arr,$pid,true,true);
        if($arrTree){
            $rst=Db::name('auth_rule')->where('id','in',$arrTree)->delete();
            if($rst!==false){
                Cache::clear();
                $this->success('权限删除成功',url('admin/Sys/admin_rule_list'));
            }else{
                $this->error('权限删除失败',url('admin/Sys/admin_rule_list'));
            }
        }else{
            $this->error('权限删除失败',url('admin/Sys/admin_rule_list'));
        }
	}
	/*
	 * 数据备份显示
	 *
		 * http://tp.demo/admin/sys/database?type=export    数据库备份
	 *
	 */
	public function database($type = null)
	{

		if(empty($type))
		{
			$type='export';
		}
		$title='';
		$list=array();

		switch ($type)
		{
			/* 数据还原 */
			case 'import':
				//列出备份文件列表
				$path=config('db_path');
				if (!is_dir($path))
				{
					mkdir($path, 0755, true);
				}

				$path = realpath($path);
				$flag = \FilesystemIterator::KEY_AS_FILENAME;
				$glob = new \FilesystemIterator($path,  $flag);

				$list = array();
				foreach ($glob as $name => $file)
				{
					if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name))
					{
						$name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

						$date = "{$name[0]}-{$name[1]}-{$name[2]}";
						$time = "{$name[3]}:{$name[4]}:{$name[5]}";
						$part = $name[6];

						if(isset($list["{$date} {$time}"])){
							$info = $list["{$date} {$time}"];
							$info['part'] = max($info['part'], $part);
							$info['size'] = $info['size'] + $file->getSize();
						} else {
							$info['part'] = $part;
							$info['size'] = $file->getSize();
						}
						$extension        = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
						$info['compress'] = ($extension === 'SQL') ? '-' : $extension;
						$info['time']     = strtotime("{$date} {$time}");
						$list["{$date} {$time}"] = $info;
					}
				}
				$title = '数据还原';
				break;

			/* 数据备份 */
			case 'export':
				$list  = Db::query('SHOW TABLE STATUS FROM '.config('database.database'));
				$list  = array_map('array_change_key_case', $list);

//				$this->p($list,1);

				//过滤非本项目前缀的表
//				foreach($list as $k=>$v)
//				{
//					if(stripos($v['name'],strtolower(config('database.prefix')))!==0)
//					{
//						unset($list[$k]);
//					}
//				}
				$title = '数据备份';
				break;

			default:
				$this->error('参数错误！');
		}

		//渲染模板
		$this->assign('meta_title', $title);
		$this->assign('data_list', $list);
		return $this->fetch($type);
	}
	/*
	 * 数据还原显示
	 */
	public function import()
	{
		$path=config('db_path');
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
		}
		$path = realpath($path);
		$flag = \FilesystemIterator::KEY_AS_FILENAME;
		$glob = new \FilesystemIterator($path,$flag);

		$list = array();
		foreach ($glob as $name => $file)
		{
			if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)){
				$name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

				$date = "{$name[0]}-{$name[1]}-{$name[2]}";
				$time = "{$name[3]}:{$name[4]}:{$name[5]}";
				$part = $name[6];

				if(isset($list["{$date} {$time}"])){
					$info = $list["{$date} {$time}"];
					$info['part'] = max($info['part'], $part);
					$info['size'] = $info['size'] + $file->getSize();
				} else {
					$info['part'] = $part;
					$info['size'] = $file->getSize();
				}
				$extension        = (pathinfo($file->getFilename(), PATHINFO_EXTENSION));
				$info['compress'] = ($extension === 'SQL') ? '-' : $extension;
				$info['time']     = strtotime("{$date} {$time}");

				$list["{$date} {$time}"] = $info;
			}
		}
		//渲染模板
		$this->assign('data_list', $list);
		return $this->fetch();
	}
	/**
	 * 优化表
	 * @param  String $tables 表名
	 * shulan
	 */
	public function optimize($tables = null)
	{
		if($tables)
		{
			if(is_array($tables))
			{
				$tables = implode('`,`', $tables);
				$list = Db::query("OPTIMIZE TABLE `{$tables}`");
				if($list){
					$this->success("数据表优化完成！");
				} else {
					$this->error("数据表优化出错请重试！");
				}
			}
			else
			{
				$list = Db::query("OPTIMIZE TABLE `{$tables}`");
				if($list){
					$this->success("数据表'{$tables}'优化完成！");
				} else {
					$this->error("数据表'{$tables}'优化出错请重试！");
				}
			}
		}
		else
		{
			$this->error("请指定要优化的表！");
		}
	}
	/**
	 * 修复表
	 * @param  String $tables 表名
	 * shulan
	 */
	public function repair($tables = null)
	{
		if($tables) {
			if(is_array($tables)){
				$tables = implode('`,`', $tables);
				$list = Db::query("REPAIR TABLE `{$tables}`");
				if($list){
					$this->success("数据表修复完成！");
				} else {
					$this->error("数据表修复出错请重试！");
				}
			} else {
				$list = Db::query("REPAIR TABLE `{$tables}`");
				if($list){
					$this->success("数据表'{$tables}'修复完成！");
				} else {
					$this->error("数据表'{$tables}'修复出错请重试！");
				}
			}
		} else {
			$this->error("请指定要修复的表！");
		}
	}
	/**
	 * 备份单表
	 * @param  String $table 不含前缀表名
	 * shulan
	 */
	public function exportsql($table = null)
	{
		if($table){
			if(stripos($table,config('database.prefix'))==0){
				//含前缀的表,去除表前缀
				$table=str_replace(config('database.prefix'),"",$table);
			}
			if (!db_is_valid_table_name($table)) {
				$this->error("不存在表" . ' ' . $table);
			}
			force_download_content(date('Ymd') . '_' . config('database.prefix') . $table . '.sql', db_get_insert_sqls($table));
		}else{
			$this->error('未指定需备份的表');
		}
	}
	/**
	 * 删除备份文件
	 * @param  Integer $time 备份时间
	 * shulan
	 */
	public function del($time = 0)
	{
		if($time){
			$name  = date('Ymd-His', $time) . '-*.sql*';
			$path  = realpath(config('db_path')) . DS . $name;
			array_map("unlink", glob($path));
			if(count(glob($path))){
				$this->error('备份文件删除失败，请检查权限！',url('admin/Sys/import'));
			} else {
				$this->success('备份文件删除成功！',url('admin/Sys/import'));
			}
		} else {
			$this->error('参数错误！',url('admin/Sys/import'));
		}
	}

	/*
	 * 数据还原
	 */
	public function restore($time = 0, $part = null, $start = null)
	{
		//读取备份配置
		$config = array(
			'path'     => realpath(config('db_path')) . DS,
			'part'     => config('db_part'),
			'compress' => config('db_compress'),
			'level'    => config('db_level'),
		);
		if(is_numeric($time) && is_null($part) && is_null($start)){ //初始化
			//获取备份文件信息
			$name  = date('Ymd-His', $time) . '-*.sql*';
			$path  = realpath(config('db_path')) . DS . $name;
			$files = glob($path);
			$list  = array();
			foreach($files as $name){
				$basename = basename($name);
				$match    = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
				$gz       = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
				$list[$match[6]] = array($match[6], $name, $gz);
			}
			ksort($list);
			//检测文件正确性
			$last = end($list);
			if(count($list) === $last[0]){
				session('backup_list', $list); //缓存备份列表
				$this->restore(0,1,0);
			} else {
				$this->error('备份文件可能已经损坏，请检查！');
			}
		} elseif(is_numeric($part) && is_numeric($start)) {
			$list  = session('backup_list');
			$db = new \Database($list[$part],$config);
			$start = $db->import($start);
			if(false === $start){
				$this->error('还原数据出错！');
			} elseif(0 === $start) { //下一卷
				if(isset($list[++$part])){
					//$data = array('part' => $part, 'start' => 0);
					$this->restore(0,$part,0);
				} else {
					session('backup_list', null);
					$this->success('还原完成！',url('admin/Sys/import'));
				}
			} else {
				$data = array('part' => $part, 'start' => $start[0]);
				if($start[1]){
					$this->restore(0,$part, $start[0]);
				} else {
					$data['gz'] = 1;
					$this->restore(0,$part, $start[0]);
				}
			}
		} else {
			$this->error('参数错误！');
		}
	}
	/*
	 * 数据备份
	 */
	public function export($tables = null, $id = null, $start = null)
	{
		if(request()->isPost() && !empty($tables) && is_array($tables))
		{
		//初始化
		//读取备份配置
			$config = array(
				'path'     => realpath(config('db_path')) . DS,
				'part'     => config('db_part'),
				'compress' => config('db_compress'),
				'level'    => config('db_level'),
			);
			//检查是否有正在执行的任务
			$lock = "{$config['path']}backup.lock";
			if(is_file($lock))
			{
				$this->error('检测到有一个备份任务正在执行，请稍后再试！');
			}
			else
			{
				//创建锁文件
				file_put_contents($lock, time());
			}

			//检查备份目录是否可写
			is_writeable($config['path']) || $this->error('备份目录不存在或不可写，请检查后重试！');
			session('backup_config', $config);

			//生成备份文件信息
			$file = array(
				'name' => date('Ymd-His', time()),
				'part' => 1,
			);
			session('backup_file', $file);
			//缓存要备份的表
			session('backup_tables', $tables);

			//创建备份文件
			$Database = new \Database($file, $config);


			if(false !== $Database->create())
			{
				$tab = array('id' => 0, 'start' => 0);
				return json(array('code'=>1,'tab' => $tab,'tables' => $tables,'msg'=>'初始化成功！'));
			}
			else
			{
				$this->error('初始化失败，备份文件创建失败！');
			}
		}
		elseif (request()->isGet() && is_numeric($id) && is_numeric($start))
		{
			//备份数据
			$tables = session('backup_tables');
			//备份指定表
			$Database = new \Database(session('backup_file'), session('backup_config'));
			$start  = $Database->backup($tables[$id], $start);
			if(false === $start){ //出错
				$this->error('备份出错！');
			} elseif (0 === $start) { //下一表
				if(isset($tables[++$id])){
					$tab = array('id' => $id, 'start' => 0);
					return json(array('code'=>1,'tab' => $tab,'msg'=>'备份完成！'));
				} else { //备份完成，清空缓存
					unlink(session('backup_config.path') . 'backup.lock');
					session('backup_tables', null);
					session('backup_file', null);
					session('backup_config', null);
					return json(array('code'=>1,'msg'=>'备份完成！'));
				}
			} else {
				$tab  = array('id' => $id, 'start' => $start[0]);
				$rate = floor(100 * ($start[0] / $start[1]));
				return json(array('code'=>1,'tab' => $tab,'msg'=>"正在备份...({$rate}%)"));
			}
		}
		else {
		//出错
			$this->error('参数错误！');
		}
	}
	/*
	 * Excel导入
	 */
	public function excel_import()
	{
		return $this->fetch();
	}
	/*
	 * Excel导出
	 */
	public function excel_export()
	{
		$list  = Db::query('SHOW TABLE STATUS FROM '.config('database.database'));
		$list  = array_map('array_change_key_case', $list);
		//过滤非本项目前缀的表
		foreach($list as $k=>$v){
			if(stripos($v['name'],strtolower(config('database.prefix')))!==0){
				unset($list[$k]);
			}
		}
		$this->assign('data_list', $list);
		return $this->fetch();
	}

	/*
	 * 表格导入
	 * shulan
	 */
	public function excel_runimport()
	{
		if (! empty ( $_FILES ['file_stu'] ['name'] ))
		{
			$tmp_file = $_FILES ['file_stu'] ['tmp_name'];
			$file_types = explode ( ".", $_FILES ['file_stu'] ['name'] );
			$file_type = $file_types [count ( $file_types ) - 1];

			/*判别是不是SQL文件*/
			if (strtolower ( $file_type ) != "gz"){
				$this->error ( '不是gz文件，重新上传',url('admin/Sys/excel_import'));
			}
			/*设置上传路径*/
			$savePath = config('db_path');

			/*以时间来命名上传的文件*/
			$time_1 = time();
			$str = date('Ymd',$time_1).'-'.date('his',$time_1).'-1.sql';

			$file_name = $str . "." . $file_type;

			if (! copy ( $tmp_file, $savePath . $file_name ))
			{
				$this->error ('上传失败',url('admin/Sys/excel_import'));
			}

			$this->restore_1($time_1);

			$this->success ('导入数据库成功',url('admin/Sys/excel_import'));
		}
	}

	/*
	 * 导入后的处理（非备份方法）
	 */
	public function restore_1($time = 0, $part = null, $start = null)
	{
		//读取备份配置
		$config = array(
			'path'     => realpath(config('db_path')) . DS,
			'part'     => config('db_part'),
			'compress' => config('db_compress'),
			'level'    => config('db_level'),
		);


		if(is_numeric($time) && is_null($part) && is_null($start))
		{
			//初始化
			//获取备份文件信息
			$name  = date('Ymd-his', $time) . '-*.sql*';
			$path  = realpath(config('db_path')) . DS . $name;
			$files = glob($path);
			$list  = array();
			foreach($files as $name)
			{
				$basename = basename($name);
				$match    = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
				$gz       = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
				$list[$match[6]] = array($match[6], $name, $gz);
			}
			ksort($list);
			//检测文件正确性
			$last = end($list);
			if(count($list) === $last[0])
			{
				session('backup_list', $list); //缓存备份列表
				$this->restore_1(0,1,0);
			} else {
				$this->error('备份文件可能已经损坏，请检查！');
			}
		}
		elseif(is_numeric($part) && is_numeric($start))
		{
			$list  = session('backup_list');
			$db = new \Database($list[$part],$config);
			$start = $db->import($start);
			if(false === $start){
				$this->error('还原数据出错！');
			} elseif(0 === $start) { //下一卷
				if(isset($list[++$part])){
					//$data = array('part' => $part, 'start' => 0);
					$this->restore_1(0,$part,0);
				} else {
					session('backup_list', null);
//					$this->success('还原完成！',url('admin/Sys/excel_import'));
					$this->success ('导入数据库成功',url('admin/Sys/excel_import'));
				}
			} else {
				$data = array('part' => $part, 'start' => $start[0]);
				if($start[1]){
					$this->restore_1(0,$part, $start[0]);
				} else {
					$data['gz'] = 1;
					$this->restore_1(0,$part, $start[0]);
				}
			}
		} else {
			$this->error('参数错误！');
		}
	}


	/*
	 * 数据导出功能
	 * shulan
	 */
	public function excel_runexport($table)
	{
		export2excel($table);
	}
	/*
	 * 清理缓存
	 */
	public function clear()
	{
		Cache::clear();
		$this->success ('清理缓存成功');
	}


	/////////////////////////////////////////////////////////////////////////   机构信用等级   start
	/*
    * admin/Sys/credit_rating_list
    *
    *机构信用等级列表
    * shulan
    */
	public function credit_rating_list()
	{
		$credit_rating=Db::name('credit_rating')->order('credit_rating_order,credit_rating_id desc')->paginate(config('paginate.list_rows'));
		$page = $credit_rating->render();
		$this->assign('credit_rating',$credit_rating);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
    * 添加信用等级操作
    * shulan
    */
	public function credit_rating_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/credit_rating_list'));
		}
		else
		{
			$data=input('post.');
			Db::name('credit_rating')->insert($data);
			$this->success('信用等级添加成功',url('admin/Sys/credit_rating_list'));
		}
	}
	/*
    * 信用等级删除操作
    * shulan
    */
	public function credit_rating_del()
	{
		$p=input('p');
		$rst=Db::name('credit_rating')->where(array('credit_rating_id'=>input('credit_rating_id')))->delete();
		if($rst!==false){
			$this->success('信用等级删除成功',url('admin/Sys/credit_rating_list',array('p' => $p)));
		}else{
			$this->error('信用等级删除失败',url('admin/Sys/credit_rating_list',array('p' => $p)));
		}
	}
	/*
    * 信用等级修改返回值操作
    * shulan
    */
	public function credit_rating_edit()
	{
		$credit_rating_id=input('credit_rating_id');
		$credit_rating=Db::name('credit_rating')->where(array('credit_rating_id'=>$credit_rating_id))->find();
		$sl_data['credit_rating_id']=$credit_rating['credit_rating_id'];
		$sl_data['credit_rating_name']=$credit_rating['credit_rating_name'];
		$sl_data['credit_rating_order']=$credit_rating['credit_rating_order'];
		$sl_data['credit_rating_explain']=$credit_rating['credit_rating_explain'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改信用等级操作
    * shulan
    */
	public function credit_rating_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/credit_rating_list'));
		}else{
			$sl_data=array(
				'credit_rating_id'=>input('credit_rating_id'),
				'credit_rating_name'=>input('credit_rating_name'),
				'credit_rating_order'=>input('credit_rating_order',999),
				'credit_rating_explain'=>input('credit_rating_explain'),
			);
			$rst=Db::name('credit_rating')->update($sl_data);
			if($rst!==false){
				$this->success('信用等级修改成功',url('admin/Sys/credit_rating_list'));
			}else{
				$this->error('信用等级修改失败',url('admin/Sys/credit_rating_list'));
			}
		}
	}
	/*
    * 信用等级排序
    * shulan
    */
	public function credit_rating_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/credit_rating_list'));
		}else{
			foreach (input('post.') as $credit_rating_id => $credit_rating_order){
				Db::name('credit_rating')->where(array('credit_rating_id' => $credit_rating_id ))->setField('credit_rating_order' , $credit_rating_order);
			}
			$this->success('排序更新成功',url('admin/Sys/credit_rating_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   机构信用等级   end


/////////////////////////////////////////////////////////////////////////   行业类别   start
	/*
    * admin/Sys/industry_category_list
    *
    *行业类别列表
    * shulan
    */
	public function industry_category_list()
	{
		$industry_category=Db::name('industry_category')->order('industry_category_order,industry_category_id desc')->paginate(25);
		$page = $industry_category->render();
		$this->assign('industry_category',$industry_category);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
    * 添加行业类别操作
    * shulan
    */
	public function industry_category_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/industry_category_list'));
		}
		else
		{
			$data=input('post.');
			Db::name('industry_category')->insert($data);
			$this->success('行业类别添加成功',url('admin/Sys/industry_category_list'));
		}
	}
	/*
    * 行业类别删除操作
    * shulan
    */
	public function industry_category_del()
	{
		$p=input('p');
		$rst=Db::name('industry_category')->where(array('industry_category_id'=>input('industry_category_id')))->delete();
		if($rst!==false){
			$this->success('行业类别删除成功',url('admin/Sys/industry_category_list',array('p' => $p)));
		}else{
			$this->error('行业类别删除失败',url('admin/Sys/industry_category_list',array('p' => $p)));
		}
	}
	/*
    * 行业类别修改返回值操作
    * shulan
    */
	public function industry_category_edit()
	{
		$industry_category_id=input('industry_category_id');
		$industry_category=Db::name('industry_category')->where(array('industry_category_id'=>$industry_category_id))->find();
		$sl_data['industry_category_id']=$industry_category['industry_category_id'];
		$sl_data['industry_category_name']=$industry_category['industry_category_name'];
		$sl_data['industry_category_order']=$industry_category['industry_category_order'];
		$sl_data['industry_category_explain']=$industry_category['industry_category_explain'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改行业类别操作
    * shulan
    */
	public function industry_category_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/industry_category_list'));
		}
		else
		{
			$sl_data=array(
				'industry_category_id'=>input('industry_category_id'),
				'industry_category_name'=>input('industry_category_name'),
				'industry_category_order'=>input('industry_category_order',999),
				'industry_category_explain'=>input('industry_category_explain'),
			);
			$rst=Db::name('industry_category')->update($sl_data);
			if($rst!==false){
				$this->success('行业类别修改成功',url('admin/Sys/industry_category_list'));
			}else{
				$this->error('行业类别修改失败',url('admin/Sys/industry_category_list'));
			}
		}
	}
	/*
    * 行业类别排序
    * shulan
    */
	public function industry_category_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/industry_category_list'));
		}else{
			foreach (input('post.') as $industry_category_id => $industry_category_order){
				Db::name('industry_category')->where(array('industry_category_id' => $industry_category_id ))->setField('industry_category_order' , $industry_category_order);
			}
			$this->success('排序更新成功',url('admin/Sys/industry_category_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   行业类别   end



/////////////////////////////////////////////////////////////////////////   服务项目   start
	/*
    * admin/Sys/service_items_list
    *
    * 服务项目列表
    * shulan
    */
	public function service_items_list()
	{
		$service_items=Db::name('service_items')->order('service_items_order,service_items_id desc')->paginate(25);
		$page = $service_items->render();
		$this->assign('service_items',$service_items);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
    * 添加服务项目操作
    * shulan
    */
	public function service_items_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/service_items_list'));
		}else{
			$data=input('post.');
			Db::name('service_items')->insert($data);
			$this->success('服务项目添加成功',url('admin/Sys/service_items_list'));
		}
	}
	/*
    * 服务项目删除操作
    * shulan
    */
	public function service_items_del()
	{
		$p=input('p');
		$rst=Db::name('service_items')->where(array('service_items_id'=>input('service_items_id')))->delete();
		if($rst!==false){
			$this->success('服务项目删除成功',url('admin/Sys/service_items_list',array('p' => $p)));
		}else{
			$this->error('服务项目删除失败',url('admin/Sys/service_items_list',array('p' => $p)));
		}
	}
	/*
    * 服务项目修改返回值操作
    * shulan
    */
	public function service_items_edit()
	{
		$service_items_id=input('service_items_id');
		$service_items=Db::name('service_items')->where(array('service_items_id'=>$service_items_id))->find();
		$sl_data['service_items_id']=$service_items['service_items_id'];
		$sl_data['service_items_name']=$service_items['service_items_name'];
		$sl_data['service_items_order']=$service_items['service_items_order'];
		$sl_data['service_items_alia']=$service_items['service_items_alia'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改服务项目操作
    * shulan
    */
	public function service_items_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/service_items_list'));
		}else{
			$sl_data=array(
				'service_items_id'=>input('service_items_id'),
				'service_items_name'=>input('service_items_name'),
				'service_items_alia'=>input('service_items_alia'),
				'service_items_order'=>input('service_items_order',999),
			);
			$rst=Db::name('service_items')->update($sl_data);
			if($rst!==false){
				$this->success('服务项目修改成功',url('admin/Sys/service_items_list'));
			}else{
				$this->error('服务项目修改失败',url('admin/Sys/service_items_list'));
			}
		}
	}
	/*
    * 服务项目排序
    * shulan
    */
	public function service_items_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/service_items_list'));
		}else{
			foreach (input('post.') as $service_items_id => $service_items_order){
				Db::name('service_items')->where(array('service_items_id' => $service_items_id ))->setField('service_items_order' , $service_items_order);
			}
			$this->success('排序更新成功',url('admin/Sys/service_items_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   服务项目   end



/////////////////////////////////////////////////////////////////////////   项目对接人   start
	/*
    * admin/Sys/customer_waiter_list
    *
    * 项目对接人列表
    * shulan
    */
	public function customer_waiter_list()
	{
		$department = Db::name('department')->column('id,name');
		$this->assign('department',$department);

		$map['customer_waiter_id'] = array('neq',28);
		$customer_waiter=Db::name('customer_waiter')->where($map)->order('customer_waiter_order,customer_waiter_id desc')->paginate(25);
		$page = $customer_waiter->render();
		$this->assign('customer_waiter',$customer_waiter);
		$this->assign('page',$page);
		return $this->fetch();
	}

	/*
    * 添加项目对接人操作
    * shulan
    */
	public function customer_waiter_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/customer_waiter_list'));
		}else{
			$data=input('post.');
			Db::name('customer_waiter')->insert($data);
			$this->success('员工添加成功',url('admin/Sys/customer_waiter_list'));
		}
	}

	/*
    * 项目对接人删除操作
    * shulan
    */
	public function customer_waiter_del()
	{
		$p=input('p');
		$rst=Db::name('customer_waiter')->where(array('customer_waiter_id'=>input('customer_waiter_id')))->delete();
		if($rst!==false){
			$this->success('员工删除成功',url('admin/Sys/customer_waiter_list',array('p' => $p)));
		}else{
			$this->error('员工删除失败',url('admin/Sys/customer_waiter_list',array('p' => $p)));
		}
	}

	/*
    * 项目对接人修改返回值操作
    * shulan
    */
	public function customer_waiter_edit()
	{
		$department = Db::name('department')->column('id,name');
		$this->assign('department',$department);

		$customer_waiter_id=input('customer_waiter_id');
		$customer_waiter=Db::name('customer_waiter')->where(array('customer_waiter_id'=>$customer_waiter_id))->find();
		$sl_data['customer_waiter_id']=$customer_waiter['customer_waiter_id'];
		$sl_data['customer_waiter_name']=$customer_waiter['customer_waiter_name'];
		$sl_data['customer_waiter_order']=$customer_waiter['customer_waiter_order'];
		$sl_data['waiter_type']=$customer_waiter['waiter_type'];
		$sl_data['code']=1;
		return json($sl_data);
	}

	/**
	 * 修改项目对接人操作
	 */
	public function customer_waiter_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/customer_waiter_list'));
		}
		else
		{
			$sl_data=array(
				'customer_waiter_id'=>input('customer_waiter_id'),
				'customer_waiter_name'=>input('customer_waiter_name'),
				'waiter_type'=>input('waiter_type',1),
				'customer_waiter_order'=>input('customer_waiter_order',999),
			);
			$rst=Db::name('customer_waiter')->update($sl_data);
			if($rst!==false){
				$this->success('项目对接人修改成功',url('admin/Sys/customer_waiter_list'));
			}else{
				$this->error('项目对接人修改失败',url('admin/Sys/customer_waiter_list'));
			}
		}
	}

	/*
    * 项目对接人排序
    * shulan
    */
	public function customer_waiter_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_waiter_list'));
		}else{
			foreach (input('post.') as $customer_waiter_id => $customer_waiter_order){
				Db::name('customer_waiter')->where(array('customer_waiter_id' => $customer_waiter_id ))->setField('customer_waiter_order' , $customer_waiter_order);
			}
			$this->success('排序更新成功',url('admin/Sys/customer_waiter_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   项目对接人   end

/////////////////////////////////////////////////////////////////////////   合同周期详情   start
	/*
    * admin/Sys/customer_contract_cycle_list
    *
    *  合同周期列表
    * shulan
    */
	public function customer_contract_cycle_list()
	{
		$customer_contract_cycle=Db::name('customer_contract_cycle')->order('customer_contract_cycle_order,customer_contract_cycle_id desc')->paginate(25);
		$page = $customer_contract_cycle->render();
		$this->assign('customer_contract_cycle',$customer_contract_cycle);
		$this->assign('page',$page);
		return $this->fetch();
	}

	/*
    * 添加 合同周期操作
    * shulan
    */
	public function customer_contract_cycle_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_contract_cycle_list'));
		}else{
			$data=input('post.');
			Db::name('customer_contract_cycle')->insert($data);
			$this->success(' 合同周期添加成功',url('admin/Sys/customer_contract_cycle_list'));
		}
	}

	/*
    *  合同周期删除操作
    * shulan
    */
	public function customer_contract_cycle_del()
	{
		$p=input('p');
		$rst=Db::name('customer_contract_cycle')->where(array('customer_contract_cycle_id'=>input('customer_contract_cycle_id')))->delete();
		if($rst!==false){
			$this->success(' 合同周期删除成功',url('admin/Sys/customer_contract_cycle_list',array('p' => $p)));
		}else{
			$this->error(' 合同周期删除失败',url('admin/Sys/customer_contract_cycle_list',array('p' => $p)));
		}
	}

	/*
    *  合同周期修改返回值操作
    * shulan
    */
	public function customer_contract_cycle_edit()
	{
		$customer_contract_cycle_id=input('customer_contract_cycle_id');
		$customer_contract_cycle=Db::name('customer_contract_cycle')->where(array('customer_contract_cycle_id'=>$customer_contract_cycle_id))->find();
//		$sl_data['customer_contract_cycle_id']=$customer_contract_cycle['customer_contract_cycle_id'];
//		$sl_data['customer_contract_cycle_name']=$customer_contract_cycle['customer_contract_cycle_name'];
//		$sl_data['customer_contract_cycle_order']=$customer_contract_cycle['customer_contract_cycle_order'];
		$sl_data = $customer_contract_cycle;
		$sl_data['code']=1;
		return json($sl_data);
	}

	/*
    * 修改 合同周期操作
    * shulan
    */
	public function customer_contract_cycle_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/customer_contract_cycle_list'));
		}else{
			$sl_data=array(
				'customer_contract_cycle_id'=>input('customer_contract_cycle_id'),
				'customer_contract_cycle_name'=>input('customer_contract_cycle_name'),
				'customer_contract_cycle_order'=>input('customer_contract_cycle_order',999),
				'equivalent_months'=>input('equivalent_months'),
			);
			$rst=Db::name('customer_contract_cycle')->update($sl_data);
			if($rst!==false){
				$this->success(' 合同周期修改成功',url('admin/Sys/customer_contract_cycle_list'));
			}else{
				$this->error(' 合同周期修改失败',url('admin/Sys/customer_contract_cycle_list'));
			}
		}
	}

	/*
    *  合同周期排序
    * shulan
    */
	public function customer_contract_cycle_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_contract_cycle_list'));
		}else{
			foreach (input('post.') as $customer_contract_cycle_id => $customer_contract_cycle_order){
				Db::name('customer_contract_cycle')->where(array('customer_contract_cycle_id' => $customer_contract_cycle_id ))->setField('customer_contract_cycle_order' , $customer_contract_cycle_order);
			}
			$this->success('排序更新成功',url('admin/Sys/customer_contract_cycle_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////    合同周期   end



/////////////////////////////////////////////////////////////////////////   回款类型详情   start
	/*
    * admin/Sys/customer_payment_type_list
    *
    *  回款类型列表
    * shulan
    */
	public function customer_payment_type_list()
	{
		$customer_payment_type=Db::name('customer_payment_type')->order('customer_payment_type_order,customer_payment_type_id desc')->paginate(25);
		$page = $customer_payment_type->render();
		$this->assign('customer_payment_type',$customer_payment_type);
		$this->assign('page',$page);
		return $this->fetch();
	}

	/*
    * 添加 回款类型操作
    * shulan
    */
	public function customer_payment_type_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_payment_type_list'));
		}else{
			$data=input('post.');

//			能够添加的都不是自定义     2
			$data['is_custom'] = 2;

			Db::name('customer_payment_type')->insert($data);
			$this->success(' 回款类型添加成功',url('admin/Sys/customer_payment_type_list'));
		}
	}

	/*
    *  回款类型删除操作
    * shulan
    */
	public function customer_payment_type_del()
	{
		$p=input('p');
		$rst=Db::name('customer_payment_type')->where(array('customer_payment_type_id'=>input('customer_payment_type_id')))->delete();
		if($rst!==false){
			$this->success(' 回款类型删除成功',url('admin/Sys/customer_payment_type_list',array('p' => $p)));
		}else{
			$this->error(' 回款类型删除失败',url('admin/Sys/customer_payment_type_list',array('p' => $p)));
		}
	}

	/*
    *  回款类型修改返回值操作
    * shulan
    */
	public function customer_payment_type_edit()
	{
		$customer_payment_type_id=input('customer_payment_type_id');
		$customer_payment_type=Db::name('customer_payment_type')->where(array('customer_payment_type_id'=>$customer_payment_type_id))->find();
		$sl_data = $customer_payment_type;
		$sl_data['code']=1;

		return json($sl_data);
	}

	/*
    * 修改 回款类型操作
    * shulan
    */
	public function customer_payment_type_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/customer_payment_type_list'));
		}else{
			$sl_data=array(
				'customer_payment_type_id'=>input('customer_payment_type_id'),
				'customer_payment_type_name'=>input('customer_payment_type_name'),
				'customer_payment_type_order'=>input('customer_payment_type_order',999),

				'number_of_periods'=>input('number_of_periods'),
				'amount_proportion'=>input('amount_proportion'),
				'time_scale'=>input('time_scale'),

			);
			$rst=Db::name('customer_payment_type')->update($sl_data);
			if($rst!==false){
				$this->success(' 回款类型修改成功',url('admin/Sys/customer_payment_type_list'));
			}else{
				$this->error(' 回款类型修改失败',url('admin/Sys/customer_payment_type_list'));
			}
		}
	}

	/*
    *  回款类型排序
    * shulan
    */
	public function customer_payment_type_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/customer_payment_type_list'));
		}else{
			foreach (input('post.') as $customer_payment_type_id => $customer_payment_type_order){
				Db::name('customer_payment_type')->where(array('customer_payment_type_id' => $customer_payment_type_id ))->setField('customer_payment_type_order' , $customer_payment_type_order);
			}
			$this->success('排序更新成功',url('admin/Sys/customer_payment_type_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////    回款类型   end


/////////////////////////////////////////////////////////////////////////   回款详情   start
//技术部     seo  aso   网建开发       (1,4,5)
//营销        sem                      (2)
//电商                                 (7)
//品牌部      新媒体运营               (6)


	/**
	 * 技术部
	 * @return mixed
	 */
	public function back_payment_list1()
	{
		$this->_actual_total_amount(2);
		$this->_get_customer();
		$this->_get_service_item();
		$back_payment=Db::name('customer_payment_back')->where('service_items','between','1,4,5')->order('back_payment_id asc')->paginate(18);
		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
	}

	/**
	 * 营销部
	 * @return mixed
	 */
	public function back_payment_list2()
	{
		$this->_actual_total_amount(2);
		$this->_get_customer();
		$this->_get_service_item();
		$back_payment=Db::name('customer_payment_back')->where('service_items','between','2')->order('back_payment_id asc')->paginate(18);
		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
	}

	/**
	 * 电商部
	 * @return mixed
	 */
	public function back_payment_list3()
	{
		$this->_actual_total_amount(2);
		$this->_get_customer();
		$this->_get_service_item();
		$back_payment=Db::name('customer_payment_back')->where('service_items','between','7')->order('back_payment_id asc')->paginate(18);
		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
	}

	/**
	 * 品牌部
	 * @return mixed
	 */
	public function back_payment_list4()
	{
		$this->_actual_total_amount(6);
		$this->_get_customer();
		$this->_get_service_item();
		$back_payment=Db::name('customer_payment_back')->where('service_items','between','7')->order('back_payment_id asc')->paginate(18);
		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
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
		if($aa[1] != 12)
		{
			$cc = $bb[0].'-'.($bb[1]+1);
		}
		else
		{
			$cc = ($bb[0]+1).'-1';
		}
		return ['start' => strtotime($aa),'end' => strtotime($cc)];
	}

	/*
    * admin/Sys/back_payment_list
    *
    *  回款列表
    * shulan
    */
	public function back_payment_list()
	{
		$this->_get_waiter();
		$this->_get_customer();
		$this->_get_service_item();

		$date_condition = $this->get_date();
		$back_payment=Db::name('customer_payment_back')
			->where('back_payment_time','between',"$date_condition[start],$date_condition[end]")
			->order('back_payment_time asc')->paginate(18);

		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
	}


	/*
    * admin/Sys/back_payment_list
    *
    *  回款列表
    * shulan
    */
	public function back_payment_list_backup()
	{
		$this->_actual_total_amount(2);
		$this->_get_customer();
		$this->_get_service_item();
		$back_payment=Db::name('customer_payment_back')->order('back_payment_id asc')->paginate(18);
		$page = $back_payment->render();
		$this->assign('back_payment',$back_payment);
		$this->assign('page',$page);

		return $this->fetch();
	}

	/*
    * 添加 回款列表操作
    * shulan
    */
	public function back_payment_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/back_payment_list'));
		}else{
			$data=input('post.');

//			能够添加的都不是自定义     2
			$data['is_custom'] = 2;

			Db::name('back_payment')->insert($data);
			$this->success(' 回款添加成功',url('admin/Sys/back_payment_list'));
		}
	}

	/*
    *  回款列表删除操作
    * shulan
    */
	public function back_payment_del()
	{
		$p=input('p');
		$rst=Db::name('customer_payment_back')->where(array('back_payment_id'=>input('back_payment_id')))->delete();
		if($rst!==false){
			$this->success(' 回款删除成功',url('admin/Sys/back_payment_list',array('p' => $p)));
		}else{
			$this->error(' 回款删除失败',url('admin/Sys/back_payment_list',array('p' => $p)));
		}
	}

	/*
    * 回款列表修改
    * shulan
    */
	public function back_payment_edit()
	{
		$back_payment_id=input('back_payment_id');
		$back_payment=Db::name('customer_payment_back')->where(array('back_payment_id'=>$back_payment_id))->find();

//		获得项目名
		$customer_title = db('customer')->where(array('n_id'=>$back_payment['customer_id']))->column('customer_title');

		$back_payment['customer_id'] = $customer_title[0];
		$back_payment['back_payment_time'] = date('Y-m',$back_payment['back_payment_time']);
		if(!$back_payment['actual_back_payment_time'])
		{
			$back_payment['actual_back_payment_time']= '';
		}
		else
		{
			$back_payment['actual_back_payment_time'] = date('Y-m-d',$back_payment['actual_back_payment_time']);
		}

		$sl_data = $back_payment;
		$sl_data['code']=1;
		return json($sl_data);
	}


	/*
    * 回款详情
    * shulan
    */
	public function back_payment_detail()
	{
		$customer_id=input('customer_id');
		$back_payment = Db::name('customer_payment_back')->where(array('customer_id'=>$customer_id))->order('back_payment_id')->select();
		$customer_info = Db::name('customer')->column('n_id,customer_title');

		$per_payment = $this->_get_customer();

		$str = '
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
									<th>操作</th>
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

			$str .= '<tr>
						<td>'.$customer_info[$row['customer_id']].'</td>
						<td>第'.$row['number_of_periods'].'期</td>
						<td>'.$row['amount_of_money'].$per_payment[$row['customer_id']].'</td>
						<td>'.date('Y-m-d',$row['back_payment_time']).'</td>
						'.$actual_amount_of_money.'
						<td>'.$row['actual_amount_of_money'].'</td>
						<td>'.$row['remark'].'</td>
						<td>
				<div class="hidden-sm hidden-xs action-buttons">
											<a class="back_paymentedit-btn3" href="/admin/Sys/back_payment_edit_info" data-id="'.$row['back_payment_id'].'" >
												<i class="ace-icon fa fa-pencil bigger-130"></i>
											</a>
										</div>
			</td>
					</tr>';
		}

		$str .= '
								</tbody>
							</table>';


		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}


	/*
    *  回款详情
    * shulan
    */
	public function back_payment_detail_1()
	{
		$customer_id=input('customer_id');
		$back_payment = Db::name('customer_payment_back')->where(array('customer_id'=>$customer_id))->order('back_payment_id')->select();
		$customer_info = Db::name('customer')->column('n_id,customer_title');

		$per_payment = $this->_get_customer();

		$str = '
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
									<th>操作</th>
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

			$str .= '<tr>
						<td>'.$customer_info[$row['customer_id']].'</td>
						<td>第'.$row['number_of_periods'].'期</td>
						<td>'.$row['amount_of_money'].$per_payment[$row['customer_id']].'</td>
						<td>'.date('Y-m-d',$row['back_payment_time']).'</td>
						'.$actual_amount_of_money.'
						<td>'.$row['actual_amount_of_money'].'</td>
						<td>'.$row['remark'].'</td>
						<td>
				<div class="hidden-sm hidden-xs action-buttons">
											<a class="back_paymentedit-btn3" href="/admin/Sys/back_payment_edit_info_1" data-id="'.$row['back_payment_id'].'" >
												<i class="ace-icon fa fa-pencil bigger-130"></i>
											</a>
										</div>
			</td>
					</tr>';
		}

		$str .= '
								</tbody>
							</table>';


		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}

	/*
    *  回款详情
    * shulan
    */
	public function back_payment_edit_info()
	{
		$back_payment_id=input('back_payment_id');

		$back_payment = Db::name('customer_payment_back')->find($back_payment_id);

//		$per_payment = $this->_get_customer();


		$str = '
			<form class="form-horizontal ajaxForm2" name="customer_status_runedit" method="post" action="/admin/Sys/customer_status_runedit">
				<input type="hidden" name="back_payment_id" id="editback_payment_id" value="'.$back_payment['back_payment_id'].'" />
						<div class="modal-body">
							<div class="row">
								<div class="col-xs-12">

									<div class="form-group">
										<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> 实收金额：  </label>
										<div class="col-sm-10">
											<input type="text" name="actual_amount_of_money" id="editactual_amount_of_money" value="'.$back_payment['actual_amount_of_money'].'" class="col-xs-10 col-sm-5" />
										</div>
									</div>
									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> 备注：  </label>
										<div class="col-sm-10">
											<input type="text" name="remark" id="editremark" value="'.$back_payment['remark'].'" class="col-xs-10 col-sm-5" />
										</div>
									</div>
									<div class="space-4"></div>

								</div>
							</div>
						</div>
						<div class="modal-body">
							<div class="row">
								<div class="col-xs-12">
									<div class="form-group">
										<div class="col-sm-10" style="margin-left:250px;">
											<button type="submit" class="btn btn-primary">
											提交保存
											</button>
										</div>
									</div>
									<div class="space-4"></div>
								</div>
							</div>
						</div>
			</form>';

		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}

	/*
    * 修改 回款列表操作
    * shulan
    */
	public function back_payment_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Customer/customer_list'));
		}
		else
		{
			$sl_data=array(
				'amount_of_money'=>input('amount_of_money'),
				'back_payment_time'=>strtotime(input('back_payment_time')),
				'actual_back_payment_time'=> strtotime(input('actual_back_payment_time')),
				'total_amount'=>input('total_amount'),
				'actual_amount_of_money'=>input('actual_amount_of_money'),
				'total_number_of_periods'=>input('total_number_of_periods'),

				'back_payment_id'=>input('back_payment_id'),
				'remark'=>input('remark'),
			);

			$sl_data = $this->_do_money_companyback($sl_data);
			$rst=Db::name('customer_payment_back')->update($sl_data);
			if($rst!==false)
			{
				$this->_actual_total_amount(input('back_payment_id'));
				$this->success('回款修改成功',url('admin/Customer/customer_list'));
			}
			else
			{
				$this->error(' 回款修改失败',url('admin/Customer/customer_list'));
			}
		}
	}



//'amount_of_money' => '8000',
//'back_payment_time' => 1504195200,
//'actual_back_payment_time' => false,
//'total_amount' => '27265',
//'actual_amount_of_money' => '0',
//'total_number_of_periods' => '1',
//'back_payment_id' => '72',
//'remark' => '',

//    	customer_cpprice

	/**
	 *  改变单个的应回金额时，总金额的变化
	 *
	 * @param $sl_data
	 * @return mixed
	 * @throws \think\Exception
	 */
	private function _do_money_companyback($sl_data)
	{
		$amount_of_money_new = $sl_data['amount_of_money'];
		$map = [
			'back_payment_id' => $sl_data['back_payment_id']
		];
		$amount_of_money_last = Db::name('customer_payment_back')->where($map)->value('amount_of_money');
		$num = $amount_of_money_new - $amount_of_money_last;

//		相等
		if($num == 0)
		{
			return $sl_data;
		}
		//总额减少了
		elseif($num < 0)
		{
			$num = $amount_of_money_last - $amount_of_money_new;
			$sl_data['total_amount'] = $sl_data['total_amount'] - $num;


			//客户回款表
			$data['n_id'] = $amount_of_money_last = Db::name('customer_payment_back')->where($map)->value('customer_id');
			$data['customer_cpprice'] = $sl_data['total_amount'];
			Db::name('customer')->update($data);

			//回款详情表
			$data_2['customer_id'] = $data['n_id'];
			$data_3['total_amount'] = $sl_data['total_amount'];
			Db::name('customer_payment_back')->where($data_2)->update($data_3);


			return $sl_data;
		}
		//总额增加了
		else
		{
			$sl_data['total_amount'] = $sl_data['total_amount'] + $num;

			$data['n_id'] = $amount_of_money_last = Db::name('customer_payment_back')->where($map)->value('customer_id');
			$data['customer_cpprice'] = $sl_data['total_amount'];
			Db::name('customer')->update($data);

			//回款详情表
			$data_2['customer_id'] = $data['n_id'];
			$data_3['total_amount'] = $sl_data['total_amount'];
			Db::name('customer_payment_back')->where($data_2)->update($data_3);

			return $sl_data;
		}
	}

	/*
    *  回款排序
    * shulan
    */
	public function back_payment_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/back_payment_list'));
		}else{
			foreach (input('post.') as $back_payment_id => $back_payment_order){
				Db::name('back_payment')->where(array('back_payment_id' => $back_payment_id ))->setField('back_payment_order' , $back_payment_order);
			}
			$this->success('排序更新成功',url('admin/Sys/back_payment_list'));
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
	 7	电商

	6	新媒体运营

	5	网建开发

	4	aso

	2	sem

	1	seo
	 */

	//获得对接人
	private function _get_waiter()
	{
//		$customer_waiter_1 = Db::name('customer')->column('n_id,customer_waiter_1');
//		$customer_waiter_2 = Db::name('customer')->column('n_id,customer_waiter_2');
//		$this->assign('customer_waiter_1',$customer_waiter_1);
//		$this->assign('customer_waiter_2',$customer_waiter_2);

		$waiter_info = Db::name('customer_waiter')->column('customer_waiter_id,customer_waiter_name');
		$this->assign('waiter_info',$waiter_info);
	}


	/*
    *  回款详情
    * shulan
    */
	public function back_payment_edit_info_1()
	{
		$back_payment_id=input('back_payment_id');

		$back_payment = Db::name('customer_payment_back')->find($back_payment_id);

//		$per_payment = $this->_get_customer();


		$str = '
			<form class="form-horizontal ajaxForm2" name="customer_status_runedit" method="post" action="/admin/Sys/customer_status_runedit_1">
				<input type="hidden" name="back_payment_id" id="editback_payment_id" value="'.$back_payment['back_payment_id'].'" />
						<div class="modal-body">
							<div class="row">
								<div class="col-xs-12">

									<div class="form-group">
										<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> 实收金额：  </label>
										<div class="col-sm-10">
											<input type="text" name="actual_amount_of_money" id="editactual_amount_of_money" value="'.$back_payment['actual_amount_of_money'].'" class="col-xs-10 col-sm-5" />
										</div>
									</div>
									<div class="space-4"></div>

									<div class="form-group">
										<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> 备注：  </label>
										<div class="col-sm-10">
											<input type="text" name="remark" id="editremark" value="'.$back_payment['remark'].'" class="col-xs-10 col-sm-5" />
										</div>
									</div>
									<div class="space-4"></div>

								</div>
							</div>
						</div>
						<div class="modal-body">
							<div class="row">
								<div class="col-xs-12">
									<div class="form-group">
										<div class="col-sm-10" style="margin-left:250px;">
											<button type="submit" class="btn btn-primary">
											提交保存
											</button>
										</div>
									</div>
									<div class="space-4"></div>
								</div>
							</div>
						</div>
			</form>';

		$sl_data['content'] = $str;
		$sl_data['code']=1;
		return json($sl_data);
	}

/////////////////////////////////////////////////////////////////////////    回款   end



/////////////////////////////////////////////////////////////////////////   客户等级   start
	/*
    * admin/Sys/cust_level_list
    *
    *客户等级列表
    * shulan
    */
	public function cust_level_list()
	{
		$cust_level=Db::name('cust_level')->order('cust_level_order,cust_level_id desc')->paginate(config('paginate.list_rows'));

		$page = $cust_level->render();
		$this->assign('cust_level',$cust_level);
		$this->assign('page',$page);

		return $this->fetch();
	}
	/*
    * 添加客户等级操作
    * shulan
    */
	public function cust_level_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/cust_level_list'));
		}
		else
		{
			$data=input('post.');
			Db::name('cust_level')->insert($data);
			$this->success('客户等级添加成功',url('admin/Sys/cust_level_list'));
		}
	}
	/*
    * 客户等级删除操作
    * shulan
    */
	public function cust_level_del()
	{
		$p=input('p');
		$rst=Db::name('cust_level')->where(array('cust_level_id'=>input('cust_level_id')))->delete();
		if($rst!==false){
			$this->success('客户等级删除成功',url('admin/Sys/cust_level_list',array('p' => $p)));
		}else{
			$this->error('客户等级删除失败',url('admin/Sys/cust_level_list',array('p' => $p)));
		}
	}
	/*
    * 客户等级修改返回值操作
    * shulan
    */
	public function cust_level_edit()
	{
		$cust_level_id=input('cust_level_id');
		$cust_level=Db::name('cust_level')->where(array('cust_level_id'=>$cust_level_id))->find();
		$sl_data['cust_level_id']=$cust_level['cust_level_id'];
		$sl_data['cust_level_name']=$cust_level['cust_level_name'];
		$sl_data['cust_level_order']=$cust_level['cust_level_order'];
		$sl_data['cust_level_explain']=$cust_level['cust_level_explain'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改客户等级操作
    * shulan
    */
	public function cust_level_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/cust_level_list'));
		}else{
			$sl_data=array(
				'cust_level_id'=>input('cust_level_id'),
				'cust_level_name'=>input('cust_level_name'),
				'cust_level_order'=>input('cust_level_order',999),
				'cust_level_explain'=>input('cust_level_explain'),
			);
			$rst=Db::name('cust_level')->update($sl_data);
			if($rst!==false){
				$this->success('客户等级修改成功',url('admin/Sys/cust_level_list'));
			}else{
				$this->error('客户等级修改失败',url('admin/Sys/cust_level_list'));
			}
		}
	}
	/*
    * 客户等级排序
    * shulan
    */
	public function cust_level_order()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/cust_level_list'));
		}else{
			foreach (input('post.') as $cust_level_id => $cust_level_order){
				Db::name('cust_level')->where(array('cust_level_id' => $cust_level_id ))->setField('cust_level_order' , $cust_level_order);
			}
			$this->success('排序更新成功',url('admin/Sys/cust_level_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   客户等级   end







/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////服务项目列表///////////////
	/**
	 * 服务项目列表
	servicee_itemse
	 */
	public function servicee_itemse_list()
	{
		$pid=input('pid',0);
		$level=input('level',0);
		$id_str=input('service_items_id','pid');
		$servicee_itemse=Db::name('service_items')->where('pid',$pid)->select();
		$servicee_itemse_all=Db::name('service_items')->select();
		$arr = menu_left($servicee_itemse,'service_items_id','pid','─',$pid,$level,$level*20);
		$arr_all = menu_left($servicee_itemse_all,'service_items_id','pid','─',0,$level,$level*20);

		$this->assign('servicee_itemse',$arr);
		$this->assign('servicee_itemse_all',$arr_all);
		$this->assign('pid',$id_str);

		if(request()->isAjax())
		{
			return $this->fetch('ajax_servicee_itemse_list');
		}else{
			return $this->fetch();
		}
	}

	/**
	 * 服务项目添加
	 */
	public function servicee_itemse_add()
	{
		$pid=input('pid',0);

		//全部规则 (只摘录 父一级的）
		$servicee_itemse_all=Db::name('service_items')->where('level',1)->select();
		$arr = menu_left($servicee_itemse_all,'service_items_id');
		$this->assign('servicee_itemse',$arr);
		$this->assign('pid',$pid);
		return $this->fetch();
	}

	/**
	 * 服务项目添加操作
	 */
	public function servicee_itemse_runadd()
	{
		if(!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/servicee_itemse_list'));
		}
		else
		{
			$pid=Db::name('service_items')->where(array('service_items_id'=>input('pid')))->field('level')->find();
			$level=$pid['level']+1;    //获得级别

			$sldata=array(
				'service_items_name'=>input('service_items_name'),
				'pid'=>input('pid'),
				'addtime'=>time(),
				'level'=>$level,
			);
			Db::name('service_items')->insert($sldata);
			Cache::clear();
			$this->success('服务项目添加成功',url('admin/Sys/servicee_itemse_list'),1);
		}
	}

	/**
	 * 服务项目编辑
	 */
	public function servicee_itemse_edit()
	{
		//全部规则
		$servicee_itemse_all=Db::name('service_items')->where('level',1)->select();
		$arr = menu_left($servicee_itemse_all,'service_items_id');
		$this->assign('servicee_itemse',$arr);
		//待编辑规则

		$servicee_itemse=Db::name('service_items')->where(array('service_items_id'=>input('id')))->find();
		$this->assign('rule',$servicee_itemse);
		return $this->fetch();
	}

	/**
	 * 服务项目编辑操作
	 */
	public function servicee_itemse_runedit()
	{
		if(!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/servicee_itemse_list'));
		}
		else
		{
			$old_pid=input('old_pid');
			$old_level=input('old_level',0,'intval');
			$pid=input('pid');
			$level_diff=0;
			//判断是否更改了pid
			if($pid!=$old_pid)
			{
				$level=Db::name('service_items')->where('service_items_id',$pid)->value('level')+1;
				$level_diff=($level>$old_level)?($level-$old_level):($old_level-$level);
			}
			else
			{
				$level=$old_level;
			}

			$sldata=array(
				'service_items_id'=>input('id',1,'intval'),
				'service_items_name'=>input('service_items_name'),
				'pid'=>input('pid',0,'intval'),
				'level'=>$level
			);
			$rst=Db::name('service_items')->update($sldata);

			if($rst!==false)
			{
				if($pid!=$old_pid)
				{
					//更新子孙级菜单的level
					$auth_rule=Db::name('service_items')->select();
					$tree=new \Tree();
					$tree->init($auth_rule,['parentid'=>'pid']);
					$ids=$tree->get_childs($auth_rule,$sldata['service_items_id'],true,false);
					if($ids)
					{
						if($level>$old_level){
							Db::name('service_items')->where('service_items_id','in',$ids)->setInc('level',$level_diff);
						}else{
							Db::name('service_items')->where('service_items_id','in',$ids)->setDec('level',$level_diff);
						}
					}
				}
				Cache::clear();
				$this->success('服务项目修改成功',url('admin/Sys/servicee_itemse_list'));
			}else{
				$this->error('服务项目修改失败',url('admin/Sys/servicee_itemse_list'));
			}
		}
	}

	/**
	 * 服务项目删除
	 */
	public function servicee_itemse_del()
	{
		$pid=input('id');
		$child_id = db('service_items')->where('pid',$pid)->column('service_items_id');
		if($child_id)
		{
			array_push($child_id,$pid);
		}
		else
		{
			$child_id[] = $pid;
		}
		if($child_id){
			$rst=Db::name('service_items')->where('service_items_id','in',$child_id)->delete();
			if($rst!==false){
				Cache::clear();
				$this->success('服务项目删除成功',url('admin/Sys/servicee_itemse_list'));
			}else{
				$this->error('服务项目删除失败',url('admin/Sys/servicee_itemse_list'));
			}
		}else{
			$this->error('服务项目删除失败',url('admin/Sys/servicee_itemse_list'));
		}
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////服务项目列表///////////////




	/////////////////////////////////////////////////////////////////////////   客户信用级别   start
	/*
    * admin/Sys/cust_jibie_list
    *
    *客户信用级别列表
    * shulan
    */
	public function cust_jibie_list()
	{
		$cust_jibie=Db::name('cust_jibie')->order('cust_jibie_id desc')->paginate(config('paginate.list_rows'));

		$page = $cust_jibie->render();
		$this->assign('cust_jibie',$cust_jibie);
		$this->assign('page',$page);

		return $this->fetch();
	}
	/*
    * 添加客户信用级别操作
    * shulan
    */
	public function cust_jibie_runadd()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/cust_jibie_list'));
		}
		else
		{
			$data=input('post.');
			Db::name('cust_jibie')->insert($data);
			$this->success('客户信用级别添加成功',url('admin/Sys/cust_jibie_list'));
		}
	}
	/*
    * 客户信用级别删除操作
    * shulan
    */
	public function cust_jibie_del()
	{
		$p=input('p');
		$rst=Db::name('cust_jibie')->where(array('cust_jibie_id'=>input('cust_jibie_id')))->delete();
		if($rst!==false){
			$this->success('客户信用级别删除成功',url('admin/Sys/cust_jibie_list',array('p' => $p)));
		}else{
			$this->error('客户信用级别删除失败',url('admin/Sys/cust_jibie_list',array('p' => $p)));
		}
	}
	/*
    * 客户信用级别修改返回值操作
    * shulan
    */
	public function cust_jibie_edit()
	{
		$cust_jibie_id=input('cust_jibie_id');
		$cust_jibie=Db::name('cust_jibie')->where(array('cust_jibie_id'=>$cust_jibie_id))->find();
		$sl_data['cust_jibie_id']=$cust_jibie['cust_jibie_id'];
		$sl_data['cust_jibie_name']=$cust_jibie['cust_jibie_name'];
		$sl_data['cust_jibie_explain']=$cust_jibie['cust_jibie_explain'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改客户信用级别操作
    * shulan
    */
	public function cust_jibie_runedit()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/cust_jibie_list'));
		}else{
			$sl_data=array(
				'cust_jibie_id'=>input('cust_jibie_id'),
				'cust_jibie_name'=>input('cust_jibie_name'),
				'cust_jibie_explain'=>input('cust_jibie_explain'),
			);
			$rst=Db::name('cust_jibie')->update($sl_data);
			if($rst!==false){
				$this->success('客户信用级别修改成功',url('admin/Sys/cust_jibie_list'));
			}else{
				$this->error('客户信用级别修改失败',url('admin/Sys/cust_jibie_list'));
			}
		}
	}
/////////////////////////////////////////////////////////////////////////   客户信用级别   end







/////////////////////////////////////////////////////////////////////////   部门管理   start
	/*
    * admin/Sys/department_list
    *
    * 部门管理列表
    * shulan
    */
	public function department_list()
	{
		$department=Db::name('department')->order('sort,id desc')->paginate(config('paginate.list_rows'));
		$page = $department->render();
		$this->assign('department',$department);
		$this->assign('page',$page);
		return $this->fetch();
	}
	/*
    * 添加部门管理操作
    * shulan
    */
	public function department_runadd()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/department_list'));
		}else{
			$data=input('post.');
			$data['sort'] = 999;
			Db::name('department')->insert($data);
			$this->success('部门管理添加成功',url('admin/Sys/department_list'));
		}
	}
	/*
    * 部门管理删除操作
    * shulan
    */
	public function department_del()
	{
		$p=input('p');
		$rst=Db::name('department')->where(array('id'=>input('id')))->delete();
		if($rst!==false){
			$this->success('部门管理删除成功',url('admin/Sys/department_list',array('p' => $p)));
		}else{
			$this->error('部门管理删除失败',url('admin/Sys/department_list',array('p' => $p)));
		}
	}
	/*
    * 部门管理修改返回值操作
    * shulan
    */
	public function department_edit()
	{
		$id=input('id');
		$department=Db::name('department')->where(array('id'=>$id))->find();
		$sl_data['id']=$department['id'];
		$sl_data['name']=$department['name'];
		$sl_data['sort']=$department['sort'];
		$sl_data['code']=1;
		return json($sl_data);
	}
	/*
    * 修改部门管理操作
    * shulan
    */
	public function department_runedit()
	{
		if (!request()->isAjax())
		{
			$this->error('提交方式不正确',url('admin/Sys/department_list'));
		}
		else
		{
			$sl_data=array(
				'id'=>input('id'),
				'name'=>input('name'),
				'sort'=>input('sort',999),
			);

			$rst=Db::name('department')->update($sl_data);
			if($rst!==false){
				$this->success('部门管理修改成功',url('admin/Sys/department_list'));
			}else{
				$this->error('部门管理修改失败',url('admin/Sys/department_list'));
			}
		}
	}
	/*
    * 部门管理排序
    * shulan
    */
	public function sort()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确',url('admin/Sys/department_list'));
		}else{
			foreach (input('post.') as $id => $sort){
				Db::name('department')->where(array('id' => $id ))->setField('sort' , $sort);
			}
			$this->success('排序更新成功',url('admin/Sys/department_list'));
		}
	}
/////////////////////////////////////////////////////////////////////////   部门管理   end


}