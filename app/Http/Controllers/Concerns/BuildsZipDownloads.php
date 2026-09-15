<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ToolProcessingException;
use App\Support\TempWorkspace;
use ZipArchive;

trait BuildsZipDownloads
{
    /**
     * @param  string[]  $filePaths
     */
    protected function zipFiles(TempWorkspace $workspace, array $filePaths, string $zipName): string
    {
        $zipPath = $workspace->path($zipName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new ToolProcessingException('Could not create the zip archive.');
        }

        foreach ($filePaths as $filePath) {
            $zip->addFile($filePath, basename($filePath));
        }

        $zip->close();

        return $zipPath;
    }
}
