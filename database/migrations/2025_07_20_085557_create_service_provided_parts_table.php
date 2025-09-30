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
        Schema::create('service_provided_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->enum('product_type',['product','material'])->default('product');
            $table->bigInteger('product_id');
            $table->integer('qty');
            $table->decimal('price',12,2);

            $table->enum('current_position_type',['branch','warehouse','employee'])->nullable();
            $table->bigInteger('current_position_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provided_parts');
    }
};
