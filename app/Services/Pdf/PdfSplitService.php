<?php

namespace App\Services\Pdf;

use App\Exceptions\ToolProcessingException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class PdfSplitService
{
    /**
     * Split a PDF into one file per page.
     *
     * @return string[] Output file paths, one per page, in page order.
     */
    public function splitAllPages(string $inputPath, string $outputDir): array
    {
        return $this->splitRanges($inputPath, $outputDir, null);
    }

    /**
     * @param  string|null  $rangesSpec  e.g. "1-3,5,8-9". Null splits every page individually.
     * @return string[]
     */
    public function splitRanges(string $inputPath, string $outputDir, ?string $rangesSpec): array
    {
        $probe = new Fpdi();

        try {
            $pageCount = $probe->setSourceFile($inputPath);
        } catch (Throwable $e) {
            throw new ToolProcessingException('Could not read the uploaded PDF: '.$e->getMessage(), previous: $e);
        }

        $ranges = $rangesSpec === null
            ? array_map(fn (int $p) => [$p, $p], range(1, $pageCount))
            : $this->parseRanges($rangesSpec, $pageCount);

        $outputs = [];

        foreach ($ranges as [$from, $to]) {
            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->setSourceFile($inputPath);

            for ($pageNo = $from; $pageNo <= $to; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }

            $name = $from === $to ? "page-{$from}.pdf" : "pages-{$from}-{$to}.pdf";
            $path = $outputDir.DIRECTORY_SEPARATOR.$name;
            $pdf->Output($path, 'F');
            $outputs[] = $path;
        }

        return $outputs;
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    protected function parseRanges(string $spec, int $pageCount): array
    {
        $ranges = [];

        foreach (explode(',', $spec) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$from, $to] = array_map('trim', explode('-', $part, 2));
                $from = (int) $from;
                $to = (int) $to;
            } else {
                $from = $to = (int) $part;
            }

            if ($from < 1 || $to < $from || $to > $pageCount) {
                throw new ToolProcessingException("Invalid page range \"{$part}\" for a {$pageCount}-page document.");
            }

            $ranges[] = [$from, $to];
        }

        if ($ranges === []) {
            throw new ToolProcessingException('No valid page ranges were provided.');
        }

        return $ranges;
    }
}
