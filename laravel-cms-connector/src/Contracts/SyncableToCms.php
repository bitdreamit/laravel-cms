<?php

namespace Platform\CmsConnector\Contracts;

interface SyncableToCms
{
    public function toCmsEntryData(): array;
    public static function fromCmsEntryData(array $data): static;
}
