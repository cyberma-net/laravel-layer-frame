<?php

namespace Cyberma\LayerFrame\DBStorage\Streams;

enum StreamType: string
{
    case CHUNK = 'chunk';
    case CHUNK_BY_ID = 'chunk_by_id';
    case LAZY = 'lazy';
    case CURSOR = 'cursor';
}
