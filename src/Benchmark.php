<?php

namespace PadConverter;

final class Benchmark
{
    protected static int $startTime;

    public static function start()
    {
        self::$startTime = time();
    }

    public static function stop(): string
    {
        $stop_time = time();
        $elapsed_time = $stop_time - self::$startTime;
        $h = floor($elapsed_time / 3600);
        $m = floor(($elapsed_time % 3600) / 60);
        $s = ($elapsed_time % 3600) % 60;
        return "{$h}h{$m}m:{$s}s";
    }
}