<?php
class Logger {
    private $file;

    public function __construct($file) {
        $this->file = $file;
    }

    public function info($msg, array $context = []) {
        $this->write('INFO', $msg, $context);
    }

    public function error($msg, array $context = []) {
        $this->write('ERROR', $msg, $context);
    }

    private function write($level, $msg, array $context = []) {
        $time = date('Y-m-d H:i:s');
        $line = "[$time] [$level] $msg";

        if (!empty($context)) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($this->file, $line . PHP_EOL, FILE_APPEND);
    }
}