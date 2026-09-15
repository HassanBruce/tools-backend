<?php

namespace App\Services\Binaries;

use App\Exceptions\ToolBinaryMissingException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class BinaryLocator
{
    /**
     * Resolve a configured binary key (soffice|qpdf|ghostscript) to a usable path,
     * verifying it can actually be executed. The executability check is cached
     * briefly since spawning soffice just to probe it can take several seconds.
     *
     * @throws ToolBinaryMissingException
     */
    public static function resolve(string $key): string
    {
        $path = config("tools.binaries.{$key}");

        if (blank($path)) {
            throw ToolBinaryMissingException::forBinary($key, '(not configured)');
        }

        $works = Cache::remember(
            "tools:binary-check:{$key}:".md5($path),
            now()->addSeconds(60),
            fn () => static::isExecutable($path)
        );

        if (! $works) {
            throw ToolBinaryMissingException::forBinary($key, $path);
        }

        return $path;
    }

    protected static function isExecutable(string $path): bool
    {
        try {
            return Process::timeout(20)->run([$path, '--version'])->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
