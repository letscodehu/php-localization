<?php

namespace Middleware;

use Locale;
use Request\Request;
use Response\Response;

class LocalizationMiddleware implements Middleware {

    private string $defaultLocale;
    private array $availableLocales;

    public function __construct(string $defaultLocale, array $availableLocales)
    {
        $this->availableLocales = $availableLocales;
        $this->defaultLocale = $defaultLocale;
    }

    public function process(Request $request, Response $response, callable $next) {
        $localeFromHeader = Locale::acceptFromHttp($request->getHeaders()["Accept-Language"]);
        $locale = in_array($localeFromHeader, $this->availableLocales) ? $localeFromHeader : $this->defaultLocale;
        return $next($request->withLocale($locale), $response);
    }

}

