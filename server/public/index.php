<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author:  lookan
 * Date:    2020/10/13 17:10
 * Desc:    ran框架入口文件
 */
// [ 应用入口文件 ]
namespace ran;

date_default_timezone_set('PRC');
/*
 * 常量设置
 */
define("DS", DIRECTORY_SEPARATOR); //目录分隔符简化
define("PUBLIC_DIR", __DIR__); //public目录地址

// 加载基础文件
require __DIR__ . '/../ran/Ran.php';

// 加载函数库
require __DIR__ . '/../ran/common.php';

//执行然框架单例
Ran::getInstance();
