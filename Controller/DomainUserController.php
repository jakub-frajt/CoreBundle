<?php

namespace UnitedCMS\CoreBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\DomainInvitation;
use UnitedCMS\CoreBundle\Entity\User;

class DomainUserController extends Controller
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @return Response
     */
    public function indexAction(Organization $organization, Domain $domain)
    {
        $users = $this->get('knp_paginator')->paginate($domain->getUsers());
        $invites = $this->get('knp_paginator')->paginate($domain->getInvites());

        return $this->render(
            'UnitedCMSCoreBundle:Domain/User:index.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'users' => $users,
                'invites' => $invites,
            ]
        );
    }

    /**
     * @Route("/create")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @return Response
     */
    public function createAction(Organization $organization, Domain $domain, Request $request)
    {
        $member = new DomainMember();
        $member->setDomain($domain);

        $formCreate = $this->get('form.factory')->createNamedBuilder('create_domain_user', FormType::class, $member)
            ->add(
                'user',
                EntityType::class,
                [
                    'label' => 'User',
                    'class' => User::class,
                    'query_builder' => function (EntityRepository $er) use ($organization, $domain) {

                        // Collect all users, that are already in this domain.
                        $domain_users = [0];
                        foreach ($domain->getUsers() as $domainMember) {
                            $domain_users[] = $domainMember->getUser()->getId();
                        }

                        return $er->createQueryBuilder('u')
                            ->leftJoin('u.organizations', 'o')
                            ->where('o.organization = :organization')
                            ->andWhere('u.id NOT IN (:domain_users)')
                            ->setParameters(
                                [
                                    'organization' => $organization,
                                    'domain_users' => $domain_users,
                                ]
                            );
                    },
                ]
            )
            ->add(
                'roles',
                ChoiceType::class,
                ['label' => 'Roles', 'multiple' => true, 'choices' => $domain->getAvailableRolesAsOptions()]
            )
            ->add('submit', SubmitType::class, ['label' => 'Create'])
            ->getForm();

        $formCreate->handleRequest($request);

        if ($formCreate->isSubmitted() && $formCreate->isValid()) {
            $this->getDoctrine()->getManager()->persist($member);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        $invitation = new DomainInvitation();
        $invitation->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
        $invitation->setRequestedAt(new \DateTime());
        $invitation->setDomain($domain);

        $formInvite = $this->get('form.factory')->createNamedBuilder('invite_domain_user', FormType::class, $invitation)
            ->add('email', EmailType::class, ['label' => 'Email',])
            ->add(
                'roles',
                ChoiceType::class,
                ['label' => 'Roles', 'multiple' => true, 'choices' => $domain->getAvailableRolesAsOptions()]
            )
            ->add('submit', SubmitType::class, ['label' => 'Invite'])
            ->getForm();

        $formInvite->handleRequest($request);

        if ($formInvite->isSubmitted() && $formInvite->isValid()) {
            $this->getDoctrine()->getManager()->persist($invitation);
            $this->getDoctrine()->getManager()->flush();

            // Send out email using the default mailer.
            $message = (new \Swift_Message('Invitation'))
                ->setFrom($this->getParameter('mailer_sender'))
                ->setTo($invitation->getEmail())
                ->setBody(
                    $this->renderView(
                        '@UnitedCMSCore/Emails/invitation.html.twig',
                        [
                            'invitation' => $invitation,
                            'invitation_url' => $this->generateUrl(
                                'unitedcms_core_profile_acceptinvitation',
                                [
                                    'token' => $invitation->getToken(),
                                ],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                        ]
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            return $this->redirectToRoute(
                'unitedcms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/User:create.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form_create' => $formCreate->createView(),
                'form_invite' => $formInvite->createView(),
            ]
        );
    }

    /**
     * @Route("/update/{member}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param \UnitedCMS\CoreBundle\Entity\DomainMember $member
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, Domain $domain, DomainMember $member, Request $request)
    {
        $form = $this->createFormBuilder($member)
            ->add(
                'roles',
                ChoiceType::class,
                ['label' => 'Roles', 'multiple' => true, 'choices' => $domain->getAvailableRolesAsOptions()]
            )
            ->add('submit', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/User:update.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/delete/{member}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param \UnitedCMS\CoreBundle\Entity\DomainMember $member
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, Domain $domain, DomainMember $member, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Remove'])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($member);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/User:delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/delete-invite/{invite}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("invite")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainInvitation $invite
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteInviteAction(
        Organization $organization,
        Domain $domain,
        DomainInvitation $invite,
        Request $request
    ) {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Remove'])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($invite);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/User:delete_invite.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'invite' => $invite,
            ]
        );
    }
}
