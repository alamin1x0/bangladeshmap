<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingOrder extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'paid' => 'Paid',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = ['name', 'phone', 'district', 'address', 'amount', 'status', 'admin_note', 'ip_address'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }
}
