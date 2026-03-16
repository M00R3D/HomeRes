<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $table = 'notification_preferences';
    protected $fillable = ['user_id','channel_email','channel_inapp','receive_push','categories'];

    protected $casts = [
        'channel_email' => 'boolean',
        'channel_inapp' => 'boolean',
        'receive_push' => 'boolean',
        'categories' => 'array',
    ];
}
