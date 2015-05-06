<?php
/**
 * 命令行输入控制
 *
 * @author albert
 */
class CommandLineInput
{

    public function __construct()
    {
        $argv = array_values($_GET);
        // cli 模式
        if (false !== strpos(php_sapi_name(), 'cli')) {
            $argv = $_SERVER['argv'];
            array_shift($argv);
        }
        $this->tokens = $argv;
    }

    /**
     * 获取命令名称
     *
     * @return multitype:
     */
    public function getCommandName()
    {
        return empty($this->tokens[0]) ? null : $this->tokens[0];
    }

    /**
     * 获取参数
     */
    public function getArgument($i)
    {
        return $this->tokens[$i];
    }

    /**
     * 输入命令集
     *
     * @var array
     */
    private $tokens;
}