<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Screenshot;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class ScreenshotsController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'screenshot' => [
                'required',
                File::types(['jpeg'])
                    ->max(10_000),
            ],
        ]);

        datadog_increment('osu.screenshots');

        $screenshot = Screenshot::create(['user_id' => \Auth::user()->getKey()]);
        $screenshot->store($validated['screenshot']);

        return response()->json([
            'url' => $screenshot->url(),
        ]);
    }

    public function show($screenshot, string $hash)
    {
        abort_if($screenshot->urlHash() !== $hash, 404);

        return $this->showBase($screenshot);
    }

    public function showLegacy($screenshot)
    {
        abort_if(!$screenshot->isLegacy(), 404);

        return $this->showBase($screenshot);
    }

    private function showBase($id)
    {
        $screenshot = Screenshot::findOrFail($id);
        $screenshot->hits++;
        $screenshot->last_access = Carbon::now();
        $screenshot->save();

        $file = $screenshot->get();

        abort_if(!$file, 404);

        return response()->stream(function () use ($file) {
            echo $file;
        }, 200, ['Content-Type' => 'image/jpeg']);
    }
}
