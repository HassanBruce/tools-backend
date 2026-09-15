<?php

namespace App\Services\Conversion;

use App\Exceptions\ToolProcessingException;
use App\Services\Binaries\BinaryLocator;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class OfficeConversionService
{
    /**
     * Convert a document with LibreOffice headless.
     *
     * @param  string  $inputPath  Source file (e.g. .docx or .pdf).
     * @param  string  $targetFormat  soffice filter/extension, e.g. "pdf" or "docx".
     * @param  string  $outputDir  Directory soffice should write the converted file into.
     * @return string Path to the converted file.
     */
    public function convert(string $inputPath, string $targetFormat, string $outputDir): string
    {
        $soffice = BinaryLocator::resolve('soffice');

        // Each conversion gets its own profile dir so concurrent requests don't
        // collide on LibreOffice's user-profile lock.
        $profileDir = $outputDir.DIRECTORY_SEPARATOR.'profile-'.Str::random(8);
        $profileUri = 'file:///'.str_replace('\\', '/', $profileDir);

        $result = Process::timeout(config('tools.process_timeout'))->run([
            $soffice,
            '--headless',
            '--norestore',
            '-env:UserInstallation='.$profileUri,
            '--convert-to', $targetFormat,
            '--outdir', $outputDir,
            $inputPath,
        ]);

        if (! $result->successful()) {
            throw new ToolProcessingException(
                'LibreOffice conversion failed: '.$result->errorOutput().$result->output()
            );
        }

        $expected = $outputDir.DIRECTORY_SEPARATOR
            .pathinfo($inputPath, PATHINFO_FILENAME).'.'.$targetFormat;

        if (! is_file($expected)) {
            throw new ToolProcessingException(
                'LibreOffice did not produce an output file. Output: '.$result->output()
            );
        }

        return $expected;
    }
}
