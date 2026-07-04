<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessageAttachment extends Model
{
    protected $fillable = [
        'conversation_message_id',
        'path',
        'name',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(ConversationMessage::class, 'conversation_message_id');
    }
}
