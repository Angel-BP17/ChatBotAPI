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
        string $questionType,
        string $materialContent = ''
    ): array {
        try {
            $typeText = match ($questionType) {
                'multiple_choice' => 'solo preguntas de opcion multiple con 4 alternativas y una respuesta correcta',
                'true_false' => 'solo preguntas de verdadero/falso',
                'open' => 'solo preguntas abiertas que requieran desarrollo',
                default => 'una mezcla de opcion multiple, verdadero/falso y abiertas',
            };

            $materialBlock = $materialContent ? "
Usa exclusivamente el siguiente material como referencia. No inventes datos fuera de este contenido.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END
" : 'Usa tu conocimiento general para generar las preguntas.';

            $prompt = "
Actua como un docente experto del curso \"$courseName\".
Genera $numQuestions preguntas sobre el tema \"$topicTitle\".
$materialBlock

- Tipo de preguntas: $typeText.
- Devuelve la respuesta en formato JSON con esta estructura:

[
  {
    \"question\": \"...\",
    \"type\": \"multiple_choice|true_false|open\",
    \"options\": [\"opcion 1\", \"opcion 2\", ...], // null si no aplica
    \"answer\": \"respuesta correcta o ejemplo de respuesta\"
  }
]
IMPORTANTE: Devuelve SOLO el JSON valido, sin texto adicional.
        ";

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un generador de cuestionarios para un LMS educativo.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '[]';

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => true,
                    'raw' => $content,
                    'message' => 'JSON invalido generado por OpenAI',
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Error al comunicarse con OpenAI',
                'details' => $e->getMessage(),
            ];
        }
    }

    public function generateImage(string $tema, string $descripcion): array
    {
        try {
            $prompt = "Tema: $tema. $descripcion. Estilo: imagen educativa, para alumnos que hablan espanol, profesional, clara y visualmente atractiva.";

            $response = $this->client->images()->create([
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ]);

            $imageUrl = $response->data[0]->url ?? null;

            if (!$imageUrl) {
                return [
                    'success' => false,
                    'error' => 'La API no devolvio una imagen valida'
                ];
            }

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
        $numTopics = 8;

        $prompt = "
Actuas como un docente experto en diseno curricular y planificacion de clases.

Debes proponer aproximadamente $numTopics temas o lecciones para un curso con los siguientes datos:

- Titulo general del curso o unidad: \"$title\"
- Descripcion del curso: \"$description\"
- Nivel educativo: \"$educationLevel\"

Cada tema debe:
- Estar redactado en lenguaje claro para estudiantes de ese nivel.
- Seguir un orden logico de aprendizaje (de basico a avanzado).
- Tener un objetivo de aprendizaje breve.
- Indicar una cantidad aproximada de sesiones (entre 1 y 3).

Devuelve la informacion en formato JSON con esta estructura:

{
  \"course_title\": \"...\",
  \"education_level\": \"...\",
  \"topics\": [
    {
      \"title\": \"Titulo del tema\",
      \"objective\": \"Objetivo de aprendizaje claro\",
      \"estimated_sessions\": 2,
      \"summary\": \"Breve descripcion del contenido del tema\"
    }
  ]
}

IMPORTANTE:
- Devuelve SOLO el JSON valido.
- No agregues explicacion adicional antes ni despues del JSON.
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
                'message' => 'JSON invalido generado por el modelo',
            ];
        }

        return $data;
    }

    public function generateSummary(
        string $topic,
        int $paragraphs,
        string $format,
        string $materialContent = ''
    ): array {
        try {
            $paragraphs = max(1, min(10, $paragraphs));

            $formatDescription = match ($format) {
                'simple' => 'un resumen sencillo, claro y en prosa continua',
                'detallado' => 'un resumen detallado, bien estructurado y explicativo',
                'bullet-points' => 'un resumen en formato de vinetas (bullet points), con ideas clave',
                default => 'un resumen sencillo, claro y en prosa continua',
            };

            $materialBlock = $materialContent ? "
Usa exclusivamente el siguiente material como fuente. No inventes datos fuera de este contenido.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END
" : 'Si no hay material, utiliza conocimiento general fiable y mantente conciso.';

            $prompt = "
Genera un resumen sobre el tema \"$topic\".
$materialBlock

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

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente educativo que resume material en JSON estructurado.',
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
                    'message' => 'JSON invalido generado por OpenAI',
                    'raw' => $content,
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Error al comunicarse con OpenAI',
                'details' => $e->getMessage(),
            ];
        }
    }

    public function evaluateAnswer(
        string $courseTopic,
        string $question,
        string $studentAnswer,
        string $materialContent = ''
    ): array {
        try {
            $materialBlock = $materialContent ? "
Evalua usando exclusivamente el siguiente material como referencia. Si falta informacion en el material, indicalo y basa la evaluacion solo en lo disponible.

Material de referencia:
<<<MATERIAL
$materialContent
<<<END
" : 'Evalua usando conocimiento general del tema, pero indica claramente cualquier supuesto necesario.';

            $prompt = "
Eres un docente experto en el tema \"$courseTopic\".
$materialBlock

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

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un evaluador educativo que responde unicamente en JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => true,
                    'message' => 'JSON invalido generado por OpenAI',
                    'raw' => $content,
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            return [
                'error' => true,
                'message' => 'Error al comunicarse con OpenAI',
                'details' => $e->getMessage(),
            ];
        }
    }
}
