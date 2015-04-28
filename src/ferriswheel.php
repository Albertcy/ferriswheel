<?php

/**
 * Ferris Wheel 辅助工具
 */
class FerrisWheel
{

    /**
     * 开始引入
     *
     * @param CommandLineInput $input            
     * @param CommandLineOutput $output            
     */
    static public function start($input, $output)
    {
        try {
            $name = ucfirst($name = $input->getCommandName()) . 'Tools';
            if (! class_exists($name)) {
                throw new Exception("command: '{$name}' does not exist.");
            }
            $command = new $name();
            
            $command->run($input, $output);
        } catch (Exception $e) {
            $output->doWrite($e->getMessage());
        }
    }

    /**
     * 获取默认操作列表
     *
     * @param CommandLineOutput $output            
     */
    static public function getlist($output)
    {
        foreach (self::$list as $key => $item)
            $output->doWrite($key . '             ' . DIRECTORY_SEPARATOR);
    }

    private static $list = array(
        'xhprof' => 'xhproftools'
    );
}

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
            echo 'Unable to write output.';
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

/**
 * xhprof工具
 *
 * @author albert
 */
class XhprofTools
{

    /**
     * 运行命令
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
     * 重载方法
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
     * 运行命令
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
     * 帮助命令
     */
    public function help()
    {}

    /**
     * 检测工具所需的配置
     *
     * @param $install 是否安装            
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
     * 安装工具
     */
    public function install()
    {
        $this->check(true);
    }

    /**
     * 下载分析数据
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
     * 检测配置文件
     *
     * @return boolean
     */
    private function checkEvnfile()
    {
        return file_exists($this->getEnvFilePath());
    }

    /**
     * 导入依赖文件
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
     * 打包文件
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
     * 压缩文件
     *
     * @param string $filename            
     * @return string 压缩包路径
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
     * 获取目录下所有文件
     *
     * @param string $desPath
     *            目录地址
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
     * 检查默认配置文件存放路径
     */
    public function ckpath()
    {
        $this->output->doWrite($this->getTargetPath());
    }

    /**
     * 获取默认配置目录
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
     * 获取配置文件路径
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
     * 检测ini配置文件
     *
     * @return bool
     */
    private function checkIniSettings()
    {
        return extension_loaded('xhprof') && ini_get('xhprof.output_dir');
    }

    /**
     * 设置配置文件
     *
     * @param string $file
     *            文件名
     * @param mixed $settings
     *            配置信息
     * @param int $i
     *            前置空格
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
     * 配置xhprof选项
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
     * 备份文件
     *
     * @return bool
     */
    private function backupFiles($filePath)
    {
        $path = realpath($filePath);
        return copy($filePath, $filePath . '_bak_' . time());
    }

    /**
     * 导出日志文件
     */
    private function exportFiles()
    {}

    /**
     * 获取导出文件路径
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
     * 输出错误信息
     *
     * @param string $message
     *            错误信息
     * @param
     *            bool throw 采用抛出异常方式
     * @param boolen $flush
     *            刷新输出缓存
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
     * 获取工具配置信息
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
     * 默认设置
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

FerrisWheel::start(new CommandLineInput(), new CommandLineOutput());