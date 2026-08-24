<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\OnConflict;

trait OnConflictTrait
{
    protected ?OnConflict $onConflict = null;

    public function onConflictDoNothing(string|array $conflict): static
    {
        $this->onConflict = new OnConflict($conflict, null);

        return $this;
    }

    public function onConflictDoUpdate(string|array $conflict, array $updates = []): static
    {
        $this->onConflict = new OnConflict($conflict, $updates);

        return $this;
    }

    public function insertIgnore(string|array $conflict): static
    {
        return $this->onConflictDoNothing($conflict);
    }

    public function onDuplicateKeyUpdate(string|array $conflict, array $updates = []): static
    {
        return $this->onConflictDoUpdate($conflict, $updates);
    }
}
