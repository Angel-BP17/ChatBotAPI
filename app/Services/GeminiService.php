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

    public function generateSummary(
        string $topic,
        int $paragraphs,
        string $format
    ): array {
        // Validamos límites por si acaso
        $paragraphs = max(1, min(10, $paragraphs));

        $formatDescription = match ($format) {
            'simple' => 'un resumen sencillo, claro y en prosa continua',
            'detallado' => 'un resumen detallado, bien estructurado y explicativo',
            'bullet-points' => 'un resumen en formato de viñetas (bullet points), con ideas clave',
            default => 'un resumen sencillo, claro y en prosa continua',
        };

        $prompt = "
Genera un resumen sobre el tema \"$topic\".

Requisitos:
- Extensión aproximada: $paragraphs párrafos.
- Formato del resumen: $formatDescription.
- Idioma: español.

Si el formato es 'bullet-points', organiza el contenido como una lista de puntos.
Devuelve la respuesta en formato JSON con esta estructura:

{
  \"topic\": \"...\",
  \"format\": \"simple|detallado|bullet-points\",
  \"paragraphs\": [
    \"párrafo o ítem 1\",
    \"párrafo o ítem 2\",
    ...
  ]
}

IMPORTANTE:
- Devuelve SOLO el JSON válido.
- No agregues texto antes o después del JSON.
    ";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
                "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}",
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
                'details' => $response->json(),
            ];
        }

        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $cleanText = preg_replace('/```json|```/i', '', $rawText);

        $decoded = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'message' => 'JSON inválido generado por Gemini',
                'raw' => $rawText,
            ];
        }

        return $decoded;
    }

    public function evaluateAnswer(
        string $courseTopic,
        string $question,
        string $studentAnswer
    ): array {
        $prompt = "
Eres un docente experto en el tema \"$courseTopic\".

Debes evaluar la siguiente respuesta de un estudiante:

PREGUNTA:
\"$question\"

RESPUESTA DEL ESTUDIANTE:
\"$studentAnswer\"

Tareas:
1. Evalúa la precisión conceptual, claridad y profundidad.
2. Asigna una puntuación numérica de 0 a 100.
3. Clasifica la respuesta en una categoría: \"Excelente\", \"Buena\", \"Regular\" o \"Insuficiente\".
4. Da retroalimentación constructiva y concreta.
5. Señala fortalezas y aspectos a mejorar.

Devuelve la respuesta en JSON con esta estructura:

{
  \"score\": 0-100,
  \"grade\": \"Excelente|Buena|Regular|Insuficiente\",
  \"feedback\": \"comentario general en 3-6 líneas\",
  \"strengths\": [\"punto fuerte 1\", \"punto fuerte 2\"],
  \"improvements\": [\"mejora 1\", \"mejora 2\"]
}

IMPORTANTE:
- Devuelve SOLO el JSON válido.
- No agregues nada antes ni después del JSON.
    ";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
                "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}",
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
                'details' => $response->json(),
            ];
        }

        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        $cleanText = preg_replace('/```json|```/i', '', $rawText);

        $decoded = json_decode($cleanText, true);

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
