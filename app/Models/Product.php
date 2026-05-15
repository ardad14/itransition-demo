<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $intProductDataId
 * @property string $strProductName
 * @property string $strProductDesc
 * @property string $strProductCode
 * @property int|null $intStock
 * @property float|null $decPrice
 * @property \Carbon\Carbon|null $dtmAdded
 * @property \Carbon\Carbon|null $dtmDiscontinued
 */
class Product extends Model
{
    protected $table = 'tblProductData';
    protected $primaryKey = 'intProductDataId';

    // stmTimestamp is managed by MySQL itself (ON UPDATE CURRENT_TIMESTAMP)
    public $timestamps = false;

    protected $fillable = [
        'strProductName',
        'strProductDesc',
        'strProductCode',
        'intStock',
        'decPrice',
        'dtmAdded',
        'dtmDiscontinued',
    ];

    protected $casts = [
        'dtmAdded' => 'datetime',
        'dtmDiscontinued' => 'datetime',
        'decPrice' => 'decimal:2',
        'intStock' => 'integer',
    ];
}
