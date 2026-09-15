<?php

namespace App\Services\Pdf;

use App\Exceptions\ToolBinaryMissingException;
use App\Exceptions\ToolProcessingException;
use App\Services\Binaries\BinaryLocator;
use Illuminate\Support\Facades\Process;

class PdfCompressService
{
    /**
     * @param  string  $quality  one of: low, medium, high (low = smallest / most lossy)
     */
    public function compress(string $inputPath, string $outputPath, string $quality = 'medium'): string
    {
        try {
            return $this->compressWithGhostscript($inputPath, $outputPath, $quality);
        } catch (ToolBinaryMissingException $ghostscriptMissing) {
            try {
                return $this->compressWithQpdf($inputPath, $outputPath);
            } catch (ToolBinaryMissingException) {
                throw $ghostscriptMissing;
            }
        }
    }

    protected function compressWithGhostscript(string $inputPath, string $outputPath, string $quality): string
    {
        $gs = BinaryLocator::resolve('ghostscript');

        $preset = match ($quality) {
            'low' => '/screen',
            'high' => '/prepress',
            default => '/ebook',
        };

        $result = Process::timeout(config('tools.process_timeout'))->run([
            $gs,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            "-dPDFSETTINGS={$preset}",
            '-dNOPAUSE',
            '-dBATCH',
            '-dQUIET',
            '-sOutputFile='.$outputPath,
            $inputPath,
        ]);

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new ToolProcessingException('Ghostscript failed to compress the PDF: '.$result->errorOutput());
        }

        return $outputPath;
    }

    protected function compressWithQpdf(string $inputPath, string $outputPath): string
    {
        $qpdf = BinaryLocator::resolve('qpdf');

        $result = Process::timeout(config('tools.process_timeout'))->run([
            $qpdf,
            '--stream-data=compress',
            '--recompress-flate',
            '--compression-level=9',
            '--object-streams=generate',
            $inputPath,
            $outputPath,
        ]);

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new ToolProcessingException('qpdf failed to compress the PDF: '.$result->errorOutput());
        }

        return $outputPath;
    }
}
