<?php

namespace Ludelix\Security;

class CsrfManager
{
    /**
     * O nome da chave da sessão onde o token é armazenado.
     */
    protected const SESSION_KEY = '_csrf_token';

    /**
     * O nome do campo do formulário.
     */
    protected const FORM_KEY = '_token';

    /**
     * Inicia a sessão se ainda não estiver ativa.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Gera e armazena um novo token CSRF na sessão.
     *
     * @return string O token gerado.
     */
    public function generateToken(): string
    {
        $this->startSession();
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;
        return $token;
    }

    /**
     * Obtém o token CSRF atual da sessão. Gera um novo se não existir.
     *
     * @return string
     */
    public function getToken(): string
    {
        $this->startSession();
        if (empty($_SESSION[self::SESSION_KEY])) {
            return $this->generateToken();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Alias for getToken().
     *
     * @return string
     */
    public function token(): string
    {
        return $this->getToken();
    }

    /**
     * Valida o token fornecido contra o armazenado na sessão.
     *
     * @param string|null $token O token vindo da requisição.
     * @return bool
     */
    public function validate(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $sessionToken = $this->getToken();

        return hash_equals($sessionToken, $token);
    }

    /**
     * Gera o campo HTML <input> completo para ser usado em formulários.
     *
     * @return string
     */
    public function generateInput(): string
    {
        $token = $this->getToken();
        return '<input type="hidden" name="' . self::FORM_KEY . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Obtém o nome do campo do formulário.
     *
     * @return string
     */
    public static function getFormKey(): string
    {
        return self::FORM_KEY;
    }
}