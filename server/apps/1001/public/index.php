<?php

/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author:  lookan
 * Date:    2020/10/13 17:10
 * Desc:    ran框架入口文件
 */
// [ 应用入口文件 ]
namespace ran;

define("PUBLIC_DIR", __DIR__);

// 加载基础文件
require __DIR__ . '/../../../ran/ran.php';

// 加载函数库
require __DIR__ . '/../../../ran/common.php';

//执行然框架单例
Ran::getInstance();
