<?php

namespace App\Services\Pdf;

use App\Exceptions\ToolProcessingException;
use App\Services\Binaries\BinaryLocator;
use Illuminate\Support\Facades\Process;

class PdfUnlockService
{
    /**
     * Remove password protection from a PDF. Requires qpdf — there is no
     * reliable pure-PHP way to open an encrypted PDF (FPDI's free tier
     * cannot import encrypted sources).
     */
    public function unlock(string $inputPath, string $outputPath, string $password): string
    {
        $qpdf = BinaryLocator::resolve('qpdf');

        $result = Process::timeout(config('tools.process_timeout'))->run([
            $qpdf,
            '--password='.$password,
            '--decrypt',
            $inputPath,
            $outputPath,
        ]);

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new ToolProcessingException(
                'Could not unlock the PDF. Check that the password is correct. '.$result->errorOutput()
            );
        }

        return $outputPath;
    }
}
