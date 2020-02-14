<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\Scavenger\Helper\ConfigProvider;

class CreateScavengerScrapsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $table = ConfigProvider::getScrapsTable();
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
        $table = ConfigProvider::getScrapsTable();
        Schema::dropIfExists($table);
    }
}
