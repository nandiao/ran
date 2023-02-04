<?php
/*************************************************
 *ClassName:     View
 *Description:   视图
 *Others:
 *************************************************/
namespace ran;

use ran\Context;

class View
{
    static private $data = []; //视图变量
    static private $view = ""; //视图内容
    static private $context = []; //上下文
    static private $path = ""; //视图路径
    /*
     * 模板赋值
     */
    static public function assign($key, $value)
    {
        self::$data[$key] = $value;
    }
    /*
     * 视图输出
     */
    static public function display($data = [], $path = "")
    {
        foreach ($data as $key => $value) self::assign($key, $value);
        if ($path) self::$path = $path;
        self::analysis();//视图标签解析
        extract(self::$data);
        require_once (__DIR__ . "/../runtime/temp/" . md5(self::$path));
    }
    /*
     * 模板分析,生成模板编译文件
     */
    static private function analysis()
    {
        self::$context = Context::getInstance();
        if (!self::$path) self::$path = self::$context->group . DS . self::$context->controller . DS . self::$context->action . '.php';
        self::$view = file_get_contents(__DIR__ .  "/../view/" . self::$path);
        //extend 模板继承分析
        self::tag_extend();
        //include
        self::tag_include();
        //生成模板编译文件
        file_put_contents(__DIR__ . "/../runtime/temp/" . md5(self::$path), self::$view);
    }
    /*
     * 分析block标签
     */
    static private function tag_block($begin, $process, $end)
    {
        /*
         * 分析原视图block
         */
        preg_match_all("/\{block\s*name\s*=\s*[\"\'](.*)[\"\']\s*\}([\S\s]*?)\{\/block\}/", $end, $view_matchs);
        /*
         * 分析模板视图block
         */
        preg_match_all("/\{block\s*name\s*=\s*[\"\'](.*)[\"\']\s*\}([\S\s]*?)\{\/block\}/", $process, $tpl_matchs);
        $replace_array = []; //替换数组初始化
        foreach ($tpl_matchs[1] as $k => $tag)
            if (($n = array_search($tag, $view_matchs[1])) !== false )
                $process = str_replace($tpl_matchs[0][$k], $view_matchs[2][$n], $process);
            else
                $process = str_replace($tpl_matchs[0][$k], "", $process);
        self::$view = $process;
    }
    /*
     * 模板继承分析
     */
    static private function tag_extend()
    {
        $tmp = explode("{extend", self::$view, 2);
        if (empty($tmp[1])) return false;
        $tmp2 = explode('/}', $tmp[1], 2);
        if (empty($tmp2[0])) return false;
        $begin = $tmp[0];$end = $tmp2[1];$process = ""; //得到模板开始和模板结束, 模板中间处理初始化
        preg_match("/name\s*=\s*[\"\'](.*)[\"\']/", $tmp2[0], $matchs);
        if (empty($matchs[0])) return false;
        $extend_view_path = $matchs[1];
        $process = file_get_contents(__DIR__ .  "/../view/". self::$context->group . DS . $extend_view_path . ".php");
        if (empty($process)) throw new RanException("extend视图文件读取失败");
        self::tag_block($begin, $process, $end);
    }
    /*
     * 模板包含分析
     */
    static private function tag_include()
    {
        $tmp = explode("{include", self::$view);
        if (empty($tmp[1])) return false;
        $tmp2 = explode('/}', $tmp[1]);
        if (empty($tmp2[0])) return false;
        $begin = $tmp[0];$end = $tmp2[1];$process = ""; //得到模板开始和模板结束, 模板中间处理初始化
        preg_match("/file\s*=\s*[\"\'](.*)[\"\']/", $tmp2[0], $matchs);
        if (empty($matchs[0])) return false;
        $extend_view_path = $matchs[1];
        $process = file_get_contents(__DIR__ .  "/../view/". self::$context->group . DS . $extend_view_path . ".php");
        if (empty($process)) throw new RanException("extend视图文件读取失败");
        self::$view = $begin . $process . $end;
    }
}