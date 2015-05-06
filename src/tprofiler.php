<?php
/**
 * 性能分析工具
 *
 * @author albert
 */
include 'profilerinterface.lib';

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
            if (! class_exists($className, false)) {
                include (strtolower($className) . '.lib');
            }
            self::$profiler = new $className();
            if (! self::$profiler instanceof ProfilerInterface || ! self::$profiler->isProfilerEnable()) {
                include 'adapterprofile.lib';
                self::$profiler = new AdapterProfiler();
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