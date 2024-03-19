<?php

namespace App\Http\Controllers;

use App\Enums\Levels;

abstract class Controller
{

    protected function flashMessage(string $message, Levels $level = Levels::SUCCESS): void
    {
        session()->flash('flash_message_data', [
            'message' => $message,
            'type' => $level->value,
        ]);
    }
}
