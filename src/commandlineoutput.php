<?php
/**
 * 命令行输出控制
 *
 * @author albert
 *
 */
class CommandLineOutput
{

    public function __construct()
    {
        if (false !== strpos(php_sapi_name(), 'cli'))
            $this->stream = $this->hasStdoutSupport() ? fopen('php://stdout', 'w') : fopen('php://output', 'w');
        else
            $this->stream = fopen('php://output', 'w');
    }

    /**
     * 输出信息
     *
     * @param string $message
     * @param string $newline
     *            是否折行
     */
    public function doWrite($message, $newline = false)
    {
        if (false === @fwrite($this->stream, $message . ($newline ? PHP_EOL : ''))) {
            error_log('Unable to write output.');
        }
        fflush($this->stream);
    }

    /**
     * 检测是否支持标准输出
     *
     * @return boolean
     */
    private function hasStdoutSupport()
    {
        return ('OS400' != php_uname('s'));
    }

    private $stream;
}