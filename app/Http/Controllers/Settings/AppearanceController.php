<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppearanceController extends Controller {
    /**
     * Persist the authenticated user's appearance preference.
     *
     * The client works with 'system'; it maps to the DB's 'auto' value so the
     * stored preference stays the single source of truth across sessions.
     */
    public function update(Request $request): RedirectResponse {
        $validated = $request->validate([
            'appearance' => ['required', 'in:light,dark,system'],
        ]);

        $darkMode = $validated['appearance'] === 'system' ? 'auto' : $validated['appearance'];

        $user = $request->user();
        assert($user instanceof User);
        $user->update(['dark_mode' => $darkMode]);

        return back();
    }
}
