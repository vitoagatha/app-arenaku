<?php

namespace Database\Factories;

use App\Models\Lapangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class LapanganFactory extends Factory
{
    protected $model = Lapangan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Create a temporary image file and store it in the uploads directory
        $tempImage = $this->faker->image();
        $imagePath = Storage::disk('public')->putFile('uploads', new File($tempImage));

        return [
            'nama_lapangan' => $this->faker->word,
            'alamat' => $this->faker->address,
            'deskripsi' => $this->faker->sentence,
            'image' => $imagePath,
            'user_id' => User::inRandomOrder()->first()->id, // Assign a random user_id
        ];
    }
}
