<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class JobSearch
{
    public static function apply(Builder $query, string $keywords, string $location): Builder
    {
        $keywords = strtolower(trim($keywords));
        $location = strtolower(trim($location));

        if ($keywords !== '') {
            $query->where(function (Builder $query) use ($keywords): void {
                $query->whereRaw('LOWER(title) like ?', ['%'.$keywords.'%'])
                    ->orWhereRaw('LOWER(description) like ?', ['%'.$keywords.'%'])
                    ->orWhereRaw('LOWER(tags) like ?', ['%'.$keywords.'%']);
            });
        }

        if ($location !== '') {
            $query->where(function (Builder $query) use ($location): void {
                $query->whereRaw('LOWER(address) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(city) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(state) like ?', ['%'.$location.'%'])
                    ->orWhereRaw('LOWER(zipcode) like ?', ['%'.$location.'%']);
            });
        }

        return $query;
    }
}
