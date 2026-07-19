<?php

namespace App\Filament\App\Resources\TeamMembers\Pages;

use App\Filament\App\Resources\TeamMembers\TeamMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;
}
