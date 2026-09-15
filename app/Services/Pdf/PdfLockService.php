<?php

namespace App\Services\Pdf;

use App\Exceptions\ToolBinaryMissingException;
use App\Exceptions\ToolProcessingException;
use App\Services\Binaries\BinaryLocator;
use Illuminate\Support\Facades\Process;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class PdfLockService
{
    public function lock(string $inputPath, string $outputPath, string $password, ?string $ownerPassword = null): string
    {
        $ownerPassword ??= $password;

        try {
            return $this->lockWithQpdf($inputPath, $outputPath, $password, $ownerPassword);
        } catch (ToolBinaryMissingException) {
            return $this->lockWithTcpdf($inputPath, $outputPath, $password, $ownerPassword);
        }
    }

    protected function lockWithQpdf(string $inputPath, string $outputPath, string $userPassword, string $ownerPassword): string
    {
        $qpdf = BinaryLocator::resolve('qpdf');

        $result = Process::timeout(config('tools.process_timeout'))->run([
            $qpdf,
            '--encrypt', $userPassword, $ownerPassword, '256',
            '--',
            $inputPath,
            $outputPath,
        ]);

        if (! $result->successful() || ! is_file($outputPath)) {
            throw new ToolProcessingException('qpdf failed to encrypt the PDF: '.$result->errorOutput());
        }

        return $outputPath;
    }

    /**
     * Fallback for unprotected source PDFs when qpdf isn't installed. Re-renders
     * every page as a template, so fidelity for complex PDFs (forms, annotations)
     * may be reduced compared to qpdf, which encrypts in place.
     */
    protected function lockWithTcpdf(string $inputPath, string $outputPath, string $userPassword, string $ownerPassword): string
    {
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        try {
            $pageCount = $pdf->setSourceFile($inputPath);
        } catch (Throwable $e) {
            throw new ToolProcessingException('Could not read the uploaded PDF: '.$e->getMessage(), previous: $e);
        }

        $pdf->SetProtection(['print', 'copy', 'modify'], $userPassword, $ownerPassword, 3);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }
}
