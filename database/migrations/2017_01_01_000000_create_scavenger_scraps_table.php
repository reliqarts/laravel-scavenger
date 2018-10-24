<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ReliQArts\Scavenger\Helpers\SchemaHelper;

class CreateScavengerScrapsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $table = SchemaHelper::getScrapsTable();
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
        $table = SchemaHelper::getScrapsTable();
        Schema::dropIfExists($table);
    }
}
