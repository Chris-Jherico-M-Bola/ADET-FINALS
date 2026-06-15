<?php

namespace App\Services;

class PDFSlideCountService
{
    public function count(string $pdfPath): int
    {
        if (! is_file($pdfPath)) {
            return 0;
        }

        $contents = file_get_contents($pdfPath);

        if ($contents === false || $contents === '') {
            return 0;
        }

        if (preg_match_all('/\/Type\s*\/Page\b/', $contents, $matches)) {
            return max(1, count($matches[0]));
        }

        return 1;
    }
}
