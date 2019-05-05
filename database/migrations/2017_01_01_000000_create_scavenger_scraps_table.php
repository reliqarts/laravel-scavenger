<?php

/*
 * @author    Reliq <reliq@reliqarts.com>
 * @copyright 2018
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\Scavenger\Helpers\Config;

class CreateScavengerScrapsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $table = Config::getScrapsTable();
        // @noinspection PhpUndefinedMethodInspection
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('hash', 128)->unique(); // a hash
                $table->string('title')->nullable();
                $table->string('model', 256);
                $table->string('related', 256)->nullable();
                $table->text('data');
                $table->string('source', 400);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $table = Config::getScrapsTable();
        Schema::dropIfExists($table);
    }
}
