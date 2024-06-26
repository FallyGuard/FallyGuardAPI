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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string("family_name")->nullable();
            $table->string('photo')->nullable();
            
            $table->string("phone");
            $table->enum("gender", ["male", "female"]);
            
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');
            $table->rememberToken();
            
            $table->string("country")->nullable();
            $table->string("address")->nullable();

            $table->string("blood_type")->nullable();
            $table->string("weight")->nullable();
            $table->string("height")->nullable();
            $table->string("allergies")->nullable();
            $table->string("medications")->nullable();

            
            $table->date('date_of_birth');

            $table->string('provider_id')->nullable();
            $table->string('provider')->nullable();

            $table->unique(['provider_id', 'provider']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
