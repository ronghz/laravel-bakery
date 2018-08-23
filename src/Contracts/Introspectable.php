<?php

namespace Bakery\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;

interface Introspectable
{
    public function typename(): string;

    public function getModel(): Model;

    public function fields(): array;

    public function getFields(): Collection;

    public function getFillableFields(): Collection;

    public function relations(): array;

    public function getRelationFields(): Collection;

    public function getFillableRelationFields(): Collection;

    public function getRelations(): Collection;

    public function getConnections(): Collection;

    public function getBakeryQuery(): Builder;

    public function scopeQuery(Builder $builder): Builder;
}
