<?php

namespace App\Services;

use Exception;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para interactuar con Google Cloud Translation API.
 */
class GoogleTranslateService
{
    protected TranslationServiceClient $client;
    protected string $projectId;
    protected int $chunkSize = 5000;

    public function __construct()
    {
        $credentialsPath = config('services.google_translate.credentials_file', '');
        $this->projectId = env('GOOGLE_CLOUD_PROJECT_ID');

        if (!file_exists($credentialsPath)) {
            throw new Exception("Archivo de credenciales no encontrado en: $credentialsPath");
        }

        try {
            $keyFile = json_decode(file_get_contents($credentialsPath), true);

            $this->client = new TranslationServiceClient([
                'credentials' => $keyFile,
                /* 'transport' => 'rest',
                'disable_ssl_verification' => true, */
            ]);

            Log::info("Google Translate Client inicializado correctamente");

        } catch (Exception $e) {
            Log::error('Error inicializando Google Translate Client:', [
                'message' => $e->getMessage()
            ]);
            throw new Exception("Error al inicializar el cliente de Google Translate. Revise las credenciales.");
        }
    }

    /**
     * Traduce un texto a un idioma destino.
     *
     * @param string $text Texto a traducir
     * @param string $targetLanguage Código del idioma destino (ej: 'es', 'en', 'fr')
     * @param string|null $sourceLanguage Código del idioma origen (opcional)
     * @return string Texto traducido
     */
    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): string
    {
        if (empty($text)) {
            return '';
        }

        // Si el texto es pequeño, traducimos directamente
        if (mb_strlen($text) <= $this->chunkSize) {
            return $this->translateChunk($text, $targetLanguage, $sourceLanguage);
        }

        // Dividimos el texto en bloques de $chunkSize caracteres
        $chunks = str_split($text, $this->chunkSize);
        $translated = '';

        foreach ($chunks as $chunk) {
            $translated .= $this->translateChunk($chunk, $targetLanguage, $sourceLanguage);
        }

        return $translated;
    }

    /**
     * Traduce un bloque de texto
     */
    protected function translateChunk(string $text, string $targetLanguage, ?string $sourceLanguage = null): string
    {
        try {
            $formattedParent = $this->client->locationName($this->projectId, 'global');

            $request = new TranslateTextRequest([
                'contents' => [$text],
                'target_language_code' => $targetLanguage,
                'parent' => $formattedParent,
            ]);

            if ($sourceLanguage) {
                $request->setSourceLanguageCode($sourceLanguage);
            }

            $response = $this->client->translateText($request);

            return $response->getTranslations()[0]->getTranslatedText();
        } catch (Exception $e) {
            Log::error('Error durante la traducción:', [
                'text' => $text,
                'target' => $targetLanguage,
                'error' => $e->getMessage()
            ]);

            // Devolver el texto original si falla la traducción
            return $text;
        }
    }
}
