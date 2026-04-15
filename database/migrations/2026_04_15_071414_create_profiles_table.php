<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('headline')->nullable(); // Short hook
            $table->text('about')->nullable();      // The long summary/bio
            $table->string('location')->nullable();
            $table->text('raw_text'); // Stores the exact copy-paste
            $table->jsonb('parsed_json')->nullable(); // PostgreSQL JSONB for the LLM output
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
