<?php
include 'commandlineoutput.lib';
include 'commandlineinput.lib';
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
            include(strtolower($name) . '.lib');
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
FerrisWheel::start(new CommandLineInput(), new CommandLineOutput());