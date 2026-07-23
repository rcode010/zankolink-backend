<?php

namespace App\QueryFilters;

use Illuminate\Contracts\Database\Query\Builder;

class MultiRecipientUniversityFilter implements \Spatie\QueryBuilder\Filters\Filter
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
