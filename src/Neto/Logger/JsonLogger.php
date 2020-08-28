<?php declare(strict_types=1);

namespace Neto\Logger;

use JsonException;
use Psr\Log\InvalidArgumentException;

/**
 * Class JsonLogger
 * @package Neto\Lambda
 */
class JsonLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function log($level, $message, array $context = [])
    {
        if (!in_array($level, self::LEVELS)) {
            throw new InvalidArgumentException("Level $level is not a valid log level.");
        }

        if ($this->isUnderMinimumLevel($level)) {
            return;
        }

        $json = json_encode([
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $this->interpolate((string)$message, $context),
            'context' => $this->sanitise($context)
        ], JSON_THROW_ON_ERROR);

        fwrite($this->outputHandler, $json . PHP_EOL);
    }

    /**
     * Makes the context safe to JSON encode
     *
     * @param array $context
     * @return array
     */
    public function sanitise(array $context)
    {
        foreach ($context as $key => &$value) {
            // is_resource() will return false for closed resources :(
            // this is a dumb solution and I hate php for this.
            if (is_resource($value) ||
                (!is_null($value) && !is_scalar($value) && !is_array($value) && !is_object($value))) {
                $value = '[resource ' . get_resource_type($value) . ']';
            }
        }

        return $context;
    }
}
