<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/6/23 16:30
 * Desc:   控制器基类
 */
namespace ran;

use think\exception\HttpResponseException;
use think\response\Redirect;

class Controller
{
    public $db;//数据库
    protected $log;             //日志
    protected $context;         //上下文
    protected $start_time;      //开始时间
    protected $start_memory;    //开始内存

    public function __construct()
    {
        $this->context = Context::getInstance();
        self::logStart();
        self::validate(); //数据验证
    }

    /*
     * 日志处理开始
     */
    public function logStart()
    {
        $config = $this->context->conf['log'];
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
        $this->log = new Log($config);
        if (isset($config['is_cinfo']) && $config['is_cinfo']) { //是否开启了控制器日志
            $header_log = [];
            $ctl_log = explode(",", $config['cinfo']);
            if (in_array("ip", $ctl_log)) { //记录访问者ip
                isset($_SERVER['REMOTE_ADDR']) && $header_log['ip'] = $_SERVER['REMOTE_ADDR'];
            }
            if (in_array("url", $ctl_log)) { //记录访问url
                isset($_SERVER['REQUEST_METHOD']) && $header_log['method'] = $_SERVER['REQUEST_METHOD'];
                isset($_SERVER['REQUEST_URI']) && $header_log['url'] = $_SERVER['REQUEST_URI'];
            }
            $this->log->log("cinfo", implode(" ", $header_log));
            if (in_array("param", $ctl_log)) { //记录参数
                $param['param'] = input();
                $this->log->log("cinfo", $param);
            }
        }
    }
    /*
     * 日志处理结束
     */
    public function logEnd()
    {
        $config = $this->context->conf['log'];
        if (isset($config['is_cinfo']) && $config['is_cinfo']) { //是否开启了控制器日志
            $ctl_log = explode(",", $config['cinfo']);
            $footer_log = [];
            if (in_array("time", $ctl_log)) { //记录访问url
                $end_time = microtime(true);
                $run_time = round($end_time - $this->start_time, 6);
                $footer_log['time'] = "run: ". $run_time ."s";
            }
            if (in_array("files", $ctl_log)) { //记录访问url
                $files_num = count(get_included_files());
                $footer_log['files'] = "引入文件:".$files_num;
            }
            if (in_array("memory", $ctl_log)) {
                $memory = memory_get_usage(true) - $this->start_memory;
                $footer_log['memory'] = "内存消耗:".$memory;
            }
            $footer_log['end'] = PHP_EOL;
            $this->log->log("cinfo", implode(" ", $footer_log));
        }
    }

    /*
     * 析构函数 删除或者当对象被显式销毁时执行
     */
    public function __destruct()
    {
        self::logEnd();
    }
    
    /**
     * curl请求方式
     * @param string $url 请求地址
     * @param string $method 请求方法
     * @param array $data 请求数据
     * @param string $type 请求头类型 [json | form]
     * @param bool  $format 是否格式化数据
     * @return bool|string|array 返回内容
     */
    function newCurl($url = '', $method = 'GET', $data = [], $type = 'form', $format = true)
    {
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }


    /**
     * outTrue api输出正确数组
     * @param  mixed      $data      返回数值
     * @return array
     */
    protected function outTrue($data = "")
    {
        if (is_object($data)) $data = $data->toArray();
        if (!is_array($data))
            die(json_encode(['error_code' => 0, 'error_msg' => "",'time'=>time()]));
        else
            die(json_encode(array_merge(['error_code' => 0, 'error_msg' => "", 'time'=>time()], $data)));
    }
    /**
     * outTrue api输出错误数组
     * @param  string       $error_msg        错误值
     * @param  integer      $error_code      错误代码
     * @return array
     */
    protected function outFalse($error_msg, $error_code = 1)
    {
        die(json_encode(['error_code' => $error_code, 'error_msg' => $error_msg], JSON_UNESCAPED_UNICODE));
    }

    /**
     * outBoolean
     * @return Boolean
     */
    protected function outBoolean($Boolean)
    {
        die($Boolean);
    }
    /*
     * 数据验证
     * 自动搜索分组/控制器名的验证类 进行方法名场景验证
     * 如访问路径 /api/noauth/mobile_login 自动搜索 validate\api\Noauth验证类 进行mobile_login场景验证
     */
    protected function validate()
    {
        $validateClass = "validate\\".$this->context->group."\\".ucfirst($this->context->controller);
        if (!class_exists($validateClass))
            return false;
        $v = new $validateClass;
        if($v->hasScene($this->context->action)) {
            $res = $v->check(input(), [], $this->context->action);
            if ($res['error_code'] > 0)
                $this->outFalse($res['error_msg'], $res['error_code']);
        }
    }
    /*
     * 成功显示
     */
    protected function success($url, $msg = "操作成功", $wait = 1)
    {
        $this->display(['msg' => $msg, 'code' => 1, 'url' => $url, 'wait' => $wait], "/common/jump.php");
    }
    /*
     * 失败显示
     */
    protected function error($url, $msg = "操作失败", $wait = 0)
    {
        $this->display(['msg' => $msg, 'code' => 1, 'url' => $url, 'wait' => $wait], "/common/jump.php");
    }

    /*
     * 视图赋值
     */
    protected function assign($key, $value)
    {
        return View::assign($key, $value);
    }
    /*
     * 视图输出
     */
    protected function display($data = [], $path = "")
    {
        //$path = "view". DS . $this->context->group . DS . $this->context->controller . DS . $this->context->action . '.php';
        return View::display($data, $path);
    }
}