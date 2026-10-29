<?php

namespace App\Console\Commands;

use App\Services\PartSearch;
use App\Services\VinResolver;
use Illuminate\Console\Command;

class CatalogVin extends Command
{
    protected $signature = 'catalog:vin {vin} {category? : shop category slug, e.g. ferrota}';

    protected $description = 'See what a customer would see for a VIN: vehicles, categories, offers';

    public function handle(VinResolver $resolver, PartSearch $search): int
    {
        $result = $resolver->resolve($this->argument('vin'));
        $this->line("VIN {$result->vin}: {$result->status}".($result->corrected ? ' (auto-corrected)' : '').($result->fromCache ? ' [cache]' : ''));
        if ($result->vehicles->isEmpty()) {
            return self::SUCCESS;
        }

        $this->table(['id', 'make', 'model', 'engine', 'kW', 'hp', 'fuel', 'years'], $result->vehicles->map(fn ($v) => [
            $v->id, $v->manufacturer, $v->model, $v->name, $v->kw, $v->hp, $v->fuel, "{$v->year_from}-{$v->year_to}",
        ]));

        $vehicleId = $result->vehicles->first()->id;
        if ($result->vehicles->count() > 1) {
            $this->warn("Several vehicles. Showing parts for the first one ($vehicleId); the customer would choose.");
        }

        $this->table(['category', 'slug', 'parts'], $search->categoriesForVehicle($vehicleId)->map(fn ($c) => [$c->name_sq, $c->slug, $c->parts]));

        if ($slug = $this->argument('category')) {
            $this->table(['brand', 'part no', 'name', 'price', 'qty', 'vendor'], $search->offers($vehicleId, $slug)->map(fn ($o) => [
                $o->brand, $o->article_no, $o->raw_name ?? $o->article_name, $o->price, $o->qty, $o->vendor,
            ]));
        }

        return self::SUCCESS;
    }
}
