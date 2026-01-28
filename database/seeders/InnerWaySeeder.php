<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InnerWay;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InnerWaySeeder extends Seeder
{
    public function run(): void
    {
        $innerWayFiles = File::files(resource_path('icon/inner_way'));

        foreach ($innerWayFiles as $file) {
            $filenameWithExt = $file->getFilename();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $slug = Str::slug($filename);

            $innerWayData = [
                'name' => str_replace('-', ' ', $filename),
                'icon' => $filenameWithExt,
                'slug' => $slug,
            ];

            $innerWay = InnerWay::where('slug', $slug)->first();

            if (!$innerWay) {
                // Default color logic similar to DiscordController if new
                $color = 'blue';
                if ($file->getExtension() === 'png') {
                    $color = 'gold';
                }
                $innerWayData['color'] = $color;
                InnerWay::create($innerWayData);
            } else {
                // Update existing record with file data
                $innerWay->update($innerWayData);
            }
        }

        $this->command->info('Inner Ways seeded from files.');

        // After seeding base data, run the color seeder to ensure colors are correct
        $this->call(InnerWayColorSeeder::class);
    }
}
