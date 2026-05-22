<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('shift_schedules', function (Blueprint $table) {
      $table->id();
      $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
      $table->date('date');
      $table->string('shift')->default('Day');
        $table->timestamps();
      });
    }

    public function down() {
      Schema::dropIfExists('shift_schedules');
    }
  };