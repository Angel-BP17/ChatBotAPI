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

        // Nombre final del archivo en el bucket
        $fileName = $file->getClientOriginalName();               // ejemplo: tema1.txt
        $folderPath = trim($path, '/');                             // "cursos/cta/tema1"
        $objectPath = $folderPath
            ? $folderPath . '/' . $fileName
            : $fileName;                                           // "cursos/cta/tema1/tema1.txt"

        // Endpoint REST de Supabase Storage
        $endpoint = "{$this->url}/storage/v1/object/{$this->bucket}/{$objectPath}";

        // Petición HTTP: el body es el binario del archivo
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'text/plain', // para .txt
        ])->post($endpoint, $contents);

        if (!$response->successful()) {
            throw new \RuntimeException('Error al subir archivo a Supabase: ' . $response->body());
        }

        // Lo que normalmente guardarías en BD para luego reconstruir la URL o buscar el objeto
        return $objectPath; // ej: "cursos/cta/tema1/tema1.txt"
    }

    /**
     * Construye la URL pública a un objeto del bucket (asumiendo bucket público).
     */
    public function getPublicUrl(string $objectPath): string
    {
        $objectPath = ltrim($objectPath, '/');

        // Formato típico de Supabase: /storage/v1/object/public/{bucket}/{objectPath}
        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$objectPath}";
    }
}
