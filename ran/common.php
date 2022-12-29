<?php

use ran\Context;

/*************************************************
 *Description:   公共函数库
 *Others:
 *************************************************/

/*
 * 日志
 * @param  mixed       $msg      信息
 * @param  string      $level    日志级别
 * @return void
 */
function dlog($msg, $level = 'info')
{
    $context = ran\Context::getInstance();
    $log = new ran\Log($context->conf['log']);
    $log->log($level, $msg);
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
function curl($url = '', $method = 'GET', $data = [], $type = 'form', $format = true)
{
    if ($type == 'json') {
        $header = 'content-type: application/json';
    } else {
        $header = 'content-type: multipart/form-data';
    }
    $curl = curl_init();
    $curl_info = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "cache-control: no-cache",
            $header
        ],
    ];
    if (strpos($url, 'https') >= 0) {
        $curl_info[CURLOPT_SSL_VERIFYPEER] = false;
        $curl_info[CURLOPT_SSL_VERIFYHOST] = false;
    }
    curl_setopt_array($curl, $curl_info);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($format == false) {
        return $response;
    }
    if ($err) {
        throw new \ran\RanException($err);
    } else {
        return (json_decode($response, true));
    }
}

/*
 * 获取输入
 * @param  string      $key    键名 为空拿全部
 * @return array
 */
function input($key = "")
{
    $body = json_decode(file_get_contents('php://input'), true) ?? []; //获取body数组
    if (isset($_GET['s'])) unset($_GET['s']);
    $input = $_POST + $_GET + $body;
    if (!empty($key)) {
        if (isset($input[$key]) && is_string($input[$key]) && empty($_FILES)) {
            if (preg_match("/'(?:\w*)\W*?[a-z].*(R|ELECT|OIN|NTO|HERE|NION)/i", $input[$key]))
                throw new \ran\RanException("数据非法");
            return $input[$key];
        } elseif (isset($input[$key]) && is_array($input[$key])) {
            return $input[$key];
        } elseif (isset($input[$key]) && is_numeric($input[$key]))
            return $input[$key];
        else
            return false;
    } else { //为空拿全部
        foreach ($input as $v)
            if (is_string($v) && preg_match("/'(?:\w*)\W*?[a-z].*(R|ELECT|OIN|NTO|HERE|NION)/i", $v) && empty($_FILES))
                throw new \ran\RanException("数据非法");
        return $input;
    }
}

/*
 * 获取IP
 */
function getIp()
{
    if (isset($_SERVER["HTTP_CLIENT_IP"]) && $_SERVER["HTTP_CLIENT_IP"] && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } else {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $_SERVER["HTTP_X_FORWARDED_FOR"] && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            if (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
                $ip = $_SERVER["REMOTE_ADDR"];
            } else {
                if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $ip = "unknown";
                }
            }
        }
    }
    return ($ip);
}
/*
 * url转换
 */
function url($url)
{
    if (false)
        return $url;
    else
        return "/index.php" . $url;
}
