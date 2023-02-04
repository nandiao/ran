<?php
namespace ran;

/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author:  lookan
 * Date:    2020/10/13 17:10
 * Desc:    上下文
 */
class Context
{
    public $conf;                   //配置
    public $confEnv = 'base';       //配置类型 base基础（模式） pro生成 test测试 dev开发
    public $group;                  //分组
    public $controller;             //控制器
    public $action;                 //方法
    public $isCmd = false;          //是否为命令行
    public $param = [];             //参数
    private static $_instance;       //单例属性

    /*
     * 构造函数
     */
    public function __construct()
    {

    }

    private function __clone() {} //覆盖__clone()方法，禁止克隆

    //获取单例
    public static function getInstance()
    {
        if(! (self::$_instance instanceof self) )
            self::$_instance = new self();
        return self::$_instance;
    }

}