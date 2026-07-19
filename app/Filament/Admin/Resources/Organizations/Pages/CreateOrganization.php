<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Organizations\Pages;

use App\Actions\Organizations\CreateOrganization as CreateOrganizationAction;
use App\Filament\Admin\Resources\Organizations\OrganizationResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    /**
     * Crée l'organisation ET invite son premier administrateur (story 1.1) via
     * l'action métier, dans une transaction.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $adminEmail = (string) ($data['admin_email'] ?? '');
        unset($data['admin_email']);

        ['organization' => $organization, 'invitation' => $invitation] =
            (new CreateOrganizationAction)->handle($data, $adminEmail);

        if ($invitation === null) {
            Notification::make()
                ->warning()
                ->title('Organisation créée')
                ->body('L’invitation n’a pas pu être envoyée : cette adresse est peut-être déjà utilisée.')
                ->send();
        }

        return $organization;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
