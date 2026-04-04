<?php

declare(strict_types=1);

namespace Oryx\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class XmlThenAttributeDriver implements MappingDriver
{
    private SimplifiedXmlDriver $xmlDriver;
    private AttributeDriver $attributeDriver;
    private string $schemaPath;

    public function __construct(string $xmlPath, string $entityPath)
    {
        $this->schemaPath = $xmlPath;
        $this->xmlDriver = new SimplifiedXmlDriver([
            $xmlPath => 'App\\Entity',
        ], '.orm.xml');

        $this->attributeDriver = new AttributeDriver([$entityPath]);
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
        if ($this->hasXmlMapping($className)) {
            $this->xmlDriver->loadMetadataForClass($className, $metadata);
        } else {
            $this->attributeDriver->loadMetadataForClass($className, $metadata);
        }
    }

    public function getAllClassNames(): array
    {
        $classes = array_unique(array_merge(
            $this->xmlDriver->getAllClassNames(),
            $this->attributeDriver->getAllClassNames()
        ));
        sort($classes);
        return $classes;
    }

    public function isTransient($className): bool
    {
        return $this->xmlDriver->isTransient($className)
            && $this->attributeDriver->isTransient($className);
    }

    private function hasXmlMapping(string $className): bool
    {
        $reflection = new \ReflectionClass($className);
        $shortName = $reflection->getShortName();
        $xmlFile = rtrim($this->schemaPath, '/') . '/' . $shortName . '.orm.xml';
        return file_exists($xmlFile);
    }
}
