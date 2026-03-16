<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';
    protected $fillable = [
        'user_id','action','target_type','target_id','ip','user_agent','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
