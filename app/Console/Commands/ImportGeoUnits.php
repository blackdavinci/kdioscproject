<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GeoUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import idempotent du référentiel géographique national COD-AB (RG-21/22).
 *
 * Rejoue par P-code : création des nouvelles unités, mise à jour des renommages,
 * jamais de suppression physique. Avec --deactivate-missing (édition complète), les
 * unités absentes du fichier sont marquées inactive. Sans l'option, un fichier
 * partiel (ex. patch des communes de la zone spéciale de Conakry) s'ajoute sans
 * désactiver quoi que ce soit.
 */
class ImportGeoUnits extends Command
{
    protected $signature = 'geo:import
        {file? : Chemin du CSV (pcode,level,parent_pcode,name,center_lat,center_lon)}
        {--deactivate-missing : Marque inactive toute unité absente du fichier (édition complète)}';

    protected $description = 'Importe / met à jour le référentiel géographique national (COD-AB) de façon idempotente.';

    public function handle(): int
    {
        $file = $this->argument('file') ?? database_path('data/geo/gin_cod_ab_adm1_3.csv');

        if (! is_readable($file)) {
            $this->error("Fichier introuvable ou illisible : {$file}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($file);

        if ($rows === []) {
            $this->error('Aucune ligne exploitable dans le fichier.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $reactivated = 0;

        DB::transaction(function () use ($rows, &$created, &$updated, &$reactivated): void {
            // Passe 1 : upsert des attributs par P-code (sans le parent).
            foreach ($rows as $row) {
                $unit = GeoUnit::firstOrNew(['pcode' => $row['pcode']]);
                $wasInactive = $unit->exists && ! $unit->active;

                $unit->fill([
                    'level' => $row['level'],
                    'name' => $row['name'],
                    'center_lat' => $row['center_lat'],
                    'center_lon' => $row['center_lon'],
                    'active' => true,
                ]);

                if (! $unit->exists) {
                    $unit->save();
                    $created++;
                } elseif ($unit->isDirty()) {
                    $unit->save();
                    $updated++;
                    if ($wasInactive) {
                        $reactivated++;
                    }
                }
            }

            // Passe 2 : résolution des parents par P-code.
            $idByPcode = GeoUnit::query()->pluck('id', 'pcode');
            $orphans = [];

            foreach ($rows as $row) {
                if ($row['parent_pcode'] === '') {
                    continue;
                }

                $parentId = $idByPcode[$row['parent_pcode']] ?? null;

                if ($parentId === null) {
                    $orphans[] = $row['parent_pcode'];

                    continue;
                }

                GeoUnit::query()->where('pcode', $row['pcode'])->update(['parent_id' => $parentId]);
            }

            if ($orphans !== []) {
                $this->warn('P-codes parents introuvables (rapport RG-24) : '.implode(', ', array_unique($orphans)));
            }
        });

        $deactivated = 0;

        if ($this->option('deactivate-missing')) {
            $pcodes = array_column($rows, 'pcode');
            $deactivated = GeoUnit::query()->whereNotIn('pcode', $pcodes)->update(['active' => false]);
        }

        $this->info("Import terminé : {$created} créées, {$updated} mises à jour ({$reactivated} réactivées), {$deactivated} désactivées.");
        $this->line('Total en base : '.GeoUnit::query()->count().' unités.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{pcode: string, level: int, parent_pcode: string, name: string, center_lat: float|null, center_lon: float|null}>
     */
    protected function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        $rows = [];

        if ($header !== false) {
            $header = array_map(static fn ($column): string => (string) $column, $header);

            while (($data = fgetcsv($handle)) !== false) {
                /** @var array<string, string> $record */
                $record = array_combine($header, $data);

                $rows[] = [
                    'pcode' => trim($record['pcode']),
                    'level' => (int) $record['level'],
                    'parent_pcode' => trim($record['parent_pcode']),
                    'name' => trim($record['name']),
                    // Arrondi à la précision de la colonne decimal(10,7) pour une
                    // idempotence stricte au re-run (RG-22).
                    'center_lat' => $record['center_lat'] === '' ? null : round((float) $record['center_lat'], 7),
                    'center_lon' => $record['center_lon'] === '' ? null : round((float) $record['center_lon'], 7),
                ];
            }
        }

        fclose($handle);

        return $rows;
    }
}
