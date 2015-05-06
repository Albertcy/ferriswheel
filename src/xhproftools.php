<?php
/**
 * xhprof����
 *
 * @author albert
 */
class XhprofTools
{

    /**
     * ��������
     *
     * @param CommandLineInput $input
     * @param CommandLineOutput $output
     */
    public function run($input, $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->doCommand($input, $output);
    }

    /**
     * ���ط���
     *
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        if (! empty($this->funcAlias[$name])) {
            $name = $this->funcAlias[$name];
            $this->$name();
        } else {
            throw new \RuntimeException('xhprof tools does not have command "' . $name . '"');
        }
    }

    /**
     * ��������
     *
     * @param CommandLineInput $input
     * @param CommandLineOutput $output
     */
    public function doCommand($input, $output)
    {
        $name = trim($input->getArgument(1), '-');
        $this->$name();
    }

    /**
     * ��������
     */
    public function help()
    {}

    /**
     * ��⹤�����������
     *
     * @param $install �Ƿ�װ
     */
    public function check($install = false)
    {
        if (! ($checkIni = $this->checkIniSettings())) {
            if ($install) {
                $this->setXhprofIni();
                $this->output->doWrite('xhprof installed.', true);
            } else {
                $this->error('xhprof.dll does not installed.');
            }
        }
        if (! ($checkEvn = $this->checkEvnfile())) {
            if ($install) {
                $this->setEnvFiles($this->getEnvFilePath(), $this->defaultSettings);
                $this->output->doWrite('.env initailized.');
            } else {
                $this->error('warning: .evn file required. use : -install to initialize.');
            }
        }
        if ($install) {
            $this->output->doWrite('please restart apache for using all settings.');
        }
        if ($checkIni && $checkEvn) {
            $this->output->doWrite('xhprof.dll already installed.');
        }
    }

    /**
     * ��װ����
     */
    public function install()
    {
        $this->check(true);
    }

    /**
     * ���ط�������
     */
    public function download()
    {
        $path = $this->packFile();
        if (file_exists($path)) {
            header('Content-Type:application/x-rar-compressed');
            header('Content-Disposition: attachment; filename="' . basename($path) . '"');
            file_put_contents('php://output', file_get_contents($path));
            fflush('php:output');
        }
    }

    /**
     * ��������ļ�
     *
     * @return boolean
     */
    private function checkEvnfile()
    {
        return file_exists($this->getEnvFilePath());
    }

    /**
     * ���������ļ�
     *
     * @param string $filepath
     * @return boolean
     */
    private function importLibFiles($filepath = null)
    {
        if (false == ! ($path = is_real($filepath))) {
            $rar_file = rar_open($filepath);
            $list = rar_list($rar_file);
            foreach ($list as $file) {
                $entry = rar_entry_get($rar_file, $file->getName());
                $entry->extract($this->getTargetPath());
            }
            rar_close($rar_file);
            return true;
        }
        return false;
    }

    /**
     * ����ļ�
     */
    private function packFile()
    {
        if ($path = $this->zipFile()) {
            // $this->output->msg($path);
            return $path;
        } else {
            $this->error('zip file failed.');
        }
    }

    /**
     * ѹ���ļ�
     *
     * @param string $filename
     * @return string ѹ����·��
     */
    private function zipFile($filename = null)
    {
        $zip = new ZipArchive();
        $zipPath = $this->getExportFilePath() . DIRECTORY_SEPARATOR . 'profile' . time() . '.zip';
        if ($zip->open($zipPath, ZIPARCHIVE::CREATE) !== TRUE) {
            $this->error("cannot open <$filename>");
        }
        $files = $this->getAllFiles($this->getExportFilePath());
        if (empty($files)) {
            $this->error("no files need to pack.");
        }
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();
        return $zipPath;
    }

    /**
     * ��ȡĿ¼�������ļ�
     *
     * @param string $desPath
     *            Ŀ¼��ַ
     * @return multitype:string
     */
    private function getAllFiles($desPath)
    {
        $fileList = array();
        $handle = opendir($desPath);
        while (($file = readdir($handle)) != false) {
            $path = $desPath . DIRECTORY_SEPARATOR . $file;
            if ('.' == $file || '..' == $file || __FILE__ == $path || in_array(pathinfo($file, PATHINFO_EXTENSION), array(
                'rar',
                'zip'
            )))
                continue;
            $fileList[] = $path;
        }
        closedir($handle);
        return $fileList;
    }

    /**
     * ���Ĭ�������ļ����·��
     */
    public function ckpath()
    {
        $this->output->doWrite($this->getTargetPath());
    }

    /**
     * ��ȡĬ������Ŀ¼
     *
     * @return boolean|string
     */
    private function getTargetPath()
    {
        if (false === ($path = realpath(DIRECTORY_SEPARATOR . 'U8SOFT' . DIRECTORY_SEPARATOR . 'turbocrm70' . DIRECTORY_SEPARATOR . 'tsvr'))) {
            $this->error('ferris wheel does not in server root path.');
            return false;
        }
        return $path;
    }

    /**
     * ��ȡ�����ļ�·��
     *
     * @return string
     */
    private function getEnvFilePath()
    {
        if (false === ($path = realpath(DIRECTORY_SEPARATOR . 'U8SOFT' . DIRECTORY_SEPARATOR . 'turbocrm70' . DIRECTORY_SEPARATOR . 'tsvr'))) {
            $this->error('tsvr path error.');
            return false;
        }
        return $path . DIRECTORY_SEPARATOR . '.env';
    }

    /**
     * ���ini�����ļ�
     *
     * @return bool
     */
    private function checkIniSettings()
    {
        return extension_loaded('xhprof') && ini_get('xhprof.output_dir');
    }

    /**
     * ���������ļ�
     *
     * @param string $file
     *            �ļ���
     * @param mixed $settings
     *            ������Ϣ
     * @param int $i
     *            ǰ�ÿո�
     * @return number|string
     */
    private function setEnvFiles($file, $settings, $i = 1)
    {
        $str = "";
        foreach ($settings as $k => $v) {
            if (is_array($v)) {
                $str .= str_repeat(" ", $i * 2) . "[$k]" . PHP_EOL;
                $str .= $this->setEnvFiles("", $v, $i + 1);
            } else
                $str .= str_repeat(" ", $i * 2) . "$k = $v" . PHP_EOL;
        }

        $phpstr = ";xhprof settings" . PHP_EOL . $str . PHP_EOL;

        if ($file)
            return file_put_contents($file, $phpstr);
        else
            return $str;
    }

    /**
     * ����xhprofѡ��
     *
     * @return bool
     */
    private function setXhprofIni()
    {
        if (false !== ($ext_path = realpath(ini_get('extension_dir') . '/php_xhprof.dll')) && false !== ($iniPath = php_ini_loaded_file())) {
            $settingStr = PHP_EOL . ';xhprof' . PHP_EOL . 'extension=' . $ext_path . PHP_EOL;
            $settingStr .= PHP_EOL . ';xhprof export directory' . PHP_EOL . 'xhprof.output_dir=' . $this->getExportFilePath();
            if ($this->backupFiles($iniPath)) {
                file_put_contents($iniPath, $settingStr, FILE_APPEND);
                return true;
            }
        }
        $this->error('php_xhprof.dll not found in extension diretory.');
        return false;
    }

    /**
     * �����ļ�
     *
     * @return bool
     */
    private function backupFiles($filePath)
    {
        $path = realpath($filePath);
        return copy($filePath, $filePath . '_bak_' . time());
    }

    /**
     * ������־�ļ�
     */
    private function exportFiles()
    {}

    /**
     * ��ȡ�����ļ�·��
     *
     * @return string
     */
    private function getExportFilePath()
    {
        if (empty($this->exportPath)) {
            $this->exportPath = ini_get('xhprof.output_dir') ? ini_get('xhprof.output_dir') : realpath($this->defaultSettings['xhprof_output']);
            @mkdir($this->exportPath, 0777);
        }
        return $this->exportPath;
    }

    /**
     * ���������Ϣ
     *
     * @param string $message
     *            ������Ϣ
     * @param
     *            bool throw �����׳��쳣��ʽ
     * @param boolen $flush
     *            ˢ���������
     */
    private function error($message, $throw = true, $flush = true)
    {
        if ($throw) {
            throw new RuntimeException($message);
        } elseif ($message) {
            $this->errorInfos[] = $message;
            if ($flush) {
                foreach ($this->errorInfos as $item) {
                    $this->output->doWrite($item, true);
                }
                $this->errorInfos = array();
            }
        }
    }

    /**
     * ��ȡ����������Ϣ
     *
     * @param string $key
     * @return multitype:
     */
    private function getEnvSettings($key)
    {
        if (empty($this->evnSettings)) {
            $this->evnSettings = parse_ini_file($this->getEnvFilePath());
        }
        return empty($this->evnSettings[$key]) ? $this->defaultSettings[$key] : $this->evnSettings[$key];
    }

    private $iniSettings = array(
        ''
    );

    /**
     * Ĭ������
     *
     * @var array
    */
    private $defaultSettings = array(
        'all_service' => false,
        'xhprof_output' => '\\profiler'
    );

    private $evnSettings = array();

    private $errorInfos;

    private $exportPath;

    private $funcAlias = array(
        'pack' => 'packFile'
    );
}
