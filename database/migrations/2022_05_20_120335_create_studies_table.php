<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable();
            $table->integer('matematika');
            $table->integer('fisika');
            $table->integer('kimia');
            $table->integer('biologi');
            $table->integer('sejarah');
            $table->integer('akuntansi');
            $table->integer('sosiologi');
            $table->integer('geografi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('studies');
    }
}
