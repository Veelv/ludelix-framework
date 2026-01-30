<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Contracts;

use Ludelix\PRT\UploadedFile;

/**
 * Interface para extração de metadados de arquivos
 * 
 * Define o contrato para extratores de metadados, permitindo
 * análise especializada por tipo de arquivo.
 */
interface MetadataExtractorInterface
{
    /**
     * Extrai metadados de um arquivo
     *
     * @param UploadedFile $file Arquivo para extração
     * @return array Metadados extraídos
     */
    public function extract(UploadedFile $file): array;

    /**
     * Verifica se o extrator suporta um tipo de arquivo
     *
     * @param string $mimeType Tipo MIME do arquivo
     * @return bool True se suportado
     */
    public function supports(string $mimeType): bool;

    /**
     * Extrai metadados EXIF de imagens
     *
     * @param UploadedFile $file Arquivo de imagem
     * @return array Dados EXIF extraídos
     */
    public function extractImageMetadata(UploadedFile $file): array;

    /**
     * Extrai metadados de vídeos
     *
     * @param UploadedFile $file Arquivo de vídeo
     * @return array Metadados de vídeo (duração, resolução, etc.)
     */
    public function extractVideoMetadata(UploadedFile $file): array;

    /**
     * Extrai metadados de documentos
     *
     * @param UploadedFile $file Arquivo de documento
     * @return array Metadados do documento (autor, título, etc.)
     */
    public function extractDocumentMetadata(UploadedFile $file): array;

    /**
     * Extrai metadados de áudio
     *
     * @param UploadedFile $file Arquivo de áudio
     * @return array Metadados de áudio (duração, bitrate, etc.)
     */
    public function extractAudioMetadata(UploadedFile $file): array;

    /**
     * Gera hash do arquivo para verificação de integridade
     *
     * @param UploadedFile $file Arquivo para hash
     * @param string $algorithm Algoritmo de hash (md5, sha1, sha256)
     * @return string Hash gerado
     */
    public function generateHash(UploadedFile $file, string $algorithm = 'sha256'): string;

    /**
     * Detecta o tipo real do arquivo baseado no conteúdo
     *
     * @param UploadedFile $file Arquivo para análise
     * @return string Tipo MIME detectado
     */
    public function detectRealMimeType(UploadedFile $file): string;
}

