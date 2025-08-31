<?php

namespace Ludelix\Database\Core;

/**
 * Trait para ocultar campos sensíveis em entidades (ex: password, tokens).
 * Basta usar na entidade e definir a propriedade $hidden.
 */
trait HiddenFieldsTrait
{
    /**
     * Lista de campos a serem ocultados ao serializar.
     * @var array
     */
    protected array $hidden = [];

    /**
     * Retorna os campos ocultos.
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * Serializa a entidade ocultando os campos definidos em $hidden.
     * @return array
     */
    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        foreach ($this->getHidden() as $field) {
            unset($vars[$field]);
        }
        return $vars;
    }
} 