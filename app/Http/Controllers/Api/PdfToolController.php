<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsZipDownloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pdf\CompressPdfRequest;
use App\Http\Requests\Pdf\LockPdfRequest;
use App\Http\Requests\Pdf\MergePdfRequest;
use App\Http\Requests\Pdf\SplitPdfRequest;
use App\Http\Requests\Pdf\UnlockPdfRequest;
use App\Services\Pdf\PdfCompressService;
use App\Services\Pdf\PdfLockService;
use App\Services\Pdf\PdfMergeService;
use App\Services\Pdf\PdfSplitService;
use App\Services\Pdf\PdfUnlockService;
use App\Support\TempWorkspace;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfToolController extends Controller
{
    use BuildsZipDownloads;

    public function merge(MergePdfRequest $request, PdfMergeService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $inputPaths = [];

        foreach ($request->file('files') as $index => $file) {
            $file->move($workspace->path(), "input-{$index}.pdf");
            $inputPaths[] = $workspace->path("input-{$index}.pdf");
        }

        $outputPath = $service->merge($inputPaths, $workspace->path('merged.pdf'));

        return response()->download($outputPath, 'merged.pdf');
    }

    public function split(SplitPdfRequest $request, PdfSplitService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $request->file('file')->move($workspace->path(), 'input.pdf');
        $inputPath = $workspace->path('input.pdf');

        $outputs = $request->input('mode') === 'ranges'
            ? $service->splitRanges($inputPath, $workspace->path(), $request->input('ranges'))
            : $service->splitAllPages($inputPath, $workspace->path());

        if (count($outputs) === 1) {
            return response()->download($outputs[0], basename($outputs[0]));
        }

        $zipPath = $this->zipFiles($workspace, $outputs, 'split.zip');

        return response()->download($zipPath, 'split.zip');
    }

    public function compress(CompressPdfRequest $request, PdfCompressService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $request->file('file')->move($workspace->path(), 'input.pdf');

        $outputPath = $service->compress(
            $workspace->path('input.pdf'),
            $workspace->path('compressed.pdf'),
            $request->input('quality', 'medium')
        );

        return response()->download($outputPath, 'compressed.pdf');
    }

    public function lock(LockPdfRequest $request, PdfLockService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $request->file('file')->move($workspace->path(), 'input.pdf');

        $outputPath = $service->lock(
            $workspace->path('input.pdf'),
            $workspace->path('locked.pdf'),
            $request->input('password'),
            $request->input('owner_password')
        );

        return response()->download($outputPath, 'locked.pdf');
    }

    public function unlock(UnlockPdfRequest $request, PdfUnlockService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $request->file('file')->move($workspace->path(), 'input.pdf');

        $outputPath = $service->unlock(
            $workspace->path('input.pdf'),
            $workspace->path('unlocked.pdf'),
            $request->input('password')
        );

        return response()->download($outputPath, 'unlocked.pdf');
    }
}
