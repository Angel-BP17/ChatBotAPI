<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseTopic;
use App\Services\GeminiService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class QuestionGeneratorController extends Controller
{
    protected GeminiService $gemini;
    protected OpenAIService $openai;

    public function __construct(GeminiService $gemini, OpenAIService $openai)
    {
        $this->gemini = $gemini;
        $this->openai = $openai;
    }

    // Lista cursos
    public function listCourses()
    {
        return response()->json(Course::all());
    }

    // Lista temas de un curso
    public function listTopics($courseId)
    {
        $course = Course::with('topics')->findOrFail($courseId);
        return response()->json($course->topics);
    }

    // Generar cuestionario
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'curso' => 'required|string',
            'tema' => 'required|string',
            'numeroPreguntas' => 'required|integer|min:1|max:15',
            'tipoPreguntas' => 'required|string',
            'contenidoMaterial' => 'required|string',
        ]);

        // Mapa front → backend
        $typeMap = [
            'mixto' => 'mixed',
            'mixta' => 'mixed',
            'multiple_choice' => 'multiple_choice',
            'opcion_multiple' => 'multiple_choice',
            'true_false' => 'true_false',
            'vf' => 'true_false',
            'abiertas' => 'open',
            'open' => 'open',
        ];

        $mappedType = $typeMap[strtolower($validated['tipoPreguntas'])] ?? 'mixed';

        $provider = 'gemini';
        $geminiResult = $this->gemini->generateQuestions(
            $validated['curso'],
            $validated['tema'],
            $validated['numeroPreguntas'],
            $mappedType,
            $validated['contenidoMaterial']
        );

        if ($this->isErrorResponse($geminiResult)) {
            $provider = 'openai';
            $openAiResult = $this->openai->generateQuestions(
                $validated['curso'],
                $validated['tema'],
                $validated['numeroPreguntas'],
                $mappedType,
                $validated['contenidoMaterial']
            );

            if ($this->isErrorResponse($openAiResult)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No se pudo generar preguntas con Gemini ni con OpenAI',
                    'gemini_error' => $geminiResult,
                    'openai_error' => $openAiResult,
                ], 500);
            }

            $questions = $openAiResult;
        } else {
            $questions = $geminiResult;
        }

        return response()->json([
            'curso' => $validated['curso'],
            'tema' => $validated['tema'],
            'cantidad' => $validated['numeroPreguntas'],
            'tipoPreguntas' => $validated['tipoPreguntas'],
            'questions' => $questions,
            'provider' => $provider,
        ]);
    }

    private function isErrorResponse($payload): bool
    {
        return is_array($payload)
            && array_key_exists('error', $payload)
            && $payload['error'] === true;
    }
}
