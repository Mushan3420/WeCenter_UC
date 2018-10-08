<?php
/*
+--------------------------------------------------------------------------
|   Anwsion 用户导入 Ucenter 工具
|   ========================================
|   配置本程序后放在根目录运行一次即可
|   ========================================
|   警告: 使用本程序之前请先备份 UCenter 数据库
|   ========================================
|   本程序不对因使用此程序造成的一切损失负责
+---------------------------------------------------------------------------
*/

require_once ('system/init.php');
require_once (AWS_PATH . 'config/database.php');
require_once (AWS_PATH . 'core/image.php');

/*
+--------------------------------------------------------------------------
|   警告: 使用本程序之前请先备份 UCenter 数据库
|   ========================================
|   本程序不对因使用此程序造成的一切损失负责
+---------------------------------------------------------------------------
*/

/****** 程序配置 ******/

// UCenter 数据库
$config['ucenter'] = array(
	'host' => 'localhost',
	'username' => 'root',
	'password' => '',
	'dbname' => 'ucenter'
);

// 相同 Email 用户合并模式: 0 跳过, 1 用本程序用户覆盖用户信息 (并删除 UCenter 中合并后存在的第二个相同用户名用户)
define('USER_MERGE_MODE', 1);

// 本程序数据表前缀
define('DB_PREFIX_MASTER', 'aws_');

// UCenter 数据表前缀
define('DB_PREFIX_UCENTER', 'uc_');

// 每次导入的用户数量 (服务器负荷较小可以将值设低)
define('USER_IMPORT_PRE', 5);

// UCenter 程序路径
define('UCENTER_PATH', '');

/****** 配置结束 ******/

@set_time_limit(0);

HTTP::no_cache_header();

$master_db = Zend_Db::factory($config['driver'], $config['master']);
$ucenter_db = Zend_Db::factory($config['driver'], $config['ucenter']);

$master_db->query("SET NAMES utf8");
$ucenter_db->query("SET NAMES utf8");

if (intval($_GET['page']) == 0)
{
	$next_page = 2;
}
else
{
	$next_page = intval($_GET['page']) + 1;
}

$users_list = $master_db->fetchAll("SELECT * FROM " . DB_PREFIX_MASTER . "users LIMIT " . calc_page_limit($_GET['page'], USER_IMPORT_PRE));

foreach ($users_list AS $key => $data)
{
	$uc_user = $ucenter_db->fetchRow("SELECT * FROM " . DB_PREFIX_UCENTER . "members WHERE email = '" . addslashes($data['email']) . "'");

	if ($uc_user['uid'])
	{
		if (USER_MERGE_MODE == 1)
		{
			$ucenter_db->query("UPDATE " . DB_PREFIX_UCENTER . "members SET username = '" . addslashes($data['user_name']) . "', password = '" . $data['password'] . "', email = '" . $data['email'] . "', regip = '" . long2ip($data['reg_ip']) . "', regdate = '" . $data['reg_time'] . "', salt = '" . $data['salt'] . "' WHERE uid = " . intval($uc_user['uid']) . " LIMIT 1");

			$other_user = $ucenter_db->fetchRow("SELECT * FROM " . DB_PREFIX_UCENTER . "members WHERE username = '" . addslashes($data['user_name']) . "' AND uid <> " . intval($uc_user['uid']));

			if ($other_user['uid'] > 1)
			{
				$ucenter_db->query("DELETE FROM " . DB_PREFIX_UCENTER . "members WHERE uid = " . intval($other_user['uid']));
				$ucenter_db->query("DELETE FROM " . DB_PREFIX_UCENTER . "memberfields WHERE uid = " . intval($other_user['uid']));
			}

			$master_db->query("DELETE FROM " . DB_PREFIX_MASTER . "users_ucenter WHERE uc_uid = " . intval($uc_user['uid']));
			$master_db->query("INSERT INTO " . DB_PREFIX_MASTER . "users_ucenter SET uid = " . $data['uid'] . ", uc_uid = " . $uc_user['uid'] . ", username = '" . addslashes($data['user_name']) . "', email = '" . $data['email'] . "'");

			import_avatar($data['uid'], $uc_user['uid']);
		}
	}
	else
	{
		$ucenter_db->query("INSERT INTO " . DB_PREFIX_UCENTER . "members SET username = '" . addslashes($data['user_name']) . "', password = '" . $data['password'] . "', email = '" . $data['email'] . "', regip = '" . long2ip($data['reg_ip']) . "', regdate = '" . $data['reg_time'] . "', salt = '" . $data['salt'] . "'");

		$uid = $ucenter_db->lastInsertId();

		$ucenter_db->query("INSERT INTO " . DB_PREFIX_UCENTER . "memberfields SET uid = " . $uid);

		$master_db->query("DELETE FROM " . DB_PREFIX_MASTER . "users_ucenter WHERE uc_uid = " . intval($uid));
		$master_db->query("INSERT INTO " . DB_PREFIX_MASTER . "users_ucenter SET uid = " . $data['uid'] . ", uc_uid = " . $uid .", username = '" . addslashes($data['user_name']) . "', email = '" . $data['email'] . "'");

		import_avatar($data['uid'], $uid);
	}
}

function get_avatar($uid, $size = '', $dirname = false)
{
	if (!$size AND !$dirname)
	{
		return false;
	}

	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);

	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);

	$dir = $dir1 . '/' . $dir2 . '/' . $dir3 . '/';

	if ($dirname)
	{
		return $dir;
	}

	return $dir . substr($uid, -2) . '_avatar_' . $size . '.jpg';
}

function import_avatar($uid, $uc_uid)
{
	$avatar = ROOT_PATH . 'uploads/avatar/' . get_avatar($uid, 'real');

	$avatar_size = array(
		'big' => array(
			'w' => 200,
			'h' => 250
		),
		'middle' => array(
			'w' => 120,
			'h' => 120
		),
		'small' => array(
			'w' => 48,
			'h' => 48
		)
	);

	if (file_exists($avatar))
	{
		$dir = rtrim(UCENTER_PATH, '/') . '/data/avatar/';

		make_dir($dir . get_avatar($uc_uid, '', true));

		foreach ($avatar_size AS $key => $val)
		{
			$image = new core_image();

			$image->initialize(array(
				'quality' => 90,
				'source_image' => $avatar,
				'new_image' => $dir . get_avatar($uc_uid, $key),
				'width' => $val['w'],
				'height' => $val['h']
			))->resize();
		}
	}
}

if (sizeof($users_list) == USER_IMPORT_PRE)
{
	TPL::assign('message', '正在导入用户, 当前第 ' . $next_page . ' 批 (每批 ' . USER_IMPORT_PRE . ' 个)');
	TPL::assign('url_bit', '?page=' . $next_page);
}
else
{
	TPL::assign('message', '全部用户导入完成, 请删除本工具, 并登录 UCenter 更新数据缓存.');
}

TPL::output('global/show_message');
