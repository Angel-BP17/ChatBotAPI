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
        $validated = $request->validate([
            'tema' => 'required|string',
            'extensionParrafos' => 'required|integer|min:1|max:10',
            'formato' => 'required|string|in:simple,detallado,bullet-points',
            'contenidoMaterial' => 'required|string',
        ]);

        $topic = $validated['tema'];
        $paragraphs = $validated['extensionParrafos'];
        $format = $validated['formato'];

        $summary = $this->gemini->generateSummary(
            $topic,
            $paragraphs,
            $format,
            $validated['contenidoMaterial']
        );

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