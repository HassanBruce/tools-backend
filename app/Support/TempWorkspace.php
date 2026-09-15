<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * A scratch directory for one request's file processing. Always cleaned up
 * via app()->terminating() so it disappears once the response has been sent,
 * even for downloads that stream the file back to the client.
 */
class TempWorkspace
{
    public readonly string $path;

    public function __construct()
    {
        $this->path = storage_path('app/tmp/'.(string) Str::uuid());

        File::ensureDirectoryExists($this->path, 0755, true);

        app()->terminating(fn () => $this->cleanup());
    }

    public function path(string $name = ''): string
    {
        return $name === '' ? $this->path : $this->path.DIRECTORY_SEPARATOR.ltrim($name, '\\/');
    }

    public function cleanup(): void
    {
        if (File::isDirectory($this->path)) {
            File::deleteDirectory($this->path);
        }
    }
}
