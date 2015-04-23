<?php

/**
 * 分析器接口
 * @author albert
 */
interface ProfilerInterface
{

    /**
     * 开始分析
     */
    public function start();

    /**
     * 结束分析
     */
    public function end();

    /**
     * 分析器是否可用
     *
     * @return bool
     */
    public function isProfilerEnable();

    /**
     * 初始化
     */
    public function ini();
}

/**
 * 性能分析工具
 *
 * @author albert
 */
class Profiler
{

    /**
     * 执行分析
     *
     * @param string $name
     *            分析工具名称
     */
    public static function profile($name)
    {
        if (! self::$profiler) {
            $className = ucfirst($name) . 'Profiler';
            self::$profiler = new $className();
            if (! self::$profiler instanceof ProfilerInterface || ! self::$profiler->isProfilerEnable()) {
                self::$profiler = new adapterProfiler();
            } else {
                self::$profiler->ini();
            }
        }
        return self::$profiler;
    }

    /**
     * 分析器实例
     *
     * @var ProfilerInterface
     */
    private static $profiler;
}

/**
 * 初始化适配器
 *
 * @author albert
 *        
 */
class adapterProfiler implements ProfilerInterface
{

    public function start()
    {}

    public function end()
    {}

    public function ini()
    {}

    public function isProfilerEnable()
    {}

    public function autoServiceProfile()
    {}
}

class XhprofProfiler implements ProfilerInterface
{

    /**
     * 获取配置信息
     *
     * @param string $name            
     * @return boolean
     */
    private function getSetting($name)
    {
        return empty($this->settings[$name]) ? false : $this->settings[$name];
    }

    /**
     * 解析配置文件
     */
    private function parseSetting()
    {
        $this->configFile = $this->getEnvFilePath();
        if ($this->configFile) {
            $this->settings = parse_ini_file($this->getEnvFilePath());
        }
    }

    /**
     * 初始化性能分析工具
     *
     * @return boolean
     */
    public function ini()
    {
        $this->parseSetting();
        if ($this->pageProfileAvailable()) {
            $this->authorize();
        }
        if ($this->isServiceProfileAvailable()) {
            $this->registerShutdown();
        }
        return true;
    }

    /**
     * 检测针对服务性能分析是否可用
     *
     * @return boolean
     */
    public function isServiceProfileAvailable()
    {
        return $this->autoServiceProfile() || $this->pageProfileavailable();
    }

    /**
     * 页面性能分析是否可用
     *
     * @return boolean
     */
    private function pageProfileAvailable()
    {
        return $this->getSetting('page_profile') && $_GET['profile'];
    }

    /**
     * 是否启用全部服务监控
     *
     * @return boolean
     */
    private function autoServiceProfile()
    {
        return $this->getSetting('all_service');
    }

    /**
     * 身份验证
     */
    private function authorize()
    {
        if (! isset($_SERVER['PHP_AUTH_USER']) || ! isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != self::username || $_SERVER['PHP_AUTH_PW'] != self::passwd) {
            if (headers_sent($file, $line)) {
                $message = "warning: http header already sent. file: {$file} , at line {$line}.";
            } else {
                Header("WWW-Authenticate: Basic realm=\"Profile Login\"");
                Header("HTTP/1.0 401 Unauthorized");
            }
            
            echo <<<EOB
				<html><body>
				<h1>Rejected!</h1>
				<big>Wrong Username or Password!</big>
                <b>{$message}</b>
				</body></html>
EOB;
            exit();
        }
    }

    /**
     * 注册shutdown 函数
     *
     * return void
     */
    private function registerShutdown()
    {
        if (! $this->registed) {
            register_shutdown_function(array(
                $this,
                'doEnd'
            ));
            $this->registed = true;
        }
    }

    /**
     * 工具是否可用
     *
     * @return boolean
     */
    public function isProfilerEnable()
    {
        return extension_loaded($this->extension) && $this->getOutputPath();
    }

    /**
     * 获取输出文件路径
     *
     * @return string
     */
    private function getOutputPath()
    {
        return ini_get('xhprof.output_dir');
    }

    /**
     * 开始调试
     *
     * @param string $logPrefix            
     * @param mixed $setting            
     * @see xhprof_enable
     *
     */
    public function start($logPrefix = null, $setting = array())
    {
        $this->setProfileLogPrefix($logPrefix);
        if ($this->pageProfileAvailable()) {
            $this->authorize();
        }
        if ($this->autoServiceProfile() && ! $this->isServiceMatch()) {
            error_log('xhprof-tools [All Service Mode] : service name doest not match ' . $this->getSetting('service_name'));
            return false;
        }
        $flag = null;
        $options = null;
        $this->setProfileLogPrefix($logPrefix);
        
        if (! empty($setting)) {
            list ($flag, $options) = $setting;
        }
        xhprof_enable($flag, $options);
    }

    /**
     * 结束分析
     *
     * @todo 添加写入文件方法
     * @return string
     */
    public function end()
    {
        if (! $this->registed) {
            $this->doEnd();
        }
    }

    /**
     * 结束分析
     *
     * @todo 添加写入文件方法
     * @return string
     */
    public function doEnd()
    {
        if ($this->autoServiceProfile() && ! $this->isServiceMatch()) {
            error_log('xhprof end : un match.' . $this->logPrefix . ' - ' . $this->getSetting('service_name'));
            return false;
        }
        $xhprof_data = xhprof_disable();
        $run_id = $this->saveRun($xhprof_data, "xhprof_crm", $this->getProfileLogPrefix());
        return $run_id;
    }

    /**
     * 服务名是否匹配
     *
     * @return boolean
     */
    private function isServiceMatch()
    {
        return $this->logPrefix === $this->getSetting('service_name');
    }

    /**
     * 保存执行结果
     *
     * @param 分析数据 $xhprof_data            
     * @param 类型 $type            
     * @param 文件id $run_id            
     * @return string
     */
    private function saveRun($xhprof_data, $type, $run_id = null)
    {
        $xhprof_data = serialize($xhprof_data);
        
        if ($run_id === null) {
            $run_id = uniqid();
        }
        
        $file_name = $this->outputFileName($run_id, $type);
        $file = fopen($file_name, 'w');
        
        if ($file) {
            fwrite($file, $xhprof_data);
            fclose($file);
        } else {
            error_log("Could not open $file_name\n");
        }
        
        // echo "Saved run in {$file_name}.\nRun id = {$run_id}.\n";
        return $run_id;
    }

    /**
     * 获取输出文件名
     *
     * @param 文件id $runId            
     * @param 类型 $type            
     * @return string
     */
    private function outputFileName($runId, $type)
    {
        $file = "$runId.$type";
        
        if ($this->getOutputPath()) {
            $file = $this->getOutputPath() . "/" . $file;
        }
        return $file;
    }

    /**
     * 获取配置文件路径
     *
     * @return string
     */
    private function getEnvFilePath()
    {
        return realpath(DIRECTORY_SEPARATOR . 'U8SOFT' . DIRECTORY_SEPARATOR . 'turbocrm70' . DIRECTORY_SEPARATOR . 'tsvr') . DIRECTORY_SEPARATOR . '.env';
    }

    /**
     * 获取日志前缀
     *
     * @return string
     */
    private function getProfileLogPrefix()
    {
        return $this->logPrefix . '_' . time();
    }

    /**
     * 设置分析日志前缀
     *
     * @param string $name            
     */
    private function setProfileLogPrefix($name)
    {
        $this->logPrefix = $name;
    }

    /**
     * 所需扩展名
     *
     * @var string
     */
    private $extension = 'xhprof';

    /**
     * 自身实例
     *
     * @var Profiler
     */
    private $profiler;

    /**
     * 是否注册shutdown函数
     */
    private $registed = false;

    /**
     * 默认配置
     *
     * @var array
     */
    private $defaultSetting = array(
        'all_service' => false
    );

    /**
     * 日志前缀
     *
     * @var string
     */
    private $logPrefix = '';

    private $configFile = '';

    const username = 'admin';

    const passwd = 'u8crm';
}