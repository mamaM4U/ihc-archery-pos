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
        Schema::create('session_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_membership_id')->constrained('customer_memberships');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('checked_in_by')->nullable()->constrained('users');
            $table->timestamp('checked_in_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_usages');
    }
};
