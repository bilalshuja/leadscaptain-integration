<?php

declare(strict_types=1);

namespace Leadscaptain\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Model;

final class LeadModel extends Model
{
    protected $table = 'leads';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'remote_created_at' => 'datetime',
    ];
}