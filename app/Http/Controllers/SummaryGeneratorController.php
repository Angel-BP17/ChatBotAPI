<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class SummaryGeneratorController extends Controller
{
    protected GeminiService $gemini;
    protected OpenAIService $openai;

    public function __construct(GeminiService $gemini, OpenAIService $openai)
    {
        $this->gemini = $gemini;
        $this->openai = $openai;
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'tema' => 'required|string',
            'extensionParrafos' => 'required|integer|min:1|max:10',
            'formato' => 'required|string|in:simple,detallado,bullet-points',
            'contenidoMaterial' => 'required|string',
        ]);

        $topic = $validated['tema'];
        $paragraphs = $validated['extensionParrafos'];
        $format = $validated['formato'];

        $provider = 'gemini';
        $geminiResult = $this->gemini->generateSummary(
            $topic,
            $paragraphs,
            $format,
            $validated['contenidoMaterial']
        );

        if ($this->isErrorResponse($geminiResult)) {
            $provider = 'openai';
            $openAiResult = $this->openai->generateSummary(
                $topic,
                $paragraphs,
                $format,
                $validated['contenidoMaterial']
            );

            if ($this->isErrorResponse($openAiResult)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No se pudo generar el resumen con Gemini ni con OpenAI',
                    'gemini_error' => $geminiResult,
                    'openai_error' => $openAiResult,
                ], 500);
            }

            $summary = $openAiResult;
        } else {
            $summary = $geminiResult;
        }

        return response()->json([
            'tema' => $topic,
            'extensionParrafos' => $paragraphs,
            'formato' => $format,
            'summary' => $summary,
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
