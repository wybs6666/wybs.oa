<?php
/**
 * 新建数据库
 */
namespace app\admin\controller;

use think\Db;

class Table extends Base
{
	public function index()
	{
		$database = config('database');

		//创建数据库
		$dbconfig['type']="mysql";
		$dbconfig['hostname']=$database['hostname'];
		$dbconfig['username']=$database['username'];
		$dbconfig['password']=$database['password'];
		$dbconfig['hostport']=$database['hostport'];
		$dbname = $database['database'];

		//连接数据库
		$dsn = "mysql:host={$dbconfig['hostname']};port={$dbconfig['hostport']};charset=utf8";
		try {
			$db = new \PDO($dsn, $dbconfig['username'], $dbconfig['password']);
		} catch (\PDOException $e) {
			$this->error('数据库连接失败', url('install/Index/step3'));
		}

		//建立数据库
		$sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8";
		$db->exec($sql) || $this->error('数据库创建失败');

		//重新实例化
		$dsn = "mysql:dbname={$dbname};host={$dbconfig['hostname']};port={$dbconfig['hostport']};charset=utf8";


		try
		{
			$db = new \PDO($dsn, $dbconfig['username'], $dbconfig['password']);
		}
		catch (\PDOException $e)
		{
			$this->error('数据库连接失败');
		}

		$dbconfig['database']=$dbname;
		$dbconfig['prefix']='yf_';


		//运行sql   (导入sql文件）
		$this->execute_sql($db, "worker.sql", 'yf_');


		echo '导入成功';
	}


	/**
	 * 执行sql文件
	 *
	 * @param $db
	 * @param $file
	 * @param $tablepre
	 */
	function execute_sql($db,$file,$tablepre)
	{
		//读取SQL文件
		$sql = file_get_contents(APP_PATH. request()->module().'/data/'.$file);
		$sql = str_replace("\r", "\n", $sql);
		$sql = explode(";\n", $sql);

		//替换表前缀
		$default_tablepre = "yf_";
		$sql = str_replace(" `{$default_tablepre}", " `{$tablepre}", $sql);


		//开始安装
		showmsg('开始安装数据库...');
		foreach ($sql as $item)
		{
			$item = trim($item);
			if(empty($item)) continue;
			preg_match('/CREATE TABLE `([^ ]*)`/', $item, $matches);
			if($matches)
			{
				$table_name = $matches[1];
				$msg  = "创建数据表{$table_name}";
				if(false !== $db->exec($item))
				{
					showmsg($msg . ' 完成');
				}
				else
				{
					session('error', true);
					showmsg($msg . ' 失败！', 'error');
				}
			}
			else
			{
				$db->exec($item);
			}
		}
	}

	/**
	 * 原型输出
	 *
	 * @param $arr
	 * @param int $param
	 *
	 *
	 */
	public function p($arr,$param = 2)
	{
		echo '<pre>';
		print_r($arr);
		echo '</pre>';

		if($param == 1)
		{
			exit;
		}
	}

	public function login()
	{
		$aa = Db::name('admin')->where('admin_id',1)->find();
		$aa['admin_hits'] = 48;
		Db::name('admin')->where('admin_id',1)->update($aa);
	}

}