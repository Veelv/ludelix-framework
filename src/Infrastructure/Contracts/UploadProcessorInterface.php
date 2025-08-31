<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Contracts;

use Ludelix\Infrastructure\ValueObjects\UploadResult;
use Ludelix\Infrastructure\ValueObjects\UploadConfig;
use Ludelix\Infrastructure\ValueObjects\ChunkedUpload;
use Ludelix\PRT\UploadedFile;

/**
 * Interface para processamento de uploads
 * 
 * Define o contrato para processadores de upload, incluindo suporte
 * a uploads chunked e uploads resumíveis.
 */
interface UploadProcessorInterface
{
    /**
     * Processa um upload simples
     *
     * @param UploadedFile $file Arquivo a ser processado
     * @param UploadConfig $config Configuração do upload
     * @return UploadResult Resultado do processamento
     */
    public function process(UploadedFile $file, UploadConfig $config): UploadResult;

    /**
     * Processa um upload chunked
     *
     * @param ChunkedUpload $upload Dados do upload chunked
     * @return UploadResult Resultado do processamento
     */
    public function processChunked(ChunkedUpload $upload): UploadResult;

    /**
     * Retoma um upload interrompido
     *
     * @param string $uploadId Identificador do upload
     * @return UploadResult Resultado da retomada
     */
    public function resumeUpload(string $uploadId): UploadResult;

    /**
     * Cancela um upload em andamento
     *
     * @param string $uploadId Identificador do upload
     * @return bool True se cancelado com sucesso
     */
    public function cancelUpload(string $uploadId): bool;

    /**
     * Obtém o status de um upload
     *
     * @param string $uploadId Identificador do upload
     * @return array Status do upload
     */
    public function getUploadStatus(string $uploadId): array;

    /**
     * Inicia uma sessão de upload chunked
     *
     * @param string $filename Nome do arquivo
     * @param int $totalSize Tamanho total do arquivo
     * @param UploadConfig $config Configuração do upload
     * @return string Identificador da sessão
     */
    public function initializeChunkedUpload(string $filename, int $totalSize, UploadConfig $config): string;

    /**
     * Finaliza uma sessão de upload chunked
     *
     * @param string $uploadId Identificador do upload
     * @return UploadResult Resultado final
     */
    public function finalizeChunkedUpload(string $uploadId): UploadResult;

    /**
     * Limpa uploads expirados
     *
     * @return int Número de uploads limpos
     */
    public function cleanupExpiredUploads(): int;
}

