<?php

namespace App\Services;

use RuntimeException;

/**
 * The book-search API failed, timed out or answered something unusable.
 */
class BookSearchUnavailable extends RuntimeException {}
