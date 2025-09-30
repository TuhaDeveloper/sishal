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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('product_service_id')->nullable();
            $table->enum('service_type', ['installation', 'maintenance', 'repair', 'filter_change', 'other']);
            $table->string('phone')->nullable();
            $table->dateTime('requested_date');
            $table->string('preferred_time')->nullable(); // e.g., Morning, Afternoon, Evening
            $table->text('address');
            $table->text('city')->nullable();
            $table->text('state')->nullable();
            $table->text('zip_code')->nullable();
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete(); // assuming technician is also a user
            $table->text('service_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->decimal('service_fee',12,2);
            $table->decimal('travel_fee',12,2);
            $table->decimal('discount',12,2);
            $table->decimal('total',12,2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
