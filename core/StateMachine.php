<?php
declare(strict_types=1);

/**
 * Reusable Generic State Machine.
 * O — Open/Closed: Transitions configurable without modifying this class.
 * S — Single Responsibility: Only manages state transitions and guards.
 */

class StateMachine
{
    private array $transitions;
    private string $currentState;

    /**
     * @param string $initialState Current state of the entity
     * @param array  $transitions  Map of state => [allowed next states]
     */
    public function __construct(string $initialState, array $transitions)
    {
        $this->currentState = $initialState;
        $this->transitions = $transitions;
    }

    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    public function canTransitionTo(string $toState): bool
    {
        $allowed = $this->transitions[$this->currentState] ?? [];
        return in_array($toState, $allowed, true);
    }

    public function getAllowedTransitions(): array
    {
        return $this->transitions[$this->currentState] ?? [];
    }

    /**
     * @throws \DomainException If transition is not allowed
     */
    public function transitionTo(string $toState): string
    {
        if (!$this->canTransitionTo($toState)) {
            throw new \DomainException(
                "Invalid transition from '{$this->currentState}' to '{$toState}'. Allowed: "
                . implode(', ', $this->getAllowedTransitions())
            );
        }
        $from = $this->currentState;
        $this->currentState = $toState;
        return $from;
    }
}
