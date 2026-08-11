<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    protected $fillable = ['user_id', 'title'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stories()
    {
        return $this->belongsToMany(Story::class, 'highlight_story')
            ->withPivot('order')
            ->orderBy('highlight_story.order');
    }
}