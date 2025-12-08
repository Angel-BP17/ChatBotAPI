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
            // Crear prompt final mejorado para DALL-E 3
            $prompt = "Tema: $tema. $descripcion. Estilo: imagen educativa, para alumnos que hablan  español ,profesional, clara y visualmente atractiva.";

            // DALL-E 3: mejor calidad de imagen
            $response = $this->client->images()->create([
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard', // 'standard' o 'hd'
            ]);

            // Obtener URL de la imagen generada
            $imageUrl = $response->data[0]->url ?? null;

            if (!$imageUrl) {
                return [
                    'success' => false,
                    'error' => 'La API no devolvió una imagen válida'
                ];
            }

            // Descargar la imagen y convertirla a base64
            $imageContent = @file_get_contents($imageUrl);

            if ($imageContent === false) {
                return [
                    'success' => false,
                    'error' => 'No se pudo descargar la imagen desde OpenAI'
                ];
            }

            $imageBase64 = base64_encode($imageContent);

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

    public function generateCourseTopics(
        string $title,
        string $description,
        string $educationLevel
    ): array {
        // Puedes ajustar cuántos temas quieres por defecto
        $numTopics = 8;

        $prompt = "
Actúas como un docente experto en diseño curricular y planificación de clases.

Debes proponer aproximadamente $numTopics temas o lecciones para un curso con los siguientes datos:

- Título general del curso o unidad: \"$title\"
- Descripción del curso: \"$description\"
- Nivel educativo: \"$educationLevel\"

Cada tema debe:
- Estar redactado en lenguaje claro para estudiantes de ese nivel.
- Seguir un orden lógico de aprendizaje (de básico a avanzado).
- Tener un objetivo de aprendizaje breve.
- Indicar una cantidad aproximada de sesiones (entre 1 y 3).

Devuelve la información en formato JSON con esta estructura:

{
  \"course_title\": \"...\",
  \"education_level\": \"...\",
  \"topics\": [
    {
      \"title\": \"Título del tema\",
      \"objective\": \"Objetivo de aprendizaje claro\",
      \"estimated_sessions\": 2,
      \"summary\": \"Breve descripción del contenido del tema\"
    }
  ]
}

IMPORTANTE:
- Devuelve SOLO el JSON válido.
- No agregues explicación adicional antes ni después del JSON.
";

        $response = $this->client->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un planificador curricular para un LMS educativo. Generas temarios estructurados en JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        $content = $response->choices[0]->message->content ?? '{}';

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'raw' => $content,
                'message' => 'JSON inválido generado por el modelo',
            ];
        }

        return $data;
    }
}
