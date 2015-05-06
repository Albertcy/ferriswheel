<?php

/**
 * xhprof ��������
 * @author albert
 *
 */
class XhprofProfiler implements ProfilerInterface
{

    /**
     * ��ȡ������Ϣ
     *
     * @param string $name            
     * @return boolean
     */
    private function getSetting($name)
    {
        return empty($this->settings[$name]) ? false : $this->settings[$name];
    }

    /**
     * ���������ļ�
     */
    private function parseSetting()
    {
        $this->configFile = $this->getEnvFilePath();
        if ($this->configFile) {
            $this->settings = parse_ini_file($this->getEnvFilePath());
        }
    }

    /**
     * ��ʼ�����ܷ�������
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
     * �����Է������ܷ����Ƿ����
     *
     * @return boolean
     */
    public function isServiceProfileAvailable()
    {
        return $this->autoServiceProfile() || $this->pageProfileavailable();
    }

    /**
     * ҳ�����ܷ����Ƿ����
     *
     * @return boolean
     */
    private function pageProfileAvailable()
    {
        return $this->getSetting('page_profile') && $_GET['profile'];
    }

    /**
     * �Ƿ�����ȫ��������
     *
     * @return boolean
     */
    private function autoServiceProfile()
    {
        return $this->getSetting('all_service');
    }

    /**
     * �����֤
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
     * ע��shutdown ����
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
     * �����Ƿ����
     *
     * @return boolean
     */
    public function isProfilerEnable()
    {
        return extension_loaded($this->extension) && $this->getOutputPath();
    }

    /**
     * ��ȡ����ļ�·��
     *
     * @return string
     */
    private function getOutputPath()
    {
        return ini_get('xhprof.output_dir');
    }

    /**
     * ��ʼ����
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
        if (! empty($setting)) {
            list ($flag, $options) = $setting;
        }
        xhprof_enable($flag, $options);
    }

    /**
     * ��������
     * 
     * @return string
     */
    public function end()
    {
        if (! $this->registed) {
            $this->doEnd();
        }
    }

    /**
     * ��������
     *
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
     * �������Ƿ�ƥ��
     *
     * @return boolean
     */
    private function isServiceMatch()
    {
        return $this->logPrefix === $this->getSetting('service_name');
    }

    /**
     * ����ִ�н��
     *
     * @param �������� $xhprof_data            
     * @param ���� $type            
     * @param �ļ�id $run_id            
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
     * ��ȡ����ļ���
     *
     * @param �ļ�id $runId            
     * @param ���� $type            
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
     * ��ȡ�����ļ�·��
     *
     * @return string
     */
    private function getEnvFilePath()
    {
        return realpath(DIRECTORY_SEPARATOR . 'U8SOFT' . DIRECTORY_SEPARATOR . 'turbocrm70' . DIRECTORY_SEPARATOR . 'tsvr') . DIRECTORY_SEPARATOR . '.env';
    }

    /**
     * ��ȡ��־ǰ׺
     *
     * @return string
     */
    private function getProfileLogPrefix()
    {
        return $this->logPrefix . '_' . time();
    }

    /**
     * ���÷�����־ǰ׺
     *
     * @param string $name            
     */
    private function setProfileLogPrefix($name)
    {
        $this->logPrefix = $name;
    }

    /**
     * ������չ��
     *
     * @var string
     */
    private $extension = 'xhprof';

    /**
     * ����ʵ��
     *
     * @var Profiler
     */
    private $profiler;

    /**
     * �Ƿ�ע��shutdown����
     */
    private $registed = false;

    /**
     * Ĭ������
     *
     * @var array
     */
    private $defaultSetting = array(
        'all_service' => false
    );

    /**
     * ��־ǰ׺
     *
     * @var string
     */
    private $logPrefix = '';

    private $configFile = '';

    const username = 'admin';

    const passwd = 'u8crm';
}