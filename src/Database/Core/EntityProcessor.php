<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Attributes\AutoTrim;
use Ludelix\Database\Attributes\AutoSlug;
use Ludelix\Database\Attributes\AutoUuid;
use Ludelix\Database\Metadata\EntityMetadata;
use Ludelix\Core\Support\Str;
use Ludelix\Core\Support\Uuid;
use ReflectionClass;

class EntityProcessor
{
    protected Str $str;
    
    public function __construct()
    {
        $this->str = new Str();
    }
    
    public function processEntity(object $entity, EntityMetadata $metadata): void
    {
        $reflection = new ReflectionClass($entity);
        
        foreach ($reflection->getProperties() as $property) {
            $this->processAutoUuid($entity, $property);
            $this->processAutoTrim($entity, $property);
            $this->processAutoSlug($entity, $property, $reflection);
        }
    }
    
    protected function processAutoUuid(object $entity, \ReflectionProperty $property): void
    {
        $autoUuidAttrs = $property->getAttributes(AutoUuid::class);
        if (empty($autoUuidAttrs)) return;
        
        $property->setAccessible(true);
        if (empty($property->getValue($entity))) {
            $property->setValue($entity, Uuid::generate());
        }
    }
    
    protected function processAutoTrim(object $entity, \ReflectionProperty $property): void
    {
        $autoTrimAttrs = $property->getAttributes(AutoTrim::class);
        if (empty($autoTrimAttrs)) return;
        
        $property->setAccessible(true);
        $value = $property->getValue($entity);
        if (is_string($value)) {
            $property->setValue($entity, trim($value));
        }
    }
    
    protected function processAutoSlug(object $entity, \ReflectionProperty $property, ReflectionClass $reflection): void
    {
        $autoSlugAttrs = $property->getAttributes(AutoSlug::class);
        if (empty($autoSlugAttrs)) return;
        
        $autoSlug = $autoSlugAttrs[0]->newInstance();
        $sourceProperty = $reflection->getProperty($autoSlug->from);
        $sourceProperty->setAccessible(true);
        $property->setAccessible(true);
        
        $sourceValue = $sourceProperty->getValue($entity);
        if (!empty($sourceValue) && empty($property->getValue($entity))) {
            $property->setValue($entity, $this->str->slug($sourceValue));
        }
    }
}