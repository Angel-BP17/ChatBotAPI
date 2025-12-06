<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class AnswerEvaluatorController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function evaluate(Request $request)
    {
        // ✅ Validación según tu formato
        $validated = $request->validate([
            'temaCurso' => 'required|string',
            'pregunta' => 'required|string',
            'respuestaEstudiante' => 'required|string',
        ]);

        $result = $this->gemini->evaluateAnswer(
            $validated['temaCurso'],
            $validated['pregunta'],
            $validated['respuestaEstudiante']
        );

        // Si hubo error en el servicio → 500
        if (isset($result['error']) && $result['error'] === true) {
            return response()->json($result, 500);
        }

        return response()->json([
            'temaCurso' => $validated['temaCurso'],
            'pregunta' => $validated['pregunta'],
            'respuestaEstudiante' => $validated['respuestaEstudiante'],
            'evaluation' => $result,
        ]);
    }
}
