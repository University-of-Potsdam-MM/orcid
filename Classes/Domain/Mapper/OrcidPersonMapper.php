<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Domain\Mapper;

use UniPotsdam\Orcid\Dto\Person;

class OrcidPersonMapper
{
    public function map(array $person): Person
    {
        $given = $person['name']['given-names']['value'] ?? '';
        $family = $person['name']['family-name']['value'] ?? '';

        return new Person($given, $family);
    }
}
