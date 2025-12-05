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

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
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
        ]);

        // Mapa front → backend
        $typeMap = [
            'mixto' => 'mixed',
            'multiple_choice' => 'multiple_choice',
            'opcion_multiple' => 'multiple_choice',
            'true_false' => 'true_false',
            'vf' => 'true_false',
            'abiertas' => 'open',
            'open' => 'open',
        ];

        $mappedType = $typeMap[strtolower($validated['tipoPreguntas'])] ?? 'mixed';

        $questions = $this->gemini->generateQuestions(
            $validated['curso'],
            $validated['tema'],
            $validated['numeroPreguntas'],
            $mappedType
        );

        return response()->json([
            'curso' => $validated['curso'],
            'tema' => $validated['tema'],
            'cantidad' => $validated['numeroPreguntas'],
            'tipoPreguntas' => $validated['tipoPreguntas'],
            'questions' => $questions,
        ]);
    }
}
