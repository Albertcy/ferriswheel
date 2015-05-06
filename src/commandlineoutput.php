<?php
/**
 * �������������
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
     * �����Ϣ
     *
     * @param string $message
     * @param string $newline
     *            �Ƿ�����
     */
    public function doWrite($message, $newline = false)
    {
        if (false === @fwrite($this->stream, $message . ($newline ? PHP_EOL : ''))) {
            error_log('Unable to write output.');
        }
        fflush($this->stream);
    }

    /**
     * ����Ƿ�֧�ֱ�׼���
     *
     * @return boolean
     */
    private function hasStdoutSupport()
    {
        return ('OS400' != php_uname('s'));
    }

    private $stream;
}