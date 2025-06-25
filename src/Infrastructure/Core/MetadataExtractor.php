<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Core;

use Ludelix\Infrastructure\Contracts\MetadataExtractorInterface;
use Ludelix\PRT\UploadedFile;

/**
 * Implementação do extrator de metadados
 * 
 * Extrai metadados específicos por tipo de arquivo, incluindo
 * EXIF para imagens, informações de vídeo, e metadados de documentos.
 */
class MetadataExtractor implements MetadataExtractorInterface
{
    private array $supportedTypes = [
        'image/*',
        'video/*',
        'audio/*',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/*'
    ];

    public function extract(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $metadata = $this->extractBasicMetadata($file);

        // Extrair metadados específicos por tipo
        if (str_starts_with($mimeType, 'image/')) {
            $metadata = array_merge($metadata, $this->extractImageMetadata($file));
        } elseif (str_starts_with($mimeType, 'video/')) {
            $metadata = array_merge($metadata, $this->extractVideoMetadata($file));
        } elseif (str_starts_with($mimeType, 'audio/')) {
            $metadata = array_merge($metadata, $this->extractAudioMetadata($file));
        } elseif ($this->isDocument($mimeType)) {
            $metadata = array_merge($metadata, $this->extractDocumentMetadata($file));
        }

        return $metadata;
    }

    public function supports(string $mimeType): bool
    {
        foreach ($this->supportedTypes as $supportedType) {
            if (str_ends_with($supportedType, '*')) {
                $prefix = str_replace('*', '', $supportedType);
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            } elseif ($mimeType === $supportedType) {
                return true;
            }
        }

        return false;
    }

    public function extractImageMetadata(UploadedFile $file): array
    {
        $metadata = [];
        $path = $file->getPathname();

        // Obter informações básicas da imagem
        $imageInfo = getimagesize($path);
        if ($imageInfo) {
            $metadata['width'] = $imageInfo[0];
            $metadata['height'] = $imageInfo[1];
            $metadata['type'] = $imageInfo[2];
            $metadata['bits'] = $imageInfo['bits'] ?? null;
            $metadata['channels'] = $imageInfo['channels'] ?? null;
            $metadata['mime'] = $imageInfo['mime'] ?? null;
        }

        // Extrair dados EXIF se disponível
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'])) {
            try {
                $exifData = exif_read_data($path);
                if ($exifData) {
                    $metadata['exif'] = $this->processExifData($exifData);
                }
            } catch (\Exception $e) {
                $metadata['exif_error'] = $e->getMessage();
            }
        }

        // Calcular aspect ratio
        if (isset($metadata['width']) && isset($metadata['height']) && $metadata['height'] > 0) {
            $metadata['aspect_ratio'] = round($metadata['width'] / $metadata['height'], 2);
        }

        // Detectar orientação
        if (isset($metadata['exif']['Orientation'])) {
            $metadata['orientation'] = $this->getOrientationDescription($metadata['exif']['Orientation']);
        }

        return $metadata;
    }

    public function extractVideoMetadata(UploadedFile $file): array
    {
        $metadata = [];
        $path = $file->getPathname();

        // Usar ffprobe se disponível
        if ($this->isCommandAvailable('ffprobe')) {
            try {
                $command = sprintf(
                    'ffprobe -v quiet -print_format json -show_format -show_streams %s',
                    escapeshellarg($path)
                );
                
                $output = shell_exec($command);
                if ($output) {
                    $data = json_decode($output, true);
                    if ($data) {
                        $metadata = $this->processFFProbeData($data);
                    }
                }
            } catch (\Exception $e) {
                $metadata['ffprobe_error'] = $e->getMessage();
            }
        }

        // Fallback para getID3 se disponível
        if (empty($metadata) && class_exists('\getID3')) {
            try {
                $getID3 = new \getID3();
                $data = $getID3->analyze($path);
                if ($data) {
                    $metadata = $this->processGetID3VideoData($data);
                }
            } catch (\Exception $e) {
                $metadata['getid3_error'] = $e->getMessage();
            }
        }

        return $metadata;
    }

    public function extractAudioMetadata(UploadedFile $file): array
    {
        $metadata = [];
        $path = $file->getPathname();

        // Usar getID3 se disponível
        if (class_exists('\getID3')) {
            try {
                $getID3 = new \getID3();
                $data = $getID3->analyze($path);
                if ($data) {
                    $metadata = $this->processGetID3AudioData($data);
                }
            } catch (\Exception $e) {
                $metadata['getid3_error'] = $e->getMessage();
            }
        }

        return $metadata;
    }

    public function extractDocumentMetadata(UploadedFile $file): array
    {
        $metadata = [];
        $mimeType = $file->getMimeType();
        $path = $file->getPathname();

        switch ($mimeType) {
            case 'application/pdf':
                $metadata = $this->extractPDFMetadata($path);
                break;

            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                $metadata = $this->extractWordMetadata($path);
                break;

            case 'text/plain':
                $metadata = $this->extractTextMetadata($path);
                break;
        }

        return $metadata;
    }

    public function generateHash(UploadedFile $file, string $algorithm = 'sha256'): string
    {
        return hash_file($algorithm, $file->getPathname());
    }

    public function detectRealMimeType(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Extrai metadados básicos comuns a todos os arquivos
     */
    private function extractBasicMetadata(UploadedFile $file): array
    {
        return [
            'original_name' => $file->getClientOriginalName(),
            'original_extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'real_mime_type' => $this->detectRealMimeType($file),
            'hash_md5' => $this->generateHash($file, 'md5'),
            'hash_sha256' => $this->generateHash($file, 'sha256'),
            'extracted_at' => date('c'),
        ];
    }

    /**
     * Processa dados EXIF de imagens
     */
    private function processExifData(array $exifData): array
    {
        $processed = [];

        // Campos importantes do EXIF
        $importantFields = [
            'DateTime', 'DateTimeOriginal', 'DateTimeDigitized',
            'Make', 'Model', 'Software',
            'Orientation', 'XResolution', 'YResolution',
            'Flash', 'FocalLength', 'ExposureTime', 'FNumber',
            'ISOSpeedRatings', 'WhiteBalance', 'GPS'
        ];

        foreach ($importantFields as $field) {
            if (isset($exifData[$field])) {
                $processed[$field] = $exifData[$field];
            }
        }

        // Processar dados GPS se disponível
        if (isset($exifData['GPS'])) {
            $processed['gps'] = $this->processGPSData($exifData['GPS']);
        }

        return $processed;
    }

    /**
     * Processa dados GPS do EXIF
     */
    private function processGPSData(array $gpsData): array
    {
        $processed = [];

        if (isset($gpsData['GPSLatitude']) && isset($gpsData['GPSLatitudeRef'])) {
            $processed['latitude'] = $this->convertGPSCoordinate(
                $gpsData['GPSLatitude'],
                $gpsData['GPSLatitudeRef']
            );
        }

        if (isset($gpsData['GPSLongitude']) && isset($gpsData['GPSLongitudeRef'])) {
            $processed['longitude'] = $this->convertGPSCoordinate(
                $gpsData['GPSLongitude'],
                $gpsData['GPSLongitudeRef']
            );
        }

        if (isset($gpsData['GPSAltitude'])) {
            $processed['altitude'] = $gpsData['GPSAltitude'];
        }

        return $processed;
    }

    /**
     * Converte coordenadas GPS para formato decimal
     */
    private function convertGPSCoordinate(array $coordinate, string $ref): float
    {
        $degrees = $coordinate[0];
        $minutes = $coordinate[1];
        $seconds = $coordinate[2];

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($ref, ['S', 'W'])) {
            $decimal *= -1;
        }

        return $decimal;
    }

    /**
     * Obtém descrição da orientação da imagem
     */
    private function getOrientationDescription(int $orientation): string
    {
        $orientations = [
            1 => 'Normal',
            2 => 'Flip horizontal',
            3 => 'Rotate 180°',
            4 => 'Flip vertical',
            5 => 'Rotate 90° CCW + Flip horizontal',
            6 => 'Rotate 90° CW',
            7 => 'Rotate 90° CW + Flip horizontal',
            8 => 'Rotate 90° CCW'
        ];

        return $orientations[$orientation] ?? 'Unknown';
    }

    /**
     * Processa dados do FFProbe para vídeos
     */
    private function processFFProbeData(array $data): array
    {
        $metadata = [];

        if (isset($data['format'])) {
            $format = $data['format'];
            $metadata['duration'] = (float) ($format['duration'] ?? 0);
            $metadata['bitrate'] = (int) ($format['bit_rate'] ?? 0);
            $metadata['format_name'] = $format['format_name'] ?? null;
        }

        if (isset($data['streams'])) {
            foreach ($data['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    $metadata['video'] = [
                        'codec' => $stream['codec_name'] ?? null,
                        'width' => $stream['width'] ?? null,
                        'height' => $stream['height'] ?? null,
                        'fps' => $this->parseFPS($stream['r_frame_rate'] ?? '0/1'),
                        'bitrate' => (int) ($stream['bit_rate'] ?? 0),
                    ];
                } elseif ($stream['codec_type'] === 'audio') {
                    $metadata['audio'] = [
                        'codec' => $stream['codec_name'] ?? null,
                        'sample_rate' => (int) ($stream['sample_rate'] ?? 0),
                        'channels' => (int) ($stream['channels'] ?? 0),
                        'bitrate' => (int) ($stream['bit_rate'] ?? 0),
                    ];
                }
            }
        }

        return $metadata;
    }

    /**
     * Processa dados do getID3 para vídeos
     */
    private function processGetID3VideoData(array $data): array
    {
        $metadata = [];

        if (isset($data['playtime_seconds'])) {
            $metadata['duration'] = (float) $data['playtime_seconds'];
        }

        if (isset($data['bitrate'])) {
            $metadata['bitrate'] = (int) $data['bitrate'];
        }

        if (isset($data['video'])) {
            $video = $data['video'];
            $metadata['video'] = [
                'codec' => $video['codec'] ?? null,
                'width' => $video['resolution_x'] ?? null,
                'height' => $video['resolution_y'] ?? null,
                'fps' => $video['frame_rate'] ?? null,
            ];
        }

        return $metadata;
    }

    /**
     * Processa dados do getID3 para áudio
     */
    private function processGetID3AudioData(array $data): array
    {
        $metadata = [];

        if (isset($data['playtime_seconds'])) {
            $metadata['duration'] = (float) $data['playtime_seconds'];
        }

        if (isset($data['bitrate'])) {
            $metadata['bitrate'] = (int) $data['bitrate'];
        }

        if (isset($data['audio'])) {
            $audio = $data['audio'];
            $metadata['audio'] = [
                'codec' => $audio['codec'] ?? null,
                'sample_rate' => $audio['sample_rate'] ?? null,
                'channels' => $audio['channels'] ?? null,
                'bitrate' => $audio['bitrate'] ?? null,
            ];
        }

        // Tags de metadados
        if (isset($data['tags'])) {
            $metadata['tags'] = $data['tags'];
        }

        return $metadata;
    }

    /**
     * Extrai metadados de PDF
     */
    private function extractPDFMetadata(string $path): array
    {
        $metadata = [];

        // Usar pdfinfo se disponível
        if ($this->isCommandAvailable('pdfinfo')) {
            try {
                $output = shell_exec('pdfinfo ' . escapeshellarg($path));
                if ($output) {
                    $metadata = $this->parsePDFInfo($output);
                }
            } catch (\Exception $e) {
                $metadata['pdfinfo_error'] = $e->getMessage();
            }
        }

        return $metadata;
    }

    /**
     * Extrai metadados de documentos Word
     */
    private function extractWordMetadata(string $path): array
    {
        // Implementação básica - pode ser expandida com bibliotecas específicas
        return [
            'type' => 'word_document',
            'extracted_with' => 'basic_parser'
        ];
    }

    /**
     * Extrai metadados de arquivos de texto
     */
    private function extractTextMetadata(string $path): array
    {
        $content = file_get_contents($path);
        
        return [
            'line_count' => substr_count($content, "\n") + 1,
            'word_count' => str_word_count($content),
            'character_count' => strlen($content),
            'encoding' => mb_detect_encoding($content),
        ];
    }

    /**
     * Verifica se um comando está disponível no sistema
     */
    private function isCommandAvailable(string $command): bool
    {
        $output = shell_exec("which $command");
        return !empty($output);
    }

    /**
     * Verifica se é um documento suportado
     */
    private function isDocument(string $mimeType): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        return in_array($mimeType, $documentTypes);
    }

    /**
     * Converte frame rate de string para float
     */
    private function parseFPS(string $fps): float
    {
        if (str_contains($fps, '/')) {
            [$num, $den] = explode('/', $fps);
            return $den > 0 ? (float) $num / (float) $den : 0.0;
        }

        return (float) $fps;
    }

    /**
     * Processa saída do pdfinfo
     */
    private function parsePDFInfo(string $output): array
    {
        $metadata = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim(strtolower(str_replace(' ', '_', $key)));
                $value = trim($value);
                
                if (!empty($value)) {
                    $metadata[$key] = $value;
                }
            }
        }

        return $metadata;
    }
}

