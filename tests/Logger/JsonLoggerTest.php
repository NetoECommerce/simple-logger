<?php

namespace Neto\Test\Logger;

use Neto\Logger\JsonLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class JsonLoggerTest extends TestCase
{
    /** @var JsonLogger */
    private $logger;

    /** @var resource */
    private $outputHandler;

    public function setUp()
    {
        $this->outputHandler = fopen('php://temp', 'w+');
        $this->logger = new JsonLogger(LogLevel::DEBUG, $this->outputHandler);
    }

    /**
     * @return false|string
     */
    private function getOutput()
    {
        rewind($this->outputHandler);
        $output = stream_get_contents($this->outputHandler);
        ftruncate($this->outputHandler, 0);
        return $output;
    }

    public function logLevelProvider()
    {
        return [
            'Debug'     => [ LogLevel::DEBUG ],
            'Info'      => [ LogLevel::INFO ],
            'Notice'    => [ LogLevel::NOTICE ],
            'Warning'   => [ LogLevel::WARNING ],
            'Error'     => [ LogLevel::ERROR ],
            'Critical'  => [ LogLevel::CRITICAL ],
            'Alert'     => [ LogLevel::ALERT ],
            'Emergency' => [ LogLevel::EMERGENCY ]
        ];
    }

    /**
     * @param string $level
     * @dataProvider logLevelProvider
     */
    public function testLogIncludesAllData($level)
    {
        $this->logger->log($level, 'Danger to {foo}', [ 'foo' => 'manifold' ]);
        $output = $this->getOutput();

        $this->assertJson($output);
        $log = json_decode($output, true);
        $this->assertArrayHasKey('timestamp', $log);
        $this->assertArrayHasKey('level', $log);
        $this->assertArrayHasKey('message', $log);
        $this->assertArrayHasKey('context', $log);

        $this->assertEquals($level, $log['level']);
        $this->assertEquals('Danger to manifold', $log['message']);
        $this->assertEquals([ 'foo' => 'manifold' ], $log['context']);
    }

    public function minimumLogLevelProvider()
    {
        return [
            'Debug'     => [ LogLevel::DEBUG, false ],
            'Info'      => [ LogLevel::INFO, false ],
            'Notice'    => [ LogLevel::NOTICE, false ],
            'Warning'   => [ LogLevel::WARNING, true ],
            'Error'     => [ LogLevel::ERROR, true ],
            'Critical'  => [ LogLevel::CRITICAL, true ],
            'Alert'     => [ LogLevel::ALERT, true ],
            'Emergency' => [ LogLevel::EMERGENCY, true ]
        ];
    }

    /**
     * @param string $level
     * @param bool   $isOutputExpected
     * @dataProvider minimumLogLevelProvider
     */
    public function testLoggerOnlyLogsMinimumLevel($level, $isOutputExpected)
    {
        $logger = new JsonLogger(LogLevel::WARNING, $this->outputHandler);
        $logger->log($level, 'foo');

        if ($isOutputExpected) {
            $this->assertNotEmpty($this->getOutput());
        } else {
            $this->assertEmpty($this->getOutput());
        }
    }

    public function typeProvider()
    {
        return [
            'string'   => [ 'foo', 'foo' ],
            'integer'  => [ 1, '1' ],
            'toString' => [ new FooClass(), 'foo' ],
            'null'     => [ null, '[NULL]' ],
            'DateTime' => [ new \DateTime('2010-01-28T15:00:00+02:00'), '2010-01-28T15:00:00+0200' ],
            'object'   => [ new \DateTimeZone('Australia/Brisbane'), '[object DateTimeZone]' ],
            'array'    => [ [ 'foo' ], '[array]' ],
        ];
    }

    /**
     * @param mixed $value
     * @param string $expected
     * @dataProvider typeProvider
     */
    public function testMessageInterpolation($value, $expected)
    {
        $this->logger->log(LogLevel::WARNING, '{foo}', [ 'foo' => $value ]);
        $output = $this->getOutput();

        $this->assertJson($output);

        $log = json_decode($output, true);
        $this->assertEquals($expected, $log['message']);
    }
}

class FooClass
{
    public function __toString()
    {
        return 'foo';
    }
}
