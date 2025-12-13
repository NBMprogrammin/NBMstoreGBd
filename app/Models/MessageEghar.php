<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class MessageEghar extends Model
{
    //

    protected $table = 'message_eghars';
    protected $fillable = ['user_id', 'image', 'sheckMessage', 'idbss', 'titel', 'TypeMessage', 'message', 'NameUserSendMessage', 'TypeAccountSendMessage', 'TypeRelationMessageUser', 'TypeRelationMessageUserSend', 'CloceMessage'];

    public function user() {
        return $this->belongsTo(User::class);
    }

}
