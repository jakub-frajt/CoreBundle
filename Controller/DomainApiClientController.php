<?php

namespace UnitedCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;

class DomainApiClientController extends Controller
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
        $clients = $this->get('knp_paginator')->paginate($domain->getApiClients());

        return $this->render(
            'UnitedCMSCoreBundle:Domain/ApiClient:index.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'clients' => $clients,
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
        $apiClient = new ApiClient();
        $apiClient->setDomain($domain);
        $apiClient->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));

        $form = $this->createFormBuilder($apiClient)
            ->add(
                'name',
                TextType::class,
                ['label' => 'Name', 'required' => true]
            )
            ->add(
                'roles',
                ChoiceType::class,
                ['label' => 'Roles', 'multiple' => true, 'choices' => $domain->getAvailableRolesAsOptions(true)]
            )
            ->add('submit', SubmitType::class, ['label' => 'Create'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($apiClient);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainapiclient_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/ApiClient:create.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/update/{client}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("client")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param ApiClient $client
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, Domain $domain, ApiClient $client, Request $request)
    {
        $form = $this->createFormBuilder($client)
            ->add(
                'name',
                TextType::class,
                ['label' => 'Name', 'required' => true]
            )
            ->add(
                'roles',
                ChoiceType::class,
                ['label' => 'Roles', 'multiple' => true, 'choices' => $domain->getAvailableRolesAsOptions(true)]
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
            'UnitedCMSCoreBundle:Domain/ApiClient:update.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }

    /**
     * @Route("/delete/{client}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param ApiClient $client
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, Domain $domain, ApiClient $client, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Remove'])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($client);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_domainapiclient_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Domain/ApiClient:delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }
}
