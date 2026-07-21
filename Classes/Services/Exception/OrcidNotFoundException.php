<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services\Exception;

/**
 * Thrown when the ORCID API responds with a 404 for an ORCID iD,
 * the ID is syntactically valid but does not exist.
 */
class OrcidNotFoundException extends OrcidApiException {}
