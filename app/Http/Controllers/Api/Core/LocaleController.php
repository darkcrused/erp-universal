<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class LocaleController extends Controller
{
    /**
     * List available locales with labels.
     */
    public function available(): JsonResponse
    {
        $locales = Config::get('app.available_locales', ['pt_BR', 'en', 'es']);

        $localeLabels = [
            'pt_BR' => 'Português (Brasil)',
            'en' => 'English',
            'es' => 'Español',
        ];

        $result = array_map(fn (string $code): array => [
            'code' => $code,
            'name' => $localeLabels[$code] ?? $code,
        ], (array) $locales);

        return response()->json([
            'current' => App::getLocale(),
            'available' => array_values($result),
        ]);
    }

    /**
     * Switch the user's locale.
     */
    public function switch(Request $request): JsonResponse
    {
        $validLocales = (array) Config::get('app.available_locales', ['pt_BR', 'en', 'es']);
        $validator = validator($request->all(), [
            'locale' => 'required|string|in:' . implode(',', $validLocales),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'locale' => [__('validation.in', ['attribute' => 'locale'])],
            ]);
        }

        $locale = $request->locale;

        // Persist locale for authenticated user
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        App::setLocale($locale);

        return response()->json([
            'message' => __('core.locale_changed'),
            'locale' => $locale,
        ]);
    }
}
