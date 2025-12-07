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
     * Generar preguntas de un curso usando Gemini.
     */
    public function generateQuestions(
        string $courseName,
        string $topicTitle,
        int $numQuestions,
        string $questionType,
        string $materialContent = ''
    ): array {
        $typeDescription = match ($questionType) {
            'multiple_choice' => 'solo preguntas de opcion multiple con 4 alternativas y una unica respuesta correcta',
            'true_false' => 'solo preguntas de verdadero/falso',
            'open' => 'solo preguntas abiertas que requieran desarrollo',
            default => 'una mezcla de opcion multiple, verdadero/falso y preguntas abiertas',
        };

        $prompt = "
Actua como un docente experto del curso \"$courseName\".
Genera $numQuestions preguntas sobre el tema \"$topicTitle\" usando exclusivamente el siguiente material como fuente. No inventes datos fuera del material.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END

- Tipo de preguntas: $typeDescription.
- Devuelve la respuesta en formato JSON con esta estructura:

[
  {
    \"question\": \"...\",
    \"type\": \"multiple_choice|true_false|open\",
    \"options\": [\"opcion 1\", \"opcion 2\", \"opcion 3\", \"opcion 4\"], // null o [] si no aplica
    \"answer\": \"respuesta correcta o ejemplo de respuesta\"
  }
]

IMPORTANTE:
- Devuelve SOLO el JSON valido.
- No agregues texto antes o despues del JSON.
        ";

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
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
        $cleanText = preg_replace('/```json|```/i', '', $rawText);
        $decoded = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'message' => 'JSON invalido generado por Gemini',
                'raw' => $rawText,
            ];
        }

        return $decoded;
    }

    public function generateSummary(
        string $topic,
        int $paragraphs,
        string $format,
        string $materialContent = ''
    ): array {
        $paragraphs = max(1, min(10, $paragraphs));

        $formatDescription = match ($format) {
            'simple' => 'un resumen sencillo, claro y en prosa continua',
            'detallado' => 'un resumen detallado, bien estructurado y explicativo',
            'bullet-points' => 'un resumen en formato de vinetas (bullet points), con ideas clave',
            default => 'un resumen sencillo, claro y en prosa continua',
        };

        $prompt = "
Genera un resumen sobre el tema \"$topic\" usando exclusivamente el siguiente material como fuente. No inventes datos fuera del material.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END

Requisitos:
- Extension aproximada: $paragraphs parrafos.
- Formato del resumen: $formatDescription.
- Idioma: espanol.

Si el formato es 'bullet-points', organiza el contenido como una lista de puntos.
Devuelve la respuesta en formato JSON con esta estructura:

{
  \"topic\": \"...\",
  \"format\": \"simple|detallado|bullet-points\",
  \"paragraphs\": [
    \"parrafo o item 1\",
    \"parrafo o item 2\",
    ...
  ]
}

IMPORTANTE:
- Devuelve SOLO el JSON valido.
- No agregues texto antes o despues del JSON.
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
                'message' => 'JSON invalido generado por Gemini',
                'raw' => $rawText,
            ];
        }

        return $decoded;
    }

    public function evaluateAnswer(
        string $courseTopic,
        string $question,
        string $studentAnswer,
        string $materialContent = ''
    ): array {
        $prompt = "
Eres un docente experto en el tema \"$courseTopic\".
Evalua la respuesta del estudiante usando exclusivamente el siguiente material como referencia. Si falta informacion en el material, indicalo y basa la evaluacion solo en lo disponible.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END

PREGUNTA:
\"$question\"

RESPUESTA DEL ESTUDIANTE:
\"$studentAnswer\"

Tareas:
1. Evalua la precision conceptual, claridad y profundidad.
2. Asigna una puntuacion numerica de 0 a 100.
3. Clasifica la respuesta en una categoria: \"Excelente\", \"Buena\", \"Regular\" o \"Insuficiente\".
4. Da retroalimentacion constructiva y concreta.
5. Senala fortalezas y aspectos a mejorar.

Devuelve la respuesta en JSON con esta estructura:

{
  \"score\": 0-100,
  \"grade\": \"Excelente|Buena|Regular|Insuficiente\",
  \"feedback\": \"comentario general en 3-6 lineas\",
  \"strengths\": [\"punto fuerte 1\", \"punto fuerte 2\"],
  \"improvements\": [\"mejora 1\", \"mejora 2\"]
}

IMPORTANTE:
- Devuelve SOLO el JSON valido.
- No agregues nada antes ni despues del JSON.
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
                'message' => 'JSON invalido generado por Gemini',
                'raw' => $rawText,
            ];
        }

        return $decoded;
    }
}