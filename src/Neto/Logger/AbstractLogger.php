<?php declare(strict_types=1);

namespace Neto\Logger;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Class AbstractLogger
 * @package Neto\Logger
 */
abstract class AbstractLogger implements LoggerInterface
{
    use LoggerTrait;

    const LEVELS = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY
    ];

    /** @var string */
    protected $minimumLevel;

    /** @var null|resource */
    protected $outputHandler;

    /**
     * AbstractLogger constructor.
     * @param string $minimumLevel Minimum log level we wish to output.
     * @param resource|null $outputHandler Resource to write output to.
     */
    public function __construct($minimumLevel, $outputHandler = null)
    {
        $this->minimumLevel = $minimumLevel;
        $this->outputHandler = $outputHandler ?? STDOUT;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws InvalidArgumentException
     */
    abstract public function log($level, $message, array $context = []);

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate(string $message, array $context)
    {
        if (strpos($message, '{') === false) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || method_exists($value, '__toString')) {
                $replace["{{$key}}"] = $value;
            } elseif ($value instanceof \DateTimeInterface) {
                $replace["{{$key}}"] = $value->format(\DateTimeInterface::ISO8601);
            } elseif (is_object($value)) {
                $replace["{{$key}}"] = '[object ' . get_class($value) . ']';
            } else {
                $replace["{{$key}}"] = '[' . gettype($value) . ']';
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Returns true if log level is underneath minimum log threshold
     *
     * @param string $level
     * @return bool
     */
    protected function isUnderMinimumLevel($level)
    {
        return array_search($level, self::LEVELS) < array_search($this->minimumLevel, self::LEVELS);
    }
}
