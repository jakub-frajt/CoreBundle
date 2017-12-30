<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 21.06.17
 * Time: 09:15
 */

namespace UnitedCMS\CoreBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\User;

class DomainVoter extends Voter
{
    const LIST = 'list domain';
    const CREATE = 'create domain';
    const VIEW = 'view domain';
    const UPDATE = 'update domain';
    const DELETE = 'delete domain';

    const BUNDLE_PERMISSIONS = [self::LIST, self::CREATE];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE, self::DELETE];

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (in_array($attribute, self::BUNDLE_PERMISSIONS)) {
            return (is_string($subject) && $subject === Domain::class);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Domain);
        }

        return false;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // This voter can decide on a Domain subject for APIClients.
        if ($token->getUser() instanceof ApiClient && ($subject instanceof Domain)) {
            return $this->checkPermission($attribute, $token->getRoles());
        }

        // This voter can decide all permissions for united users.
        if (!$token->getUser() instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        // Platform admins are allowed to preform all actions.
        if (in_array(User::ROLE_PLATFORM_ADMIN, $token->getUser()->getRoles())) {
            return self::ACCESS_GRANTED;
        }

        // All domain admins are allowed to preform all actions on their domains.
        foreach ($token->getUser()->getOrganizations() as $organizationMember) {
            if (in_array(Organization::ROLE_ADMINISTRATOR, $organizationMember->getRoles())) {

                if ((is_string($subject) && $subject === Domain::class) && in_array(
                        $attribute,
                        self::BUNDLE_PERMISSIONS
                    )) {
                    return self::ACCESS_GRANTED;
                }

                if (($subject instanceof Domain) && in_array($attribute, self::ENTITY_PERMISSIONS)) {
                    if ($subject->getOrganization() === $organizationMember->getOrganization()) {
                        return self::ACCESS_GRANTED;
                    }
                }
            }
        }

        // All Domain members and admins are allowed to list domains.
        if ($attribute === self::LIST) {
            foreach ($token->getUser()->getOrganizations() as $organizationMember) {
                if (in_array(Organization::ROLE_USER, $organizationMember->getRoles())) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        if ($subject instanceof Domain) {

            // Check domain member access.
            foreach ($token->getUser()->getDomains() as $domainMember) {
                if ($domainMember->getDomain() === $subject) {
                    return $this->checkPermission($attribute, $domainMember->getRoles());
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * Check if the user has access to the domain.
     *
     * @param $attribute
     * @param array $roles
     * @return bool
     */
    private function checkPermission($attribute, array $roles)
    {

        // Admins can view and update an domain
        if (in_array(Domain::ROLE_ADMINISTRATOR, $roles)) {
            return in_array($attribute, [self::VIEW, self::UPDATE]);
        }

        // Users can only view an domain
        if (in_array(Domain::ROLE_EDITOR, $roles)) {
            return $attribute === self::VIEW;
        }

        return self::ACCESS_DENIED;
    }
}