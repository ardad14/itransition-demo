<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds stock level and price columns to tblProductData.
 * These fields come from the supplier CSV and were not in the original table.
 *
 * Types chosen:
 *   intStock  — UNSIGNED INT: stock cannot be negative
 *   decPrice  — DECIMAL(10,2): avoids floating-point rounding when comparing to $5 / $1000 thresholds
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblProductData', function (Blueprint $table) {
            $table->unsignedInteger('intStock')
                ->nullable()
                ->after('strProductCode')
                ->comment('Stock level from supplier');

            $table->decimal('decPrice', 10, 2)
                ->nullable()
                ->after('intStock')
                ->comment('Unit price in GBP from supplier');
        });
    }

    public function down(): void
    {
        Schema::table('tblProductData', function (Blueprint $table) {
            $table->dropColumn(['intStock', 'decPrice']);
        });
    }
};
