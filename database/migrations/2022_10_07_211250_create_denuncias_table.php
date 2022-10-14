<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDenunciasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('denuncias', function (Blueprint $table) {
            
            $table->id();
            $table->string('establecimiento');
            $table->integer('provincia');
            $table->integer('distrito');
            $table->integer('corregimiento');
            $table->string('referencia')->nullable();
            $table->integer('categoria');
            $table->integer('subcategoria');
            $table->string('descripcion')->nullable();
            $table->string('lat');
            $table->string('denuncia_rel')->nullable();
            $table->string('lon');
            $table->string('user');            
            $table->string('img1')->nullable();
            $table->string('img2')->nullable();
            $table->string('img3')->nullable();
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
        Schema::dropIfExists('denuncias');
    }
}
