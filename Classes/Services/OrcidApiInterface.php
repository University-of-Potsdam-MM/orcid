<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Services;

use UniPotsdam\Orcid\Dto\Person;
use UniPotsdam\Orcid\Dto\Work;
use UniPotsdam\Orcid\Dto\WorkList;
use UniPotsdam\Orcid\Value\OrcidId;

interface OrcidApiInterface
{
    public function fetchPerson(OrcidId $orcidId): Person;

    public function fetchWorkList(OrcidId $orcidId): WorkList;

    public function fetchWork(OrcidId $orcidId, int $putCode, Person $person): Work;
}
