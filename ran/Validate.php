<?php
/**
 * COPYRIGHT (C), Yun Shang. Co., Ltd.
 * Author: karl<nandiao@qq.com>
 * Date:   2021/7/31 16:30
 * Desc:   验证类
 */
namespace ran;

class Validate
{
    /**
     * 当前验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 当前验证场景
     * @var array
     */
    protected $currentScene = null;

    /**
     * 构造函数
     * @access public
     */
    public function __construct()
    {

    }

    /**
     * 设置验证场景
     * @access public
     * @param  string  $name  场景名
     * @return $this
     */
    public function scene($name)
    {
        // 设置当前场景
        $this->currentScene = $name;

        return $this;
    }

    /**
     * 数据自动验证
     * @access public
     * @param  array     $data  数据
     * @param  mixed     $rule  验证规则
     * @param  string    $scene 验证场景
     * @return array
     * @throws RanException
     */
    public function check($data, $rule = [], $scene = 0)
    {
        $result = ['error_code' => 0, 'error_msg' => ""];

        $this->rule = $rule + $this->rule;  //合并规则

        $sceneRule = $this->getSceneRule($scene); //得到本场景规则

        foreach ($sceneRule as $field => $v) { //场景规则遍历
            if (isset($data[$field])) { //值存在
                $rule_array = explode("|", $v[0]);
                foreach ($rule_array as $rule) { //字段规则遍历
                    $tmp = explode("#", $rule);
                    $msg = isset($tmp[1]) ? trim($tmp[1]) : $field."数据出错！";         //提示语
                    $tmp = explode(":", $tmp[0]);
                    $cmd = $tmp[0];                                     //检测指令
                    if ($cmd == "require") continue;
                    $param = isset($tmp[1]) ? trim($tmp[1]) : "";       //参数
                    $result = $this->checkItem($field, $cmd, $data[$field], $param, $msg); //单项检测
                    if ($result['error_code'] > 0) return $result;      //检测出错误返回
                }
            } else { //值不存在，看require
                if (strstr($v[0], "require")) { //存在require
                    $tmp = explode("#", $v[0]);
                    $msg = isset($tmp[1]) ? trim($tmp[1]) : $field."为必填";         //提示语
                    return ['error_msg' => $msg, 'error_code' => 1];
                }
            }
        }

        return ['error_code' => 0, 'error_msg' => ""];
    }

    /**
     * 单项检测
     * @access protected
     * @param  string  $field   字段
     * @param  string  $cmd     命令
     * @param  string  $data    待检查数据
     * @param  string  $param   参数
     * @param  string  $msg     提示
     * @return array
     * @throws RanException
     */
    protected function checkItem($field, $cmd, $data, $param = '', $msg = '')
    {
        $result = ['error_code' => 0, 'error_msg' => ""];

        if (empty($msg)) $msg = $field."数据有误！";

        if (method_exists($this, $cmd)) {
            if ($this->$cmd($data, $param))
                return ['error_code' => 0, 'error_msg' => ""];
            else
                return ['error_code' => 1, 'error_msg' => "数据有误"];
        } else {
            $result = $this->is($cmd, $data);
            if ($result['error_code'] >0 && $result['error_msg'] == "")
                $result['error_msg'] = $field."数据有误！";
        }

        return ['error_code' => 0, 'error_msg' => ""];
    }
    /**
     * 得到场景规则数组
     * @access protected
     * @param  string  $scene  场景名
     * @return boolean
     */
    public function hasScene($scene)
    {
        if (!isset($this->rule[$scene]))
            return false;
        else
            return true;
    }

    /**
     * 得到场景规则数组
     * @access protected
     * @param  string  $scene  场景名
     * @return array
     * @throws RanException
     */
    protected function getSceneRule($scene)
    {
        $sceneRule = [];
        //获取场景规则
        if (!$this->hasScene($scene))
            throw new RanException("场景不存在", 1);

        foreach ($this->rule[$scene] as $k => $v) {
            if (is_array($v)) { //本场景有值，用本场景值
                if (!isset($v['name']) && isset($this->rule[0][$k]['name'])) //本场景没name，基本场景有name
                    $sceneRule[$k] = [$v[0], "name" => $this->rule[0][$k]['name']];
                else
                    $sceneRule[$k] = $v;
            } elseif(!is_array($v) && isset($this->rule[0][$v])) //本场景没值，基本场景有值用基本场景值
                $sceneRule[$v] = $this->rule[0][$v];
        }

        return $sceneRule;
    }

/**********************************************************************************
 * 检测方法
 **********************************************************************************/
    /**
     * 验证字段值是否为有效格式
     * @access public
     * @param  string    $cmd   命令
     * @param  mixed     $value  字段值
     * @return bool
     * @throws RanException
     */
    public function is($cmd, $value)
    {
        $return = ['error_code' => 0, 'error_msg' => ''];
        switch ($cmd) {
            case 'accepted':
                // 接受
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                // 是否是一个有效日期
                $result = false !== strtotime($value);
                break;
            case 'activeUrl':
                // 是否为有效的网址
                $result = checkdnsrr($value);
                break;
            case 'boolean':
            case 'bool':
                // 是否为布尔值
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'number':
                $result = ctype_digit((string) $value);
                break;
            case 'alphaNum':
                $result = ctype_alnum($value);
                break;
            case 'mobile':  //手机格式检测
                $result = preg_match('/^1[3|4|5|7|8|9]\d{9}$/', $value);
                break;
            case 'array':
                // 是否为数组
                $result = is_array($value);
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
                break;
            default:
               throw new RanException($cmd."检查方法不存在");
        }
        if (!$result) {
            $error_code = isset($error_code) ? $error_code : 1;
            $error_msg = isset($error_msg) ? $error_msg : "";
            return ['error_code' => $error_code, 'error_msg' => $error_msg];
        } else {
            return ['error_code' => 0, 'error_msg' => ''];
        }
    }
    /**
     * 验证数据最大长度
     * @access public
     * @param  mixed     $value  字段值
     * @param  mixed     $rule  验证规则
     * @return bool
     */
    public function max($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        return $length <= $rule;
    }

}