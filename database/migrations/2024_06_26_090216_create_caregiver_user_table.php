<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('caregiver_user', function (Blueprint $table) {
            // each carevier has many patients (follow system)
            $table->id();
            
            $table->foreignId('caregiver_id')->constrained("caregivers")->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregiver_user');
    }
};
