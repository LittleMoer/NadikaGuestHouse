<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SlotDemoController extends Controller
{
    public function index()
    {
        return response('Slots demo is not configured in this build.', 200);
    }
}
