<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('employees', function (Blueprint $table) {
      $table->id();
      $table->foreignId('telegram_user_id')->constrained('telegram_users')->cascadeOnDelete();
      $table->string('name');
      $table->string('nrp')->unique();
      $table->string('shift_pattern'); // contoh: "DDDDDDDDNNNNNO"
      $table->date('shift_start_date'); // acuan pola shift
      $table->string('shift_start')->default('Day'); // shift pada shift_start_date
        $table->unsignedInteger('work_days')->default(70);
        $table->unsignedInteger('leave_days')->default(14);
        $table->date('pattern_start_date'); // acuan siklus kerja-cuti pertama
        $table->timestamps();
      });
    }

    public function down() {
      Schema::dropIfExists('employees');
    }
  };