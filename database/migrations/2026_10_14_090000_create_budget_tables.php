<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('type', 10); // income or expense
            $table->string('color', 7);
            $table->string('icon', 30);
            $table->unsignedInteger('budget')->nullable(); // monthly limit in cents
            $table->timestamps();

            $table->unique(['user_id', 'name', 'type']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount'); // cents, always positive
            $table->string('description', 120)->nullable();
            $table->date('occurred_on');
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('target'); // cents
            $table->unsignedInteger('saved')->default(0); // cents
            $table->date('deadline')->nullable();
            $table->string('color', 7);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
    }
};
