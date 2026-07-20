<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AgeBracket;
use App\Enums\DisaggregationPhase;
use App\Enums\Sex;
use App\Filament\App\Resources\Activities\Support\ActivityDisaggregation;
use App\Models\Activity;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Formulaires papier d'une activité (RGA-09) : fiche d'activité et liste de
 * présence, cohérentes avec les écrans de saisie, pour le circuit terrain.
 */
class ActivityPdfController extends Controller
{
    public function sheet(Activity $activity): Response
    {
        $this->authorize($activity);

        $planned = ActivityDisaggregation::load($activity, DisaggregationPhase::Planned);

        return Pdf::loadView('activities.sheet', [
            'activity' => $activity,
            'planned' => $planned,
            'sexes' => Sex::cases(),
            'brackets' => AgeBracket::cases(),
        ])->download('fiche-activite-'.$activity->id.'.pdf');
    }

    public function attendance(Activity $activity): Response
    {
        $this->authorize($activity);

        return Pdf::loadView('activities.attendance', [
            'activity' => $activity,
            'brackets' => AgeBracket::cases(),
            'rows' => range(1, 25),
        ])->download('liste-presence-'.$activity->id.'.pdf');
    }

    protected function authorize(Activity $activity): void
    {
        $user = Auth::guard('web')->user();

        abort_unless(
            $user instanceof User
                && $user->organization_id === $activity->organization_id
                && ! $user->hasRole('bailleur'),
            403,
        );
    }
}
