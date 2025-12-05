<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTopic extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
