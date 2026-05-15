<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the base tblProductData table.
 * This replicates the existing supplier table structure before our additions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblProductData', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->unsignedInteger('intProductDataId')->autoIncrement();
            $table->string('strProductName', 50)->comment('Product name');
            $table->string('strProductDesc', 255)->comment('Product description');
            $table->string('strProductCode', 10)->unique()->comment('Unique product code');
            $table->dateTime('dtmAdded')->nullable()->comment('Date product was added');
            $table->dateTime('dtmDiscontinued')->nullable()->comment('Date product was discontinued');
            $table->timestamp('stmTimestamp')->useCurrent()->useCurrentOnUpdate()->comment('Last updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblProductData');
    }
};
