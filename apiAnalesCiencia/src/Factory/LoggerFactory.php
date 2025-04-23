<?php

namespace TDW\ACiencia\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\{ Handler, Level, Logger };
use Psr\Log\LoggerInterface;

/**
 * Factory.
 */
class LoggerFactory
{
    private string $path;

    private Level $level;

    private int $file_permission;

    /** @var Handler\HandlerInterface[] $handler  */
    private array $handler = [];

    /**
     * The constructor.
     *
     * @param array<string,mixed> $settings The settings
     */
    public function __construct(array $settings = [])
    {
        $this->path = (string) ($settings['path'] ?? '');
        $this->level = $settings['level'] ?? Level::Debug;
        $this->file_permission = (int) ($settings['file_permission'] ?? 777);
    }

    /**
     * Build the logger.
     *
     * @param string $name The name
     *
     * @return LoggerInterface The logger
     */
    public function createInstance(string $name): LoggerInterface
    {
        $logger = new Logger($name);

        foreach ($this->handler as $handler) {
            $logger->pushHandler($handler);
        }
        $this->handler = [];

        return $logger;
    }

    /**
     * Add a handler.
     *
     * @param Handler\HandlerInterface $handler The handler
     *
     * @return self The logger factory
     */
    public function addHandler(Handler\HandlerInterface $handler): self
    {
        $this->handler[] = $handler;

        return $this;
    }

    /**
     * Add rotating file logger handler.
     *
     * @param string $filename The filename
     * @param Level|null $level The level (optional)
     *
     * @return LoggerFactory The logger factory
     */
    public function addFileHandler(string $filename, ?Level $level = null): self
    {
        $filename = sprintf('%s/%s', $this->path, $filename);
        $rotatingFileHandler = new Handler\RotatingFileHandler(
            filename: $filename,
            maxFiles: 0,
            level: $level ?? $this->level,
            bubble: true,
            filePermission: $this->file_permission
        );

        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, true, false));
        $this->addHandler($rotatingFileHandler);

        return $this;
    }

    /**
     * Add a console logger.
     *
     * @param Level|null $level The level (optional)
     *
     * @return self The instance
     */
    public function addConsoleHandler(?Level $level = Level::Debug): self
    {
        $streamHandler = new Handler\StreamHandler('php://output', $level ?? $this->level);
        $streamHandler->setFormatter(new LineFormatter(null, null, true, false));
        $this->addHandler($streamHandler);

        return $this;
    }
}
