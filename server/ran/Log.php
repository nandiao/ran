<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/6/23 16:30
 * Desc:   日志类 遵循PSR-3
 */
namespace ran;

use Psr\Log\AbstractLogger;
use think\facade\Db;
use ran\Context;

class Log extends AbstractLogger
{
    private $type = "file"; //日志类型
    private $folder = __DIR__. "/../runtime/log/";   //保存文件夹
    private $filename = ""; //日志文件名
    private $context = ""; //上下文

    public function __construct($config = ['type' => 'file'])
    {
        $this->context = Context::getInstance();
        $this->type = $config['type']; //日志类型
        $this->folder .= date("Ym", time()) . "/";
        //$this->filename = date("d", time());
        if(!is_dir($this->folder)) mkdir($this->folder,0777,true);
    }
    /**
     * Logs with an arbitrary level.
     * 写入日志（支持各级别）
     * @param mixed  $level     日志级别
     * @param string $message   消息
     * @param array  $context   上下文 
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        /*
         * 判断是不是忽略级别，是就跳过
         */
        $disable_level = [];
        isset($this->context->conf['log']['disable_level']) && $disable_level = $this->context->conf['log']['disable_level'];
        if ($disable_level && in_array($level, $disable_level)) return;

        if (is_array($message) || is_object($message)) $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        if ($this->type == 'file') { //日志类型为文件
            $filename = "";         //日志文件名
            $first_filename = "";   //日志文件前部

            /*
             * 判断是不是独立记录日志级别。是独立级别按级别文件命令，其他按日期文件命令
             */
            $apart_level = [];
            isset($this->context->conf['log']['apart_level']) && $apart_level = $this->context->conf['log']['apart_level'];
            if ($apart_level && in_array($level, $apart_level))
                $first_filename = $level;
            else
                $first_filename = date("d", time());

            /*
             * 判断是不是命令行
             */
            if ($this->context->isCmd)
                $filename = $first_filename."-cli.log";
            else
                $filename = $first_filename.".log";

            /*
             * 判断文件有没有超过限制，有的话把当前文件按时间戳备份后，新生成一个文件
             */
            $file_max_size = 10;
            $file_size = 0;
            isset($this->context->conf['log']['file_max_size']) && $file_size_limit = $this->context->conf['log']['file_max_size'];
            file_exists($this->folder . $filename) && $file_size = filesize($this->folder . $filename);
            if ($file_size > $file_max_size * 1024 * 1024 ) {
                if ($this->context->isCmd)
                    $new_filename = $first_filename ."-". time() ."-cli.log";
                else
                    $new_filename = $first_filename ."-". time() .".log";
                if(copy($filename, $new_filename)) {
                    fopen($this->folder . $filename, "w");
                } else
                    throw new RanException("日志文件创建失败");
            }

            //写入日志
            file_put_contents($this->folder . $filename, date("Y-m-d H:i:s", time())." [".$level."] ".$message . PHP_EOL, FILE_APPEND);

        } elseif ($this->type == 'mysql') { //日志类型为mysql
            Db::name("log")->insert([
                'level' => $level,
                'message' => $message,
                'context' => json_encode($context),
                'create_time' => time()//date("Y-m-d H:i:s", time())
            ]);
        }

    }
}