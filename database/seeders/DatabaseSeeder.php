<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bungalow;
use App\Models\BungalowRoom;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $bungalows = [
            ['name' => 'বেরং কমপ্লেক্স'],
            ['name' => 'টিনশেড'],
            ['name' => 'কাঠের কটেজ'],
            ['name' => 'নতুন ডাকবাংলো'],
            ['name' => 'পুরাতন ডাকবাংলো'],
            ['name' => 'শিক্ষা প্রকৌশল'],
            ['name' => 'বন বিভাগ'],
            ['name' => 'জনস্বাস্থ্য প্রকৌশল'],
        ];

        foreach ($bungalows as $bungalow) {
            $createdBungalow = Bungalow::create($bungalow);
            // প্রতিটি বাংলোর জন্য দুটি করে রুম তৈরি করা হচ্ছে
            BungalowRoom::create([
                'bungalow_id' => $createdBungalow->id,
                'room_number' => '০১'
            ]);
            BungalowRoom::create([
                'bungalow_id' => $createdBungalow->id,
                'room_number' => '০৩'
            ]);
            BungalowRoom::create([
                'bungalow_id' => $createdBungalow->id,
                'room_number' => '০৪'
            ]);
            BungalowRoom::create([
                'bungalow_id' => $createdBungalow->id,
                'room_number' => '০৫'
            ]);
            BungalowRoom::create([
                'bungalow_id' => $createdBungalow->id,
                'room_number' => '০৬'
            ]);
        }
    }
}
