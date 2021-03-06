<?php

namespace Sonata\HealthBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sonata\HealthBundle\Entity\Allergy;
use Sonata\HealthBundle\Form\AllergyType;

/**
 * Allergy controller.
 *
 * @Route("/allergy")
 */
class AllergyController extends Controller {

    /**
     * Creates a new Allergy entity.
     *
     * @Route("/{userID}/{userName}", requirements={"userID" = "\d+"}, defaults={"userName" = null}, name="allergy_create")
     * @Method("POST")
     * @Template("SonataHealthBundle:Allergy:new.html.twig")
     */
    public function createAction(Request $request, $userID, $userName) {
        $allergy = new Allergy();
        $form = $this->createForm(new AllergyType(), $allergy);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            // Add Allergy to User
            $user = $em->getRepository('SonataUserBundle:User')->find($userID);
            $user->getAllergies()->add($allergy);
            $allergy->setPatient($user);
            
            $em->persist($allergy);
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('allergy_show', array('userID' => $userID, 'userName' => $userName, 'id' => $allergy->getId())));
        }

        return array(
            'userName' => $userName,
            'userID' => $userID,
            'entity' => $allergy,
            'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Allergy entity.
     *
     * @Route("/new/{userID}/{userName}", requirements={"userID" = "\d+"}, defaults={"userName" = null}, name="allergy_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($userID, $userName) {
        $entity = new Allergy();
        $form = $this->createForm(new AllergyType(), $entity);

        return array(
            'userName' => $userName,
            'userID' => $userID,
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Allergy entity.
     *
     * @Route("/show/{userID}/{userName}", requirements={"userID" = "\d+"}, defaults={"userName" = null}, name="allergy_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($userID, $userName) {
        $em = $this->getDoctrine()->getManager();

        $allergies = $em->getRepository('SonataHealthBundle:Allergy')->findAll();

        if (!$allergies) {
            throw $this->createNotFoundException('Unable to find Allergies.');
        }

        foreach ($allergies as $allergy) {
            $deleteForms[] = $this->createDeleteForm($allergy->getId());
        }

        return array(
            'userID' => $userID,
            'userName' => $userName,
            'allergies' => $allergies,
            'delete_forms' => $deleteForms,
        );
    }

    /**
     * Displays a form to edit an existing Allergy entity.
     *
     * @Route("/edit/{id}/{userID}/{userName}", requirements={"userID" = "\d+", "id" = "\d+"}, defaults={"userName" = null}, name="allergy_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id, $userID, $userName) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataHealthBundle:Allergy')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Allergy entity.');
        }

        $editForm = $this->createForm(new AllergyType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'userID' => $userID,
            'userName' => $userName,
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Allergy entity.
     *
     * @Route("/update/{id}/{userID}/{userName}", requirements={"userID" = "\d+", "id" = "\d+"}, defaults={"userName" = null}, name="allergy_update")
     * @Method("PUT")
     * @Template("SonataHealthBundle:Allergy:edit.html.twig")
     */
    public function updateAction(Request $request, $id, $userID, $userName) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataHealthBundle:Allergy')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Allergy entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new AllergyType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('allergy_edit', array('id' => $id, 'userID' => $userID, 'userName' => $userName)));
        }

        return array(
            'userID' => $userID,
            'userName' => $userName,
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Allergy entity.
     *
     * @Route("/{id}", name="allergy_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SonataHealthBundle:Allergy')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Allergy entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        // Grab the currently Logged In User to determine where to send
        $currentUser = $this->get('security.context')->getToken()->getUser();
        
        if ($currentUser->hasRoleByName('ROLE_PATIENT')) {
            // If the User is logged in
            $url = $this->container->get('router')->generate('user_splash');
        } elseif ($currentUser->hasRoleByName('ROLE_USER')) {
            $url = $this->container->get('router')->generate('user_show', $entity->getPatient()->getId());
        } else {
            // If the User cannot the determined then return to homepage
            $url = $this->container->get('router')->generate('homepage');
        }

        return new RedirectResponse($url);
    }

    /**
     * Creates a form to delete a Allergy entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm();
    }

}