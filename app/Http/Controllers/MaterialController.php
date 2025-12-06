<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class MaterialController extends Controller
{
    /**
     * Extrae texto de archivos PDF, DOCX o TXT
     */
    public function extractText(Request $request)
    {
        try {
            // Validar que se envió un archivo
            $request->validate([
                'file' => 'required|file|mimes:pdf,docx,txt|max:10240' // max 10MB
            ]);

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Extraer texto según el tipo de archivo
            $text = match($extension) {
                'pdf' => $this->extractFromPDF($file),
                'docx' => $this->extractFromDOCX($file),
                'txt' => $this->extractFromTXT($file),
                default => throw new \Exception('Formato no soportado')
            };

            return response()->json([
                'success' => true,
                'text' => $text,
                'filename' => $file->getClientOriginalName()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Archivo inválido. Solo se permiten PDF, DOCX y TXT (máx 10MB)'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al extraer texto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrae texto de un archivo PDF
     */
    private function extractFromPDF($file): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getRealPath());
        $text = $pdf->getText();

        // Limpiar espacios múltiples y saltos de línea excesivos
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Extrae texto de un archivo DOCX
     */
    private function extractFromDOCX($file): string
    {
        $phpWord = IOFactory::load($file->getRealPath());
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                // Extraer texto de diferentes tipos de elementos
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    // Para elementos contenedores como TextRun
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText();
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        // Limpiar saltos de línea excesivos
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Extrae texto de un archivo TXT
     */
    private function extractFromTXT($file): string
    {
        $content = file_get_contents($file->getRealPath());

        // Intentar detectar encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return trim($content);
    }
}
