<?php
declare(strict_types=1);

/**
 * Global Observer/Event Bus (Singleton).
 * O — Open/Closed: New listeners added without modifying existing code.
 * S — Single Responsibility: Only manages event subscription and dispatch.
 */

class EventBus
{
    private static ?EventBus $instance = null;

    /** @var array<string, callable[]> */

    private array $listeners = [];

    private function __construct() {}
    
    private function __clone() {}

    public static function getInstance(): EventBus
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function subscribe(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function publish(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }

    public function clearListeners(string $event = ''): void
    {
        if ($event) {
            unset($this->listeners[$event]);
        } else {
            $this->listeners = [];
        }
    }
}
