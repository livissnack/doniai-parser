<?php

namespace ManaPHP\Logging;

use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Coroutine;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Logging\Logger\Log;
use ManaPHP\Logging\Logger\LogCategorizable;
use Throwable;
use ArrayObject;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class LoggerContext
{
    /**
     * @var int
     */
    public $level;

    /**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $request_id;
}

/**
 * @property-read \ManaPHP\AliasInterface        $alias
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Logging\LoggerContext $context
 */
abstract class Logger extends Component implements LoggerInterface
{
    const LEVEL_FATAL = 10;
    const LEVEL_ERROR = 20;
    const LEVEL_WARN = 30;
    const LEVEL_INFO = 40;
    const LEVEL_DEBUG = 50;

    /**
     * @var int
     */
    protected $level;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var array
     */
    protected static $levels
        = [
            self::LEVEL_FATAL => 'fatal',
            self::LEVEL_ERROR => 'error',
            self::LEVEL_WARN  => 'warn',
            self::LEVEL_INFO  => 'info',
            self::LEVEL_DEBUG => 'debug'
        ];

    /**
     * @var float
     */
    protected $lazy;

    /**
     * @var int
     */
    protected $buffer_size = 1024;

    /**
     * @var float
     */
    protected $last_write;

    /**
     * @var \ManaPHP\Logging\Logger\Log[]
     */
    protected $logs = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['level'])) {
            $this->level = $this->self->parseLevel($options['level']);
        } else {
            $error_level = error_reporting();

            if ($error_level & E_ERROR) {
                $this->level = self::LEVEL_ERROR;
            } elseif ($error_level & E_WARNING) {
                $this->level = self::LEVEL_WARN;
            } elseif ($error_level & E_NOTICE) {
                $this->level = self::LEVEL_INFO;
            } else {
                $this->level = self::LEVEL_DEBUG;
            }
        }

        $this->lazy = MANAPHP_CLI ? false : $options['lazy'] ?? true;

        if (isset($options['buffer_size'])) {
            $this->buffer_size = (int)$options['buffer_size'];
        }

        $this->hostname = $options['hostname'] ?? gethostname();

        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    /**
     * @return LoggerContext
     */
    protected function createContext()
    {
        /** @var \ManaPHP\Logging\LoggerContext $context */
        $context = parent::createContext();

        $context->level = $this->level;
        $context->client_ip = MANAPHP_CLI ? '' : $this->request->getClientIp();
        $context->request_id = $this->request->getRequestId();

        return $context;
    }

    /**
     * @return void
     */
    public function onRequestEnd()
    {
        if ($this->logs) {
            $this->self->append($this->logs);
            $this->logs = [];
        }
    }

    /**
     * @param int|string $level
     *
     * @return int
     */
    protected function parseLevel($level)
    {
        $r = is_numeric($level) ? (int)$level : array_search($level, self::$levels, true);
        if (!is_int($r) || !isset(self::$levels[$r])) {
            throw new InvalidValueException('logger `:level` level is invalid', ['level' => $level]);
        }

        return $r;
    }

    /**
     * @param int|string $level
     *
     * @return static
     */
    public function setLevel($level)
    {
        $this->context->level = $this->self->parseLevel($level);

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->context->level;
    }

    /**
     * @return array
     */
    public function getLevels()
    {
        return self::$levels;
    }

    /**
     * @param bool $lazy
     *
     * @return static
     */
    public function setLazy($lazy = true)
    {
        $this->lazy = $lazy;

        return $this;
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    abstract public function append($logs);

    /**
     * @param array $traces
     *
     * @return array
     */
    protected function getLocation($traces)
    {
        for ($i = count($traces) - 1; $i >= 0; $i--) {
            $trace = $traces[$i];
            $function = $trace['function'];

            if (in_array($function, self::$levels, true)) {
                return $trace;
            } elseif (str_starts_with($function, 'log_') && in_array(substr($function, 4), self::$levels, true)) {
                return $trace;
            }
        }

        return [];
    }

    /**
     * @param array $traces
     *
     * @return string
     */
    protected function inferCategory($traces)
    {
        foreach ($traces as $trace) {
            if (isset($trace['object'])) {
                $object = $trace['object'];
                if (Reflection::isInstanceOf($object, LogCategorizable::class)) {
                    return $object->categorizeLog();
                }
            }
        }
        return 'unknown';
    }

    /**
     * @param \Throwable $exception
     *
     * @return string
     */
    public function exceptionToString($exception)
    {
        $str = get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . get_class($caused) . ': ' . $caused->getMessage() . PHP_EOL;
            $str .= '    at ' . $caused->getFile() . ':' . $caused->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            if ($traces !== $prev) {
                $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            } else {
                $str .= '    at ...';
            }

            $prev = $traces;
        }

        $replaces = [];
        if ($this->alias->has('@root')) {
            $replaces[dirname(realpath($this->alias->get('@root'))) . DIRECTORY_SEPARATOR] = '';
        }

        return strtr($str, $replaces);
    }

    /**
     * @param \Throwable|array|\JsonSerializable $message
     *
     * @return string
     */
    public function formatMessage($message)
    {
        if ($message instanceof Throwable) {
            return $this->self->exceptionToString($message);
        } elseif ($message instanceof JsonSerializable || $message instanceof ArrayObject) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif (!is_array($message)) {
            return (string)$message;
        }

        if (!isset($message[0]) || !is_string($message[0])) {
            return json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        if (substr_count($message[0], '%') + 1 >= ($count = count($message)) && isset($message[$count - 1])) {
            foreach ((array)$message as $k => $v) {
                if ($k === 0 || is_scalar($v) || $v === null) {
                    continue;
                }

                if ($v instanceof Throwable) {
                    $message[$k] = $this->self->exceptionToString($v);
                } elseif (is_array($v)) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                } elseif ($v instanceof JsonSerializable || $v instanceof ArrayObject) {
                    $message[$k] = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
                }
            }
            return sprintf(...$message);
        }

        if (count($message) === 2) {
            if (isset($message[1]) && !str_contains($message[0], ':1')) {
                $message[0] = rtrim($message[0], ': ') . ': :1';
            }
        } elseif (count($message) === 3) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (isset($message[1], $message[2]) && !str_contains($message[0], ':1') && is_scalar($message[1])) {
                $message[0] = rtrim($message[0], ': ') . ': :1 => :2';
            }
        }

        $replaces = [];
        foreach ($message as $k => $v) {
            if ($k === 0) {
                continue;
            }

            if ($v instanceof Throwable) {
                $v = $this->self->exceptionToString($v);
            } elseif (is_array($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif ($v instanceof JsonSerializable) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif (is_string($v)) {
                null;
            } elseif ($v === null || is_scalar($v)) {
                $v = json_stringify($v, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                $v = (string)$v;
            }

            $replaces[":$k"] = $v;
        }

        return strtr($message[0], $replaces);
    }

    /**
     * @param int          $level
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function log($level, $message, $category = null)
    {
        $context = $this->context;

        if ($level > $context->level) {
            return $this;
        }

        if ($category !== null && !is_string($category)) {
            $message = [$message . ': :param', 'param' => $category];
            $category = null;
        }

        if (is_array($message) && count($message) === 1 && isset($message[0])) {
            $message = $message[0];
        }

        $log = new Log();

        $log->hostname = $this->hostname;
        $log->client_ip = $context->client_ip;
        $log->level = self::$levels[$level];
        $log->request_id = $context->request_id ?: $this->request->getRequestId();

        if ($message instanceof Throwable) {
            $log->category = $category ?: 'exception';
            $log->file = basename($message->getFile());
            $log->line = $message->getLine();
        } else {
            $traces = Coroutine::getBacktrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 7);
            if ($category !== null && $category[0] === '.') {
                $log->category = $this->self->inferCategory($traces) . $category;
            } else {
                $log->category = $category ?: $this->self->inferCategory($traces);
            }

            $location = $this->self->getLocation($traces);
            if (isset($location['file'])) {
                $log->file = basename($location['file']);
                $log->line = $location['line'];
            } else {
                $log->file = '';
                $log->line = 0;
            }
        }

        $log->message = is_string($message) ? $message : $this->self->formatMessage($message);
        $log->timestamp = microtime(true);

        $this->fireEvent('logger:log', compact('level', 'message', 'category', 'log'));

        if ($this->lazy) {
            $this->logs[] = $log;

            if ($this->last_write === null) {
                $this->last_write = $log->timestamp;
            } elseif ($log->timestamp - $this->last_write > 1 || count($this->logs) > $this->buffer_size) {
                $this->last_write = $log->timestamp;

                $this->self->append($this->logs);
                $this->logs = [];
            }
        } else {
            $this->self->append([$log]);
        }

        return $this;
    }

    /**
     * Sends/Writes a debug message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function debug($message, $category = null)
    {
        return $this->self->log(self::LEVEL_DEBUG, $message, $category);
    }

    /**
     * Sends/Writes an info message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function info($message, $category = null)
    {
        return $this->self->log(self::LEVEL_INFO, $message, $category);
    }

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function warn($message, $category = null)
    {
        return $this->self->log(self::LEVEL_WARN, $message, $category);
    }

    /**
     * Sends/Writes an error message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function error($message, $category = null)
    {
        return $this->self->log(self::LEVEL_ERROR, $message, $category);
    }

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return static
     */
    public function fatal($message, $category = null)
    {
        return $this->self->log(self::LEVEL_FATAL, $message, $category);
    }

    public function dump()
    {
        $data = parent::dump();

        unset($data['logs'], $data['last_write']);

        return $data;
    }
}