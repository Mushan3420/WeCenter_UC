 WeCenter UCenter 用户对接组件
 ===========
 
 版本需求
 ===========
 WeCenter 3.x

 安装方法
 =========== 
 1. 将 Upload 目录的文件放到程序根目录, 设置 data 目录与下面的文件可写 (777 权限, 这步非常重要)
 2. 进入 UCenter 后台, 添加应用

 应用类型: 其他

 其他的按照说明填写
 
 标签无需填写

 3. 添加完成后点击编辑应用, 将最下面的 应用的 UCenter 配置信息: 里的内容建立成一个 PHP 文件保存为 config.inc.php 到 uc_client/ 目录下面, 权限设置为 777, 保存为 UTF-8 编码 (重要!) 内容大致如下:
 
```
<?php	// 	别忘记这行

define('UC_CONNECT', 'mysql');	
define('UC_DBHOST', 'localhost');
define('UC_DBUSER', 'ucenter');
define('UC_DBPW', '!!!');
define('UC_DBNAME', 'ucenter');
define('UC_DBCHARSET', 'utf8');
define('UC_DBTABLEPRE', '`ucenter`.pre_ucenter_');
define('UC_DBCONNECT', '0');
define('UC_KEY', 'ERGWECDQWDEWFEXSA');
define('UC_API', 'http://ucenter.net/uc_server');
define('UC_CHARSET', 'utf-8');
define('UC_IP', '');
define('UC_APPID', '2');
define('UC_PPP', '20');
```

 4. 刷新 UCenter 后台管理, 显示通信成功即安装完成, 去后台开启 Ucenter 用户对接选项就可以使用了
 
 附加工具
 ===========
 
 Tools 下的 uc_import.php 可以将 WeCenter 的用户数据导入 UCenter
 
 常见问题
 ===========
 
 1. WeCenter 登录后 Discuz 不同步登录
 	该用户是 WeCenter 用户, 没有在 Discuz 登录过会有此问题
