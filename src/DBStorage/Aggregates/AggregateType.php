<?php

namespace Cyberma\LayerFrame\DBStorage\Aggregates;

use InvalidArgumentException;

enum AggregateType: string
{
    case COUNT = 'count';
    case SUM = 'sum';
    case AVG = 'avg';
    case MIN = 'min';
    case MAX = 'max';
    case VALUE = 'value';
    case EXISTS = 'exists';

    public static function fromString(string $type): self
    {
        $normalized = strtolower(trim($type));
        $aggregateType = self::tryFrom($normalized);

        if ($aggregateType === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported aggregate type "%s". Allowed values: %s.',
                    $type,
                    implode(', ', self::values())
                )
            );
        }

        return $aggregateType;
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(
            static fn(self $type): string => $type->value,
            self::cases()
        );
    }

    public function requiresColumn(): bool
    {
        return in_array($this, [self::SUM, self::AVG, self::MIN, self::MAX, self::VALUE], true);
    }

    public function supportsColumn(): bool
    {
        return $this !== self::EXISTS;
    }
}
