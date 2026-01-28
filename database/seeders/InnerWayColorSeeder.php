<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InnerWay;

class InnerWayColorSeeder extends Seeder
{
    public function run(): void
    {
        // Reset all colors to blue first (default color)
        InnerWay::query()->update(['color' => 'blue']);

        $colors = [
            'gold' => [
                'echoes-of-oblivion',
                'exquisite-scenery',
                'sword-morph',
                'morale-chant',
                'blossom-barrage',
                'royal-remedy',
                'seasonal-edge',
                'sword-horizon',
            ],
            'purple' => [
                'bitter-seasons',
                'adaptive-steel',
                'art-of-resistance',
                'battle-anthem',
                'fury-harvest',
                'vital-leech',
                'divine-roulette',
                'envigorated warrior',
                'insightful-strike',
                'mountains might',
                'mending-loom',
                'esoteric-revival',
                'restoring-blossom',
                'trapped-beast',
                'flying-gourd',
                'riptide-reflex',
                'rock-solid',
                'thunderous-bloom',
                'vendetta-internal',
                'wildfire-spark',
                'wolfchasers-art'

            ],
            // rest are blue
        ];

        foreach ($colors as $color => $slugs) {
            InnerWay::whereIn('slug', $slugs)->update(['color' => $color]);
        }

        // Ensure others are blue
        InnerWay::whereNotIn('color', ['gold', 'purple'])->update(['color' => 'blue']);
    }
}
