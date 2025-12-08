<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use Illuminate\Http\Request;

class ImageGeneratorController extends Controller
{
    protected OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    /**
     * Genera una imagen a partir de un tema y una descripción
     */
    public function generate(Request $request)
    {
        // Validación de entrada
        $validated = $request->validate([
            'tema' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000'
        ]);

        $tema = $validated['tema'];
        $descripcion = $validated['descripcion'];

        // Llamar a OpenAI
        $result = $this->openai->generateImage($tema, $descripcion);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'success' => true,
            'tema' => $tema,
            'descripcion' => $descripcion,
            'image_base64' => $result['image_base64']
        ]);
    }
}
