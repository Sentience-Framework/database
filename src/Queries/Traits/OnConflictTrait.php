<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\OnConflict;

trait OnConflictTrait
{
    protected ?OnConflict $onConflict = null;

    public function onConflictIgnore(string|array $conflict, ?string $primaryKey = null): static
    {
        $this->onConflict = new OnConflict($conflict, null, $primaryKey);

        return $this;
    }

    public function onConflictUpdate(string|array $conflict, array $updates = [], ?string $primaryKey = null): static
    {
        $this->onConflict = new OnConflict($conflict, $updates, $primaryKey);

        return $this;
    }
}
