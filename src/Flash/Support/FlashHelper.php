<?php

namespace Ludelix\Flash\Support;

use Ludelix\Bridge\Bridge;

/**
 * FlashHelper - Funções auxiliares para uso de flash messages em templates
 * 
 * Este helper fornece funções convenientes para exibir mensagens flash em templates.
 * 
 * @package Ludelix\Flash\Support
 */
class FlashHelper
{
    /**
     * Renderiza todas as mensagens flash como HTML
     *
     * @param array $options Opções de personalização
     * @return string HTML das mensagens
     */
    public static function render(array $options = []): string
    {
        $html = '';
        $defaultClasses = [
            'info' => 'alert alert-info',
            'success' => 'alert alert-success',
            'warning' => 'alert alert-warning',
            'error' => 'alert alert-danger'
        ];
        
        $classes = $options['classes'] ?? $defaultClasses;
        $dismissable = $options['dismissable'] ?? true;
        $wrapper = $options['wrapper'] ?? 'div';
        
        foreach (Bridge::flash()->all() as $type => $messages) {
            foreach ($messages as $message) {
                $class = $classes[$type] ?? 'alert alert-' . $type;
                
                if ($dismissable) {
                    $html .= "<{$wrapper} class=\"{$class} alert-dismissible fade show\" role=\"alert\">";
                    $html .= htmlspecialchars($message);
                    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    $html .= "</{$wrapper}>";
                } else {
                    $html .= "<{$wrapper} class=\"{$class}\" role=\"alert\">";
                    $html .= htmlspecialchars($message);
                    $html .= "</{$wrapper}>";
                }
            }
        }
        
        return $html;
    }
    
    /**
     * Verifica se existem mensagens de um tipo específico
     *
     * @param string $type Tipo da mensagem (info, success, warning, error)
     * @return bool
     */
    public static function has(string $type): bool
    {
        return Bridge::flash()->has($type);
    }
    
    /**
     * Obtém mensagens de um tipo específico
     *
     * @param string $type Tipo da mensagem
     * @return array
     */
    public static function get(string $type): array
    {
        return Bridge::flash()->get($type);
    }
    
    /**
     * Verifica se existem mensagens de qualquer tipo
     *
     * @return bool
     */
    public static function any(): bool
    {
        return Bridge::flash()->any();
    }
    
    /**
     * Renderiza mensagens de um tipo específico
     *
     * @param string $type Tipo da mensagem
     * @param array $options Opções de personalização
     * @return string
     */
    public static function renderType(string $type, array $options = []): string
    {
        if (!self::has($type)) {
            return '';
        }
        
        $html = '';
        $defaultClass = 'alert alert-' . $type;
        $class = $options['class'] ?? $defaultClass;
        $dismissable = $options['dismissable'] ?? true;
        $wrapper = $options['wrapper'] ?? 'div';
        
        foreach (self::get($type) as $message) {
            if ($dismissable) {
                $html .= "<{$wrapper} class=\"{$class} alert-dismissible fade show\" role=\"alert\">";
                $html .= htmlspecialchars($message);
                $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                $html .= "</{$wrapper}>";
            } else {
                $html .= "<{$wrapper} class=\"{$class}\" role=\"alert\">";
                $html .= htmlspecialchars($message);
                $html .= "</{$wrapper}>";
            }
        }
        
        return $html;
    }
}