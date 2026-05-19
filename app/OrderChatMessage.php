<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderChatMessage extends Model
{
    protected $fillable = [
        'order_chat_id',
        'sender_user_id',
        'sender_role',
        'message',
    ];

    public function chat()
    {
        return $this->belongsTo(OrderChat::class, 'order_chat_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
