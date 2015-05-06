<?php
/**
 * �������������
 *
 * @author albert
 */
class CommandLineInput
{

    public function __construct()
    {
        $argv = array_values($_GET);
        // cli ģʽ
        if (false !== strpos(php_sapi_name(), 'cli')) {
            $argv = $_SERVER['argv'];
            array_shift($argv);
        }
        $this->tokens = $argv;
    }

    /**
     * ��ȡ��������
     *
     * @return multitype:
     */
    public function getCommandName()
    {
        return empty($this->tokens[0]) ? null : $this->tokens[0];
    }

    /**
     * ��ȡ����
     */
    public function getArgument($i)
    {
        return $this->tokens[$i];
    }

    /**
     * �������
     *
     * @var array
     */
    private $tokens;
}