<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class AnswerEvaluatorController extends Controller
{
    protected GeminiService $gemini;
    protected OpenAIService $openai;

    public function __construct(GeminiService $gemini, OpenAIService $openai)
    {
        $this->gemini = $gemini;
        $this->openai = $openai;
    }

    public function evaluate(Request $request)
    {
        $validated = $request->validate([
            'temaCurso' => 'required|string',
            'pregunta' => 'required|string',
            'respuestaEstudiante' => 'required|string',
            'contenidoMaterial' => 'required|string',
        ]);

        $provider = 'gemini';
        $geminiResult = $this->gemini->evaluateAnswer(
            $validated['temaCurso'],
            $validated['pregunta'],
            $validated['respuestaEstudiante'],
            $validated['contenidoMaterial']
        );

        if ($this->isErrorResponse($geminiResult)) {
            $provider = 'openai';
            $openAiResult = $this->openai->evaluateAnswer(
                $validated['temaCurso'],
                $validated['pregunta'],
                $validated['respuestaEstudiante'],
                $validated['contenidoMaterial']
            );

            if ($this->isErrorResponse($openAiResult)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No se pudo evaluar la respuesta con Gemini ni con OpenAI',
                    'gemini_error' => $geminiResult,
                    'openai_error' => $openAiResult,
                ], 500);
            }

            $result = $openAiResult;
        } else {
            $result = $geminiResult;
        }

        return response()->json([
            'temaCurso' => $validated['temaCurso'],
            'pregunta' => $validated['pregunta'],
            'respuestaEstudiante' => $validated['respuestaEstudiante'],
            'evaluation' => $result,
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
