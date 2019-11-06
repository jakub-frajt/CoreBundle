<?php


namespace UniteCMS\CoreBundle\Field\Types;

use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedContent;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedFieldData;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;

class EmbeddedType extends AbstractFieldType
{
    const TYPE = 'embedded';

    /**
     * @var DomainManager
     */
    protected $domainManager;

    /**
     * @var FieldDataMapper $fieldDataMapper
     */
    protected $fieldDataMapper;

    /**
     * @var LoggerInterface $uniteCMSDomainLogger
     */
    protected $domainLogger;

    public function __construct(DomainManager $domainManager, FieldDataMapper $fieldDataMapper, LoggerInterface $uniteCMSDomainLogger)
    {
        $this->domainManager = $domainManager;
        $this->fieldDataMapper = $fieldDataMapper;
        $this->domainLogger = $uniteCMSDomainLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        // Validate return type.
        $returnTypes = empty($field->getUnionTypes()) ? [$field->getReturnType()] : array_keys($field->getUnionTypes());
        foreach($returnTypes as $returnType) {
            if(!$this->domainManager->current()->getContentTypeManager()->getEmbeddedContentType($returnType)) {
                $context
                    ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use a GraphQL type (or an union of types) that implements UniteEmbeddedContent.')
                    ->setParameter('{{ type }}', static::getType())
                    ->setParameter('{{ return_type }}', $field->getReturnType())
                    ->addViolation();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return sprintf('%sInput', $field->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return new EmbeddedContent($fieldData->getId(), $fieldData->getType(), $fieldData->getData());
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldData(ContentInterface $content, ContentTypeField $field, ContextualValidatorInterface $validator, ExecutionContextInterface $context, FieldData $fieldData = null) : void {
        parent::validateFieldData($content, $field, $validator, $context, $fieldData);

        if($validator->getViolations()->count() > 0) {
            return;
        }

        // Allow all embedded field types to validate their content.
        if(!empty($fieldData)) {

            $rows = $fieldData instanceof FieldDataList ? $fieldData->rows() : [$fieldData];
            $embeddedContent = [];

            foreach($rows as $delta => $row) {

                // Only validate embedded fields if field data is not empty.
                // This allows to set embedded sub-fields required but not the
                // embedded field self.
                if($row->empty()) {
                    continue;
                }

                $embeddedContent[] = $this->resolveRowData($content, $field, $row);
            }

            $validator->validate($embeddedContent, null, [$context->getGroup()]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null) : FieldData {

        $domain = $this->domainManager->current();

        // If this is not a known embedded type.
        if(!$contentType = $domain->getContentTypeManager()->getEmbeddedContentType($field->getReturnType())) {
            $this->domainLogger->warning(sprintf('Unknown embedded content type "%s" was used as return type of field "%s".', $field->getReturnType(), $field->getId()));
            return null;
        }

        // Create embedded field data if not already set.
        $fieldData = $content->getFieldData($field->getId()) ?? new EmbeddedFieldData(uniqid(), $contentType->getId());

        // If we have no input data and the field can be null, just return the empty field.
        if(empty($inputData) && !$field->isNonNull()) {
            return $fieldData;
        }

        $tmpEmbeddedContent = $this->resolveRowData($content, $field, $fieldData);

        // Create new embedded content with input data.
        return new EmbeddedFieldData(
            $fieldData->getId(),
            $fieldData->getType(),
            $this->fieldDataMapper->mapToFieldData($domain, $tmpEmbeddedContent, $inputData, $contentType)
        );
    }
}
