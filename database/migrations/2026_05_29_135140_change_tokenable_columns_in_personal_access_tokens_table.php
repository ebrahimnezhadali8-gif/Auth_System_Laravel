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
        Schema::table('personal_access_tokens', function (Blueprint $table) {

            // delete old morphs columns  
            $table->dropMorphs('tokenable');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {

            // create UUID
            $table->uuidMorphs('tokenable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {

                $table->dropMorphs('tokenable');
            });

            Schema::table('personal_access_tokens', function (Blueprint $table) {

                $table->morphs('tokenable');
            });
        });
    }
};
