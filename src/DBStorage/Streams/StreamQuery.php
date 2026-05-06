<?php

namespace Cyberma\LayerFrame\DBStorage\Streams;

use InvalidArgumentException;

final readonly class StreamQuery
{
    public StreamType $type;
    public int $chunkSize;
    public ?string $idColumn;
    public array $columns;

    private function __construct(StreamType $type, int $chunkSize = 1000, ?string $idColumn = null, array $columns = [])
    {
        $idColumn = $idColumn !== null ? trim($idColumn) : null;

        if ($chunkSize < 1) {
            throw new InvalidArgumentException('Stream chunk size must be greater than zero.');
        }

        if ($idColumn === '') {
            throw new InvalidArgumentException('Stream id column cannot be empty.');
        }

        if ($type !== StreamType::CHUNK_BY_ID && $idColumn !== null) {
            throw new InvalidArgumentException('Stream id column is supported only for chunkById.');
        }

        $this->type = $type;
        $this->chunkSize = $chunkSize;
        $this->idColumn = $idColumn;
        $this->columns = $columns;
    }

    public static function chunk(int $chunkSize = 1000, array $columns = []): self
    {
        return new self(StreamType::CHUNK, $chunkSize, null, $columns);
    }

    public static function chunkById(int $chunkSize = 1000, string $idColumn = 'id', array $columns = []): self
    {
        return new self(StreamType::CHUNK_BY_ID, $chunkSize, $idColumn, $columns);
    }

    public static function lazy(int $chunkSize = 1000, array $columns = []): self
    {
        return new self(StreamType::LAZY, $chunkSize, null, $columns);
    }

    public static function cursor(array $columns = []): self
    {
        return new self(StreamType::CURSOR, 1, null, $columns);
    }
}
