<?php

namespace Cyberma\LayerFrame\DBStorage\Mutations;

enum MutationType: string
{
    case UPDATE = 'update';
    case DELETE = 'delete';
    case INCREMENT = 'increment';
    case DECREMENT = 'decrement';
}
