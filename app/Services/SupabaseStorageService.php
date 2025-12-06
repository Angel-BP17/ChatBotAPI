<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class SupabaseStorageService
{
    protected string $url;
    protected string $apiKey;
    protected string $bucket;

    public function __construct()
    {
        $this->url = rtrim(env('SUPABASE_URL'), '/');
        $this->apiKey = env('SUPABASE_API_KEY');
        $this->bucket = env('SUPABASE_BUCKET');
    }

    /**
     * Sube un archivo .txt a un bucket de Supabase.
     *
     * @param UploadedFile $file   Archivo recibido desde un formulario (Request::file)
     * @param string       $path   Ruta lógica dentro del bucket, ej: "cursos/cta/tema1"
     *
     * @return string Ruta del objeto dentro del bucket (para guardar en BD)
     *
     * @throws \RuntimeException Si la subida falla
     */
    public function uploadTxt(UploadedFile $file, string $path = ''): string
    {
        // Contenido del archivo
        $contents = file_get_contents($file->getRealPath());
        $contents = mb_convert_encoding(
            $contents,
            'UTF-8',
            mb_detect_encoding($contents, 'UTF-8, ISO-8859-1, Windows-1252', true) ?: 'UTF-8'
        );

        // Nombre final del archivo en el bucket
        $fileName = $file->getClientOriginalName();
        $folderPath = trim($path, '/');
        $objectPath = $folderPath ? "{$folderPath}/{$fileName}" : $fileName;

        // Endpoint REST de Supabase Storage
        $endpoint = "{$this->url}/storage/v1/object/{$this->bucket}/{$objectPath}";

        // Petición HTTP: el body es el binario del archivo
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Transfer-Encoding' => 'binary',
        ])->withBody($contents, 'text/plain; charset=utf-8')->post($endpoint);

        if (!$response->successful()) {
            throw new \RuntimeException('Error al subir archivo a Supabase: ' . $response->body());
        }

        // Ruta que se puede guardar en BD
        return $objectPath;
    }

    /**
     * Construye la URL pública a un objeto del bucket (asumiendo bucket público).
     */
    public function getPublicUrl(string $objectPath): string
    {
        $objectPath = ltrim($objectPath, '/');

        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$objectPath}";
    }
}
