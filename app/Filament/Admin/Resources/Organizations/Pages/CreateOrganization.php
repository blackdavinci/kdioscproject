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
        $adminFullName = trim(((string) ($data['admin_first_name'] ?? '')).' '.((string) ($data['admin_last_name'] ?? '')));
        $adminPhone = isset($data['admin_phone']) ? (string) $data['admin_phone'] : null;

        unset($data['admin_email'], $data['admin_first_name'], $data['admin_last_name'], $data['admin_phone']);

        ['organization' => $organization, 'invitation' => $invitation] =
            (new CreateOrganizationAction)->handle($data, $adminEmail, $adminFullName, $adminPhone);

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
