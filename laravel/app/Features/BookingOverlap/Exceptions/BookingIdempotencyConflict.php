<?php

namespace App\Features\BookingOverlap\Exceptions;

use RuntimeException;

final class BookingIdempotencyConflict extends RuntimeException {}
