<?php

namespace UnitedCMS\CoreBundle\SchemaType\Factories;

use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface SchemaTypeFactoryInterface {

    /**
     * Returns true, if this factory can create a schema for the given name.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName) : bool;

    /**
     * Returns the new created schema type object for the given name.
     * @param SchemaTypeManager $schemaTypeManager
     * @param Domain $domain
     * @param string $schemaTypeName
     * @return Type
     */
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, Domain $domain, string $schemaTypeName) : Type;
}