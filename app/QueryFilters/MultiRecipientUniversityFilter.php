<?php

namespace App\QueryFilters;

use Illuminate\Contracts\Database\Query\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class MultiRecipientUniversityFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereHas('recipients.recipient.userScopes', function ($query) use ($value) {
            $query
                ->where('scope_type', 'UNIVERSITY')
                ->join('universities', 'user_scopes.scope_id', '=', 'universities.id')
                ->where('universities.name', 'like', "%{$value}%");
        });
    }
}
