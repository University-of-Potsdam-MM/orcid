<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services\Exception;

use RuntimeException;

/**
 * Thrown when the ORCID API returns an unexpected (non-success) response.
 */
class OrcidApiException extends RuntimeException {}
