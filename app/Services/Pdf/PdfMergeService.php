<?php

namespace App\Services\Pdf;

use App\Exceptions\ToolProcessingException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class PdfMergeService
{
    /**
     * Merge PDFs, in the given order, into a single output file.
     * Pure PHP (FPDI + TCPDF) — no external binary required.
     *
     * @param  string[]  $inputPaths  Ordered list of source PDF file paths.
     */
    public function merge(array $inputPaths, string $outputPath): string
    {
        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($inputPaths as $inputPath) {
            $this->appendAllPages($pdf, $inputPath);
        }

        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }

    protected function appendAllPages(Fpdi $pdf, string $inputPath): void
    {
        try {
            $pageCount = $pdf->setSourceFile($inputPath);
        } catch (Throwable $e) {
            throw new ToolProcessingException(
                'Could not read "'.basename($inputPath).'": '.$e->getMessage(),
                previous: $e
            );
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
    }
}
