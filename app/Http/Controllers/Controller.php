<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Answer 404 when a record belongs to someone else,
     * so the API does not reveal which ids exist.
     */
    protected function ensureOwner(Request $request, Model $model): void
    {
        abort_if($model->getAttribute('user_id') !== $request->user()->id, 404);
    }
}
