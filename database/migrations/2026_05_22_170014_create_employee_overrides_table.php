<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('employee_overrides', function (Blueprint $table) {
      $table->id();
      $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
      $table->date('start_date');
      $table->timestamps();
    });
  }

  public function down() {
    Schema::dropIfExists('employee_overrides');
  }
};