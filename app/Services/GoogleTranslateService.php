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
            ]);
        } catch (Exception $e) {
            Log::error('Error inicializando Google Translate Client:', [
                'message' => $e->getMessage()
            ]);
            throw new Exception("Error al inicializar el cliente de Google Translate. Revise las credenciales.");
        }
    }

    /**
     * Traduce un texto o array de textos a un idioma destino.
     *
     * @param string|array $text Texto(s) a traducir
     * @param string $targetLanguage Código del idioma destino (ej: 'es', 'en', 'fr')
     * @param string|null $sourceLanguage Código del idioma origen (opcional)
     * @return string|array Texto(s) traducido(s) - mantiene el tipo de entrada
     */
    public function translate(string|array $text, string $targetLanguage, ?string $sourceLanguage = 'es'): string|array
    {
        // Si es un array, procesarlo como batch
        if (is_array($text)) {
            return $this->translateBatch($text, $targetLanguage, $sourceLanguage);
        }

        // Si es string vacío
        if (empty($text)) {
            return '';
        }

        // Si el texto es pequeño, traducimos directamente
        if (mb_strlen($text) <= $this->chunkSize) {
            return $this->translateChunk([$text], $targetLanguage, $sourceLanguage)[0];
        }

        // Dividimos el texto en bloques de $chunkSize caracteres
        $chunks = str_split($text, $this->chunkSize);
        $translated = '';

        foreach ($chunks as $chunk) {
            $result = $this->translateChunk([$chunk], $targetLanguage, $sourceLanguage);
            $translated .= $result[0];
        }

        return $translated;
    }

    /**
     * Traduce un array de textos en una sola llamada API
     *
     * @param array $texts Array de textos a traducir
     * @param string $targetLanguage Código del idioma destino
     * @param string|null $sourceLanguage Código del idioma origen
     * @return array Array de textos traducidos (mantiene el orden)
     */
    protected function translateBatch(array $texts, string $targetLanguage, ?string $sourceLanguage = 'es'): array
    {
        if (empty($texts)) {
            return [];
        }

        // Filtrar textos vacíos pero mantener índices
        $textsToTranslate = [];
        $emptyIndices = [];

        foreach ($texts as $index => $text) {
            if (empty($text)) {
                $emptyIndices[] = $index;
            } else {
                $textsToTranslate[$index] = $text;
            }
        }

        // Si todos están vacíos
        if (empty($textsToTranslate)) {
            return array_fill(0, count($texts), '');
        }

        try {
            // Traducir todos los textos en una sola llamada
            $translated = $this->translateChunk(array_values($textsToTranslate), $targetLanguage, $sourceLanguage);

            // Reconstruir array con índices originales
            $result = [];
            $translatedIndex = 0;

            foreach ($texts as $index => $text) {
                if (in_array($index, $emptyIndices)) {
                    $result[$index] = '';
                } else {
                    $result[$index] = $translated[$translatedIndex] ?? $text;
                    $translatedIndex++;
                }
            }

            return array_values($result);

        } catch (Exception $e) {
            Log::error('Error durante la traducción batch:', [
                'texts_count' => count($texts),
                'target' => $targetLanguage,
                'error' => $e->getMessage()
            ]);

            // Devolver los textos originales si falla
            return $texts;
        }
    }

    /**
     * Traduce un bloque de uno o más textos
     *
     * @param array $texts Array de textos a traducir
     * @param string $targetLanguage Código del idioma destino
     * @param string|null $sourceLanguage Código del idioma origen
     * @return array Array de textos traducidos
     */
    protected function translateChunk(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        try {
            $formattedParent = $this->client->locationName($this->projectId, 'global');

            $request = new TranslateTextRequest([
                'contents' => $texts,
                'target_language_code' => $targetLanguage,
                'parent' => $formattedParent,
            ]);

            if ($sourceLanguage) {
                $request->setSourceLanguageCode($sourceLanguage);
            }

            $response = $this->client->translateText($request);
            $translations = $response->getTranslations();

            $result = [];
            foreach ($translations as $translation) {
                $result[] = $translation->getTranslatedText();
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Error durante la traducción:', [
                'texts_count' => count($texts),
                'target' => $targetLanguage,
                'error' => $e->getMessage()
            ]);

            // Devolver los textos originales si falla la traducción
            return $texts;
        }
    }
}
