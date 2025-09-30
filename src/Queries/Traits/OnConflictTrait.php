<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\OnConflict;

trait OnConflictTrait
{
    protected ?OnConflict $onConflict = null;

    public function onConflictIgnore(string|array $conflict): static
    {
        $this->onConflict = new OnConflict($conflict, null);

        return $this;
    }

    public function onConflictUpdate(string|array $conflict, array $updates = []): static
    {
        $this->onConflict = new OnConflict($conflict, $updates);

        return $this;
    }
}
