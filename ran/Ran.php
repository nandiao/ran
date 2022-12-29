<?php

namespace ran;

include __dir__ . "/../vendor/autoload.php";

/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author:  lookan
 * Date:    2020/10/13 17:10
 * Desc:    ran框架
 */

use ran\cache\CacheNS;

/*
 * 框架类
 */

class Ran
{
    private $context;                //上下文
    private $conf;                   //配置
    private $confEnv = 'base';       //配置类型 base基础（模式） pro生成 test测试 dev开发
    private $isCmd = false;          //是否为命令行运行
    private $db;                     //数据库
    private $log;                    //日志对象
    private $group;                  //分组
    private $controller;             //控制器
    private $action;                 //方法
    private $param;                  //参数
    private static $_instance;       //单例属性

    /*
     * 构造函数
     */
    public function __construct($env = "dev")
    {
        global $argv;

        $this->context = Context::getInstance();

        $this->confEnv = $env; //默认环境

        //加载配置文件
        $config = require_once(PUBLIC_DIR . "/../config.php");

        //获取group controller action属性
        if (isset($argv) && $argv) { //从命令行获取
            if (in_array($argv[1], ['pro', 'test', 'dev']))
                $this->confEnv = $argv[1]; //获取配置类型
            $router_param = ""; //路由参数
            $tmp_param = "";
            isset($argv[1]) && $router_param = $argv[1];
            isset($argv[2]) && $tmp_param = $argv[2];
            if ($tmp_param) {
                $tmp_param = explode("/", $tmp_param);
                foreach ($tmp_param as $row) {
                    list($key, $value) = explode("=", $row);
                    if ($key && $value)
                        $this->param[$key] = $value;
                }
            }
            $this->isCmd = true;
            $this->context->isCmd = true;
        } else { //从url获取
            if (isset($config['env']) && in_array($config['env'], ['pro', 'test', 'dev']))
                $this->confEnv = $config['env']; //获取配置类型
            $router_param = substr($_SERVER["REQUEST_URI"], 1);
            $this->param = input();
        }
        $this->context->param = $this->param;

        $router_param = explode("/", $router_param);

        if (strpos($router_param[0], "index.php") !== false) //请求包含index.php
            $base = 2;
        else //请求不包含index.php
            $base = 1;

        $this->context->group = $this->group = isset($router_param[$base - 1]) && $router_param[$base - 1] ? $router_param[$base - 1] : "index";  //分组
        $this->context->controller = $this->controller = isset($router_param[$base]) ? $router_param[$base] : 'index';         //控制器
        isset($router_param[$base + 1]) && $this->context->action = $this->action = explode('?', $router_param[$base + 1])[0];   //方法
        if (empty($this->action)) $this->context->action = $this->action = "index";

        //base配置与环境配置合并,array_merge 不能合并相同键名
        $controller_config_file = __DIR__ . "/../controller/" . $this->group . "/config.php";
        $base_conf = [];
        if (file_exists($controller_config_file)) {
            $controller_config = require_once($controller_config_file);
            $base_conf = $controller_config[$this->confEnv] + $controller_config['base'];
        }

        $this->context->conf = $this->conf = $base_conf + $config[$this->confEnv] + $config['base'];

        /*
         * 时区设置
         */
        $timezone = "Asia/Shanghai";
        isset($this->context->conf['timezone']) && $timezone = $this->context->conf['timezone'];
        date_default_timezone_set($timezone);

        /*
         * 加载数据库
         */
        // $this->db = Db::class;
        // Db::setConfig($this->conf['db']);
        // Db::setCache(new CacheNS());

        //加载日志
        $this->log = new Log();

        /*
         * 运行控制器及方法
         */
        if ($this->group && $this->controller && $this->action) {
            /*
             * 检查是否在非命令行状态下，运行了命令行程序。如果是就报错
             */
            $crontab_group = [];
            isset($this->context->conf['router']['cmd_groups']) && $crontab_group = explode(",", $this->context->conf['router']['cmd_groups']);
            if (!$this->isCmd && in_array($this->group, $crontab_group))
                throw new RanException("非法运行");

            $className = '\\controller\\' . $this->group . '\\' . ucwords($this->controller);
            $c = new $className();
            $a = $this->action;
            //$c->db = $this->db;
            $c->isCmd = $this->isCmd;
            $c->param = $this->param;
            $c->$a($this->param);
        }
    }

    private function __clone()
    {
    } //覆盖__clone()方法，禁止克隆

    //获取单例
    public static function getInstance($env = "dev")
    {
        if (!(self::$_instance instanceof self))
            self::$_instance = new self($env);
        return self::$_instance;
    }
}
