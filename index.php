<?php
	
	// 定义项目名称和路径
	define('APP_NAME', 'Apps');
	define('APP_PATH', './Apps/Index/');

	// 定义运行时缓存路径
	define('RUNTIME_PATH', './cache/Runtime/Index/');
	define('LOG_PATH', './cache/Runtime/Index/Logs/');

	// 是否取消预编译缓存
	define('NO_CACHE_RUNTIME', false);

	// 对编译缓存的内容是否迕行去空白和注释
	define('STRIP_RUNTIME_SPACE', false);

	// 开启ALLINONE运行模式(用于部署正式生产环境)
	// define('RUNTIME_ALLINONE', true);

	// 调试模式配置
	define('APP_DEBUG',true);

	//载入可框架文件	
	require("./ThinkPHP/ThinkPHP.php");