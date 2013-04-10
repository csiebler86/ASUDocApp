<?php

namespace Sonata\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sonata\UserBundle\Entity\Address;
use Sonata\UserBundle\Form\AddressType;

/**
 * Address controller.
 *
 * @Route("/address")
 */
class AddressController extends Controller {

    /**
     * Creates a new Address entity.
     *
     * @Route("/create/{userID}, requirements={"userID" = "\d+"}, name="address_create")
     * @Method("POST")
     * @Template("SonataUserBundle:Address:new.html.twig")
     */
    public function createAction($userID, Request $request) {
        $address = new Address();
        $form = $this->createForm(new AddressType(), $address);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            // Add Address to User
            $user = $em->getRepository('SonataUserBundle:User')->find($userID);
            $user->setAddress($address);
            
            $em->persist($user);
            $em->persist($address);
            $em->flush();

            return $this->redirect($this->generateUrl('address_show', array('id' => $address->getId())));
        }

        return array(
            'entity' => $address,
            'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Address entity.
     *
     * @Route("/new/{userID}", requirements={"userID" = "\d+"}, name="address_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($userID) {
        $entity = new Address();
        $form = $this->createForm(new AddressType(), $entity);

        return array(
            'userID' => $userID,
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Address entity.
     *
     * @Route("/show/{userID}", requirements={"userID" = "\d+"}, name="address_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($userID) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataUserBundle:User')->find($userID)->getAddress();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Displays a form to edit an existing Address entity.
     *
     * @Route("/edit/{userID}", requirements={"userID" = "\d+"}, name="address_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($userID) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataUserBundle:User')->find($userID)->getAddress();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createForm(new AddressType(), $entity);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing Address entity.
     *
     * @Route("/update/{id}", requirments={"id" = "\d+"}, name="address_update")
     * @Method("PUT")
     * @Template("SonataUserBundle:Address:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataUserBundle:Address')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createForm(new AddressType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('address_edit', array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }
}
