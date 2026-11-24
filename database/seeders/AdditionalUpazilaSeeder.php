<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Database\Seeder;

/**
 * Adds missing upazilas to existing districts. Safe to run on production:
 * it only inserts rows that do not exist yet (never updates or deletes),
 * and continues each district's code sequence (e.g. D02-05-NN).
 */
class AdditionalUpazilaSeeder extends Seeder
{
    /** [division, district, upazila, upazila_bn] */
    private const ROWS = [
        ['Barishal', 'Bhola', 'Bhola Sadar', 'ভোলা সদর'],
        ['Chattogram', 'Chattogram', 'Fatikchhari Uttar', 'ফটিকছড়ি উত্তর'],
        ['Chattogram', "Cox's bazar", 'Eidgaon', 'ঈদগাঁও'],
        ['Chattogram', 'Cumilla', 'Bangara', 'বাঙ্গরা'],
        ['Chattogram', 'Laxmipur', 'Chandraganj', 'চন্দ্রগঞ্জ'],
        ['Dhaka', 'Madaripur', 'Dasar', 'দাসার'],
        ['Mymensingh', 'Mymensingh', 'South Gafargaon', 'দক্ষিণ গফরগাঁও'],
        ['Rajshahi', 'Bogura', 'Mokamtala', 'মোকামতলা'],
        ['Rangpur', 'Thakurgaon', 'Bhulli', 'ভুল্লি'],
        ['Rangpur', 'Thakurgaon', 'Ruhia', 'রুহিয়া'],
        ['Sylhet', 'Sunamganj', 'Madhyanagar', 'মধ্যনগর'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as [$divisionName, $districtName, $name, $nameBn]) {
            $division = Division::where('name', $divisionName)->first();
            $district = $division
                ? District::where('division_id', $division->id)->where('name', $districtName)->first()
                : null;

            if (! $district) {
                $this->command?->warn("Skipped {$name}: district {$divisionName} / {$districtName} not found.");
                continue;
            }

            if (Upazila::where('district_id', $district->id)->where('name', $name)->exists()) {
                $this->command?->line("Exists: {$name}");
                continue;
            }

            $next = Upazila::where('district_id', $district->id)->count() + 1;
            do {
                $code = sprintf('%s-%02d', $district->code, $next++);
            } while (Upazila::where('code', $code)->exists());

            Upazila::create([
                'district_id' => $district->id,
                'name' => $name,
                'name_bn' => $nameBn,
                'code' => $code,
            ]);
            $this->command?->info("Added: {$name} ({$code})");
        }
    }
}
