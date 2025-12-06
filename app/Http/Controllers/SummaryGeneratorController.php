<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class SummaryGeneratorController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function generate(Request $request)
    {
        // ✅ Validación según tu formato
        $validated = $request->validate([
            'tema' => 'required|string',
            'extensionParrafos' => 'required|integer|min:1|max:10',
            'formato' => 'required|string|in:simple,detallado,bullet-points',
        ]);

        $topic = $validated['tema'];
        $paragraphs = $validated['extensionParrafos'];
        $format = $validated['formato'];

        $summary = $this->gemini->generateSummary(
            $topic,
            $paragraphs,
            $format
        );

        // Si hubo error en el servicio, devolvemos 500
        if (isset($summary['error']) && $summary['error'] === true) {
            return response()->json($summary, 500);
        }

        return response()->json([
            'tema' => $topic,
            'extensionParrafos' => $paragraphs,
            'formato' => $format,
            'summary' => $summary,
        ]);
    }
}
