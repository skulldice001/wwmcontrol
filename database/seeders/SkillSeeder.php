<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skillFiles = File::files(resource_path('icon/skill'));

        foreach ($skillFiles as $file) {
            $filenameWithExt = $file->getFilename();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $slug = Str::slug($filename);

            Skill::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str_replace('-', ' ', $filename),
                    'icon' => $filenameWithExt,
                ]
            );
        }

        $this->command->info('Skills seeded from files.');
    }
}
