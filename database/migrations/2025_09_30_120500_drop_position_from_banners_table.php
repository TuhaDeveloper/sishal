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
        if (Schema::hasColumn('banners', 'position')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('banners', 'position')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->enum('position', ['top', 'middle', 'bottom', 'sidebar'])->default('top')->after('image');
            });
        }
    }
};


