<?php

namespace App\Services;

use OpenAI;

class OpenAIService
{
    protected $client;
    protected string $model;

    public function __construct()
    {
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
        $this->model = env('OPENAI_MODEL', 'gpt-4.1-mini');
    }

    public function generateQuestions(
        string $courseName,
        string $topicTitle,
        int $numQuestions,
        string $questionType
    ): array {
        $typeText = match ($questionType) {
            'multiple_choice' => 'solo preguntas de opción múltiple con 4 alternativas y una respuesta correcta',
            'true_false' => 'solo preguntas de verdadero/falso',
            'open' => 'solo preguntas abiertas que requieran desarrollo',
            default => 'una mezcla de opción múltiple, verdadero/falso y abiertas',
        };

        $prompt = "
Actúa como un docente experto del curso \"$courseName\".
Genera $numQuestions preguntas sobre el tema \"$topicTitle\".

- Tipo de preguntas: $typeText.
- Devuelve la respuesta en formato JSON con esta estructura:

[
  {
    \"question\": \"...\",
    \"type\": \"multiple_choice|true_false|open\",
    \"options\": [\"opción 1\", \"opción 2\", ...], // null si no aplica
    \"answer\": \"respuesta correcta o ejemplo de respuesta\"
  }
]
IMPORTANTE: Devuelve SOLO el JSON válido, sin texto adicional.
        ";

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un generador de cuestionarios para un LMS educativo.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response->choices[0]->message->content ?? '[]';

        // Intentamos decodificar el JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Si falló, lo devolvemos en bruto para depurar
            return [
                'raw' => $content,
                'error' => 'JSON inválido generado por el modelo',
            ];
        }

        return $data;
    }

    public function generateImage(string $tema, string $descripcion): array
    {
        try {
            // Crear prompt final
            $prompt = "Tema: $tema\nDescripción: $descripcion\nGenera una imagen educativa y visualmente clara.";

            // Llamada al endpoint de imágenes
            $response = $this->client->images()->create([
                'model' => 'gpt-image-1',   // o 'dall-e-3' si prefieres
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'b64_json', // para obtener base64
            ]);

            // La imagen viene codificada en base64
            $imageBase64 = $response->data[0]->b64_json ?? null;

            if (!$imageBase64) {
                return [
                    'success' => false,
                    'error' => 'La API no devolvió una imagen válida'
                ];
            }

            return [
                'success' => true,
                'image_base64' => $imageBase64
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al generar imagen: ' . $e->getMessage()
            ];
        }
    }
}
