<?php

namespace App\Http\Controllers;

use App\Enums\Levels;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function flashMessage(string $message, Levels $level = Levels::SUCCESS): void
    {
        session()->flash('flash_message_data', [
            'message' => $message,
            'type' => $level,
        ]);
    }
}
