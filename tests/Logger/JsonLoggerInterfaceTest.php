<?php

namespace Neto\Test\Logger;

use Neto\Logger\JsonLogger;
use Psr\Log\LogLevel;
use Psr\Log\Test\LoggerInterfaceTest;

class JsonLoggerInterfaceTest extends LoggerInterfaceTest
{
    /** @var resource */
    private $outputHandler;

    public function getLogger()
    {
        $this->outputHandler = fopen('php://temp', 'w+');
        return new JsonLogger(LogLevel::DEBUG, $this->outputHandler);
    }

    public function getLogs()
    {
        rewind($this->outputHandler);
        $output = stream_get_contents($this->outputHandler);
        ftruncate($this->outputHandler, 0);

        $records = explode(PHP_EOL, trim($output));

        return array_map(function($record) {
            $json = json_decode($record, true);
            return $json['level'] . ' ' . $json['message'];
        }, $records);
    }

}