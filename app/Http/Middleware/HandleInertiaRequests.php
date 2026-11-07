<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'appName' => config('app.name'),
            'flash_message_data' => session('flash_message_data'),
            'localeAlternates' => $this->localeAlternates($request),
        ]);
    }

    /**
     * Maps the current page to its equivalent URL in each supported locale,
     * used for the language switcher and the <link rel="alternate" hreflang>
     * tags.
     *
     * @return array<string, string>
     */
    private function localeAlternates(Request $request): array
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return [];
        }

        $canonicalName = str_starts_with($routeName, 'en.') ? substr($routeName, 3) : $routeName;
        $parameters = $request->route()->parameters();

        $alternates = [];

        foreach (['es' => $canonicalName, 'en' => "en.{$canonicalName}"] as $locale => $name) {
            if (Route::has($name)) {
                $alternates[$locale] = route($name, $parameters);
            }
        }

        return $alternates;
    }
}
