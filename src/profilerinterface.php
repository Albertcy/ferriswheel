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
