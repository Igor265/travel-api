<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requester_name');
            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('status')->default('requested');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
