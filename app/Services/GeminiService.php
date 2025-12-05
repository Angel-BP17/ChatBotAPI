<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-pro');
    }

    /**
     * Generar preguntas de un curso usando Gemini
     */
    public function generateQuestions(
        string $courseName,
        string $topicTitle,
        int $numQuestions,
        string $questionType
    ): array {

        // Mapeo de tipo humano → tipo técnico
        $typeDescription = match ($questionType) {
            'multiple_choice' => 'solo preguntas de opción múltiple con 4 alternativas y una única respuesta correcta',
            'true_false' => 'solo preguntas de verdadero/falso',
            'open' => 'solo preguntas abiertas que requieran desarrollo',
            default => 'una mezcla de opción múltiple, verdadero/falso y preguntas abiertas',
        };

        $prompt = "
Actúa como un docente experto del curso \"$courseName\".
Genera $numQuestions preguntas sobre el tema \"$topicTitle\".

- Tipo de preguntas: $typeDescription.
- Devuelve la respuesta en formato JSON con esta estructura:

[
  {
    \"question\": \"...\",
    \"type\": \"multiple_choice|true_false|open\",
    \"options\": [\"opción 1\", \"opción 2\", \"opción 3\", \"opción 4\"], // null o [] si no aplica
    \"answer\": \"respuesta correcta o ejemplo de respuesta\"
  }
]

IMPORTANTE:
- Devuelve SOLO el JSON válido.
- No agregues texto antes o después del JSON.
        ";

        // Llamada HTTP a Gemini
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]
            );

        if (!$response->ok()) {
            return [
                'error' => true,
                'message' => 'Error al comunicarse con Gemini',
                'details' => $response->json()
            ];
        }

        $data = $response->json();

        // Gemini usualmente responde así:
        // candidates[0].content.parts[0].text
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        // Eliminar ```json ``` o ``` si vienen
        $cleanText = preg_replace('/```json|```/i', '', $rawText);

        $decoded = json_decode($cleanText, true);

        // Si falla el JSON → retornamos respuesta cruda para debugging
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'message' => 'JSON inválido generado por Gemini',
                'raw' => $rawText,
            ];
        }

        return $decoded;
    }
}
