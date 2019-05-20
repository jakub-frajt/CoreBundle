<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 30.04.18
 * Time: 15:11
 */

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="accessor_type", type="string")
 * @ORM\DiscriminatorMap({"user" = "User", "api_key" = "ApiKey"})
 */
abstract class DomainAccessor
{
    /**
     * @var int
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var DomainMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMember", mappedBy="accessor", cascade={"persist", "remove", "merge"})
     */
    protected $domains;

    /**
     * Returns the unique type of this accessor.
     *
     * @return string
     */
    abstract static function getType() : string;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
    }

    public function __toString()
    {
        return ''.$this->getName();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return DomainAccessor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return DomainMember[]|ArrayCollection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param DomainMember[] $dMembers
     *
     * @return DomainAccessor
     */
    public function setDomains($dMembers)
    {
        $this->domains->clear();
        foreach ($dMembers as $dMember) {
            $this->addDomain($dMember);
        }

        return $this;
    }

    /**
     * @param DomainMember $dMember
     *
     * @return DomainAccessor
     */
    public function addDomain(DomainMember $dMember)
    {
        if (!$this->domains->contains($dMember)) {
            $this->domains->add($dMember);
            $dMember->setAccessor($this);
        }

        return $this;
    }

    /**
     * Returns the roles of the user for a given domain.
     *
     * @param Domain $domain
     * @return Role[]|string[] The user roles for the domain
     */
    public function getDomainRoles(Domain $domain)
    {
        foreach ($this->getDomains() as $domainMember) {
            if (!empty($domain->getId()) && $domainMember->getDomain()->getId() === $domain->getId()) {
                return $domainMember->getRoles();
            }
        }

        return [];
    }

    /**
     * Returns all matching domain memberships of this user for the given domain or null if this user is not member of the domain.
     *
     * @param Domain $domain
     * @return DomainMember[]
     */
    public function getDomainMembers(Domain $domain): array
    {
        $domainMembers = [];
        foreach ($this->getDomains() as $domainMember) {
            if (!empty($domain->getId()) && $domainMember->getDomain()->getId() === $domain->getId()) {
                $domainMembers[] = $domainMember;
            }
        }

        return $domainMembers;
    }

    /**
     * Returns all organizations, this accessor has access to.
     *
     * @return Organization[]
     */
    public abstract function getAccessibleOrganizations() : array;
}