<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use Illuminate\Http\Request;

class CourseTopicGeneratorController extends Controller
{
    protected OpenAIService $openai;

    public function __construct(OpenAIService $openai)
    {
        $this->openai = $openai;
    }

    /**
     * Genera temas para un curso usando OpenAI
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:3000',
            'nivelEducativo' => 'required|string|max:255',
        ]);

        $result = $this->openai->generateCourseTopics(
            $validated['titulo'],
            $validated['descripcion'],
            $validated['nivelEducativo']
        );

        // Si el servicio indica error en el JSON
        if (isset($result['error']) && $result['error'] === true) {
            return response()->json([
                'success' => false,
                'error' => $result['message'] ?? 'Error al generar temas',
                'raw' => $result['raw'] ?? null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'titulo' => $validated['titulo'],
            'descripcion' => $validated['descripcion'],
            'nivelEducativo' => $validated['nivelEducativo'],
            'data' => $result, // aquí viene course_title, education_level, topics[...]
        ]);
    }
}
