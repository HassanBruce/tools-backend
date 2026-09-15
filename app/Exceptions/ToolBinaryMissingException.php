<?php

namespace App\Exceptions;

use RuntimeException;

class ToolBinaryMissingException extends RuntimeException
{
    public static function forBinary(string $name, string $configuredPath): self
    {
        $hints = [
            'soffice' => 'Install LibreOffice (https://www.libreoffice.org/download/) and set SOFFICE_BINARY in .env to the full path of soffice.exe.',
            'qpdf' => 'Install qpdf (https://qpdf.sourceforge.io, or `choco install qpdf` on Windows) and set QPDF_BINARY in .env.',
            'ghostscript' => 'Install Ghostscript (https://ghostscript.com/releases, or `choco install ghostscript` on Windows) and set GHOSTSCRIPT_BINARY in .env.',
        ];

        $hint = $hints[$name] ?? "Install the \"{$name}\" binary and configure its path.";

        return new self("Required tool \"{$name}\" was not found (tried: {$configuredPath}). {$hint}");
    }
}
