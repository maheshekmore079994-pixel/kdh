<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule violation that should be shown to the operator as a message.
 */
class DomainException extends RuntimeException {}
