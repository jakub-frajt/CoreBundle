<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 12:06
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use UniteCMS\CoreBundle\Expression\WebhookExpressionChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class ValidWebhookConditionValidator extends ConstraintValidator
{
    /**
     * @var WebhookExpressionChecker $webhookExpressionChecker
     */
    private $webhookExpressionChecker;

    public function __construct()
    {
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
    }

    public function validate($value, Constraint $constraint)
    {
        if(empty($value)) {
            return;
        }

        if (!$this->webhookExpressionChecker->validate($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}