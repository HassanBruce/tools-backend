<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversion\PdfToWordRequest;
use App\Http\Requests\Conversion\WordToPdfRequest;
use App\Services\Conversion\OfficeConversionService;
use App\Support\TempWorkspace;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConversionController extends Controller
{
    public function wordToPdf(WordToPdfRequest $request, OfficeConversionService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension() ?: 'docx';
        $inputName = 'input.'.$extension;

        $file->move($workspace->path(), $inputName);

        $outputPath = $service->convert($workspace->path($inputName), 'pdf', $workspace->path());

        $downloadName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.pdf';

        return response()->download($outputPath, $downloadName);
    }

    public function pdfToWord(PdfToWordRequest $request, OfficeConversionService $service): BinaryFileResponse
    {
        $workspace = new TempWorkspace;

        $file = $request->file('file');
        $file->move($workspace->path(), 'input.pdf');

        $outputPath = $service->convert($workspace->path('input.pdf'), 'docx', $workspace->path());

        $downloadName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.docx';

        return response()->download($outputPath, $downloadName);
    }
}
