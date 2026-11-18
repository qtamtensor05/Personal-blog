<?php

declare(strict_types=1);

namespace App\Presentation\Web\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImageWritingExecutionBudget
{
    public function handle(Request $request, Closure $next): Response
    {
        // Image retrieval (15s) + Gemini (45s) must finish before PHP terminates the request.
        $previous = (int) ini_get('max_execution_time');
        $changed = function_exists('set_time_limit') && set_time_limit(90);

        try {
            return $next($request);
        } finally {
            if ($changed) {
                set_time_limit($previous);
            }
        }
    }
}
