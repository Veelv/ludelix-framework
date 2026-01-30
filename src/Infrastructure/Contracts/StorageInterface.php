<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Contracts;

use Ludelix\Infrastructure\ValueObjects\StorageResult;
use Ludelix\Infrastructure\ValueObjects\StorageFile;
use Ludelix\PRT\UploadedFile;
use DateTimeInterface;

/**
 * Interface principal para adaptadores de armazenamento
 * 
 * Define o contrato que todos os provedores de armazenamento devem implementar,
 * garantindo consistência na API independentemente do backend utilizado.
 */
interface StorageInterface
{
    /**
     * Armazena um arquivo no provedor de storage
     *
     * @param UploadedFile $file Arquivo a ser armazenado
     * @param string $path Caminho onde o arquivo será armazenado
     * @param array $options Opções adicionais específicas do provedor
     * @return StorageResult Resultado da operação de armazenamento
     */
    public function store(UploadedFile $file, string $path, array $options = []): StorageResult;

    /**
     * Recupera um arquivo do provedor
     *
     * @param string $path Caminho do arquivo
     * @return StorageFile|null Arquivo encontrado ou null se não existir
     */
    public function get(string $path): ?StorageFile;

    /**
     * Remove um arquivo do provedor
     *
     * @param string $path Caminho do arquivo a ser removido
     * @return bool True se removido com sucesso, false caso contrário
     */
    public function delete(string $path): bool;

    /**
     * Verifica se um arquivo existe no provedor
     *
     * @param string $path Caminho do arquivo
     * @return bool True se existe, false caso contrário
     */
    public function exists(string $path): bool;

    /**
     * Gera uma URL pública para acesso ao arquivo
     *
     * @param string $path Caminho do arquivo
     * @param array $options Opções adicionais (transformações, etc.)
     * @return string URL pública do arquivo
     */
    public function url(string $path, array $options = []): string;

    /**
     * Gera uma URL temporária com expiração para arquivos privados
     *
     * @param string $path Caminho do arquivo
     * @param DateTimeInterface $expiration Data de expiração
     * @param array $options Opções adicionais
     * @return string URL temporária assinada
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string;

    /**
     * Copia um arquivo dentro do mesmo provedor
     *
     * @param string $from Caminho de origem
     * @param string $to Caminho de destino
     * @return bool True se copiado com sucesso
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move um arquivo dentro do mesmo provedor
     *
     * @param string $from Caminho de origem
     * @param string $to Caminho de destino
     * @return bool True se movido com sucesso
     */
    public function move(string $from, string $to): bool;

    /**
     * Obtém o tamanho de um arquivo em bytes
     *
     * @param string $path Caminho do arquivo
     * @return int Tamanho em bytes
     */
    public function size(string $path): int;

    /**
     * Obtém a data de última modificação do arquivo
     *
     * @param string $path Caminho do arquivo
     * @return DateTimeInterface Data de última modificação
     */
    public function lastModified(string $path): DateTimeInterface;

    /**
     * Obtém o tipo MIME do arquivo
     *
     * @param string $path Caminho do arquivo
     * @return string Tipo MIME
     */
    public function mimeType(string $path): string;

    /**
     * Lista arquivos em um diretório
     *
     * @param string $directory Diretório a ser listado (vazio para raiz)
     * @param bool $recursive Se deve listar recursivamente
     * @return array Lista de arquivos encontrados
     */
    public function listFiles(string $directory = '', bool $recursive = false): array;

    /**
     * Obtém informações de configuração do provedor
     *
     * @return array Configurações do provedor
     */
    public function getConfig(): array;

    /**
     * Verifica se o provedor suporta uma funcionalidade específica
     *
     * @param string $feature Nome da funcionalidade
     * @return bool True se suportada
     */
    public function supports(string $feature): bool;
}

