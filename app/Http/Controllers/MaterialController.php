<?php

namespace App\Http\Controllers;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class MaterialController extends Controller
{

    protected SupabaseStorageService $supabase;

    public function __construct(SupabaseStorageService $supabase)
    {
        $this->supabase = $supabase;
    }
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
            $text = match ($extension) {
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
     * NUEVO: Sube un archivo .txt al bucket de Supabase.
     *
     * Body esperado:
     *  - file: archivo .txt (multipart/form-data)
     *  - path (opcional): ruta lógica dentro del bucket, ej: "cursos/cta/tema1"
     */
    public function uploadTxtToSupabase(Request $request)
    {
        try {
            // Solo se permiten .txt, como indicaste
            $request->validate([
                'file' => 'required|file|mimes:txt|max:10240', // máx 10MB
                'path' => 'nullable|string',
            ]);

            $file = $request->file('file');
            $path = $request->input('path', ''); // ej: "cursos/cta/tema1"

            // Subir al bucket usando el servicio
            $objectPath = $this->supabase->uploadTxt($file, $path);
            $publicUrl = $this->supabase->getPublicUrl($objectPath);

            return response()->json([
                'success' => true,
                'message' => 'Archivo .txt subido correctamente a Supabase',
                'object_path' => $objectPath,
                'public_url' => $publicUrl,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Archivo inválido. Solo se permiten archivos .txt (máx 10MB)',
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al subir a Supabase: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error inesperado: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function listTxtFiles(Request $request)
    {
        try {
            // Carpeta opcional enviada desde el front (ej.: cursos/cta/tema1)
            $path = $request->input('path', '');

            $files = $this->supabase->listFiles($path);

            // Filtrar solo archivos .txt
            $txtFiles = array_filter($files, function ($file) {
                return isset($file['name']) && str_ends_with(strtolower($file['name']), '.txt');
            });

            // Retornar solo nombres
            $names = array_map(function ($file) {
                return $file['name'];
            }, $txtFiles);

            return response()->json([
                'success' => true,
                'files' => $names
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listTxtFilesWithContent(Request $request)
    {
        try {
            $path = $request->input('path', '');

            $files = $this->supabase->listFiles($path);

            $txtFiles = array_filter($files, function ($file) {
                return isset($file['name']) && str_ends_with(strtolower($file['name']), '.txt');
            });

            $result = [];

            foreach ($txtFiles as $file) {
                $objectPath = $path ? "$path/{$file['name']}" : $file['name'];
                $content = $this->supabase->downloadFile($objectPath);

                $result[] = [
                    'name' => $file['name'],
                    'content' => $content
                ];
            }

            return response()->json([
                'success' => true,
                'files' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un archivo .txt del bucket de Supabase
     */
    public function deleteMaterial(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string'
            ]);

            $fileName = $validated['nombre'];

            // Intentar eliminar usando el servicio
            $this->supabase->deleteFile($fileName);

            return response()->json([
                'success' => true,
                'message' => "Archivo '{$fileName}' eliminado correctamente."
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debe enviar un nombre válido de archivo.'
            ], 422);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => "Error inesperado: " . $e->getMessage()
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
