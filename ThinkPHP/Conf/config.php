<?php
if (in_array($_SERVER['HTTP_HOST'], array('mybloglocal.jinx888.com'))) {
	//数据库
    $db_host = '127.0.0.1';
    $db_name = 'myblog';
    $db_user = 'root';
    $db_pwd = '123456';
    //redis
    $redis_host = '127.0.0.1';
    $redis_port = '6379';
    $redis_prefix = 'local';
    // redis缓存日志
	$redis_applog_host = '127.0.0.1';
    // 网站域名
    $blog_dir = 'http://mybloglocal.jinx888.com/';

    // 空间路径
}elseif (in_array($_SERVER['HTTP_HOST'], array('www.jinx888.com'))) {
	//数据库
    $db_host = '127.0.0.1';
    $db_name = 'myblog';
    $db_user = 'root';
    $db_pwd = '123456';
    //redis
    $redis_host = '127.0.0.1';
    $redis_port = '6379';
    $redis_prefix = 'test';
    // redis缓存日志
    $redis_applog_host = '127.0.0.1';
    // 网站域名
    $blog_dir = 'http://www.jinx888.com/';
}

$myconfig = array(
	'DB_TYPE'=> 'mysql',   	// 数据库类型
    'DB_HOST'=> $db_host, 	// 数据库服务器地址
    'DB_NAME'=>$db_name,  	// 数据库名称
    'DB_USER'=>$db_user, 	// 数据库用户名
    'DB_PWD'=>$db_pwd, 		// 数据库密码
    'DB_PORT'=>'3306', 		// 数据库端口
    'DB_PREFIX'=>'', 		// 数据表前缀
    
	//redis 缓存
    'REDIS_HOST' => $redis_host,
	'REDIS_APPLOG_HOST' => $redis_applog_host,
    'REDIS_PORT' => $redis_port,
    'REDIS_PREFIX' => $redis_prefix,
    // 网站域名
	'BLOG_DIR' => $blog_dir,
	//限制ip登录查看网站
	'BLOG_LIMIT_IP' => array('101.231.121.134','112.65.213.83','61.141.201.188','140.207.169.67','118.212.213.20'),


	);
?>