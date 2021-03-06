<?php

namespace Sonata\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\UserEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sonata\UserBundle\Form\UserType;

/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends Controller {

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", requirements={"id" = "\d+"}, name="user_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('SonataUserBundle:User')->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }
        
        if ($user->hasRoleByName('ROLE_PATIENT')) {
            $appointments = $user->getAppointments();
            
            foreach ($appointments as $appointment) {
                if (true === $appointment->getInOffice()) {
                    $lastAppointment = $appointment;
                } else {
                    continue;
                }
            }
            
            if (isset($lastAppointment)) {
                return array(
                    'entity' => $user,
                    'appointment' => $lastAppointment,
                );
            } else {
                return array(
                    'entity' => $user,
                    'appointment' => null,
                );
            }
        }

        return array(
            'entity' => $user,
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/edit/{id}", requirements={"id" = "\d+"}, name="user_edit")
     * @Method("GET")
     * @Template("SonataUserBundle:User:edit.html.twig")
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataUserBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(new UserType(), $entity);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/update/{id}", requirements={"id" = "\d+"}, name="user_update")
     * @Method("PUT")
     * @Template("SonataUserBundle:User:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SonataUserBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(new UserType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('user_edit', array('id' => $id)));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }
    
    /**
     * @Route("/add/PrimaryDoctor/{userName}/{userID}", requirements={"userID" = "\d+"}, defaults={"userName" = null, "userID" = 0}, name="new_primary_doctor")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function newPrimaryDoctorAction($userName, $userID) {
        $request = $this->getRequest();
        
        if ('POST' === $request->getMethod()) {
            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository('SonataUserBundle:User')->findOneById($userID);

            if (!$user) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }
            
            $primaryDoctor = $request->request->get('name');

            // If Primary Doctor is set then sanitize input and persist to database
            // Need to sanitize since Symfony is not processing the data.
            if (isset($primaryDoctor) && is_string($primaryDoctor)) {
                $primaryDoctor = filter_var($primaryDoctor, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                
                if (false !== $primaryDoctor) {
                    $user->setPrimaryDoctor($primaryDoctor);

                    $em->persist($user);
                    $em->flush();
                } else {
                    // User entered invalid data, return to form submission
                    return array(
                        'userName' => $userName,
                        'userID' => $userID,
                    );
                }
                
                // Redirect to User splash
                $url = $this->container->get('router')->generate('user_splash');

                return new RedirectResponse($url);
            }
        }
        
        return array(
            'userName' => $userName,
            'userID' => $userID,
        );
    }
    
    /**
     * @Route("/edit/PrimaryDoctor/{primaryDoctor}/{userName}/{userID}",requirements={"userID" = "\d+"}, defaults={"primaryDoctor" = null, "userName" = null, "userID" = 0}, name="edit_primary_doctor")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editPrimaryDoctorAction($primaryDoctor, $userName, $userID) {
        $request = $this->getRequest();
        
        if ('POST' === $request->getMethod()) {
            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository('SonataUserBundle:User')->findOneById($userID);

            if (!$user) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }
            
            $primaryDoctor = $request->request->get('name');

            // If Primary Doctor is set then sanitize input and persist to database
            // Need to sanitize since Symfony is not processing the data.
            if (isset($primaryDoctor) && is_string($primaryDoctor)) {
                $primaryDoctor = filter_var($primaryDoctor, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                
                if (false !== $primaryDoctor) {
                    $user->setPrimaryDoctor($primaryDoctor);

                    $em->persist($user);
                    $em->flush();
                } else {
                    // User entered invalid data, return to form submission
                    return array(
                        'userName' => $userName,
                        'userID' => $userID,
                    );
                }
                
                // Redirect to User splash
                $url = $this->container->get('router')->generate('user_splash');
            
                return new RedirectResponse($url);
            }
        }
        
        return array(
            'primaryDoctor' => $primaryDoctor,
            'userName' => $userName,
            'userID' => $userID,
        );
    }

    /**
     * @Route("/splash", name="user_splash")
     * @Method({"GET"})
     * @Template("SonataUserBundle:User:splash.html.twig")
     */
    public function splashAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        
        if ($user->hasRoleByName('ROLE_ADMIN') || $user->hasRoleByName('ROLE_NURSE') || $user->hasRoleByName('ROLE_DOCTOR')) {
            $em = $this->getDoctrine()->getManager();
            
            $patientRole = $em->getRepository('SonataUserBundle:Role')->findOneByName('ROLE_PATIENT');
            
            $patients = $em->getRepository('SonataUserBundle:User')->getAllByRole($patientRole);
            
            return array(
                'patients' => $patients,
                'user' => $user,
            );
        }
        
        return array(
            'user' => $user,
        );
    }

    /**
     * @Route("/register", name="user_registration")
     * @Method({"GET", "POST"})
     */
    public function registerAction(Request $request) {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->container->get('fos_user.registration.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');
        
        $user = $userManager->createUser();
        $user->setEnabled(true);
        
        // Generate Temporary Password
        // Save to memory in order to email to user
        $tempPassword = substr(md5(microtime().rand()),0,10);
        $user->setPlainPassword($tempPassword); // Autogenerate Password

        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

        $form = $formFactory->createForm();
        $form->setData($user);

        if ('POST' === $request->getMethod()) {
            $form->bind($request);


            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                $userManager->updateUser($user);
                
                // Specify redirect URL to go to after User is registered
                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }
                
                $subject = 'Notification: User Registered';
                
                // Send Email Notification to User
                $email = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($this->container->getParameter('mailer_user'))
                        ->setReplyTo($this->container->getParameter('mailer_replyTo'))
                        ->setTo($user->getEmail())
                        ->setBody($this->renderView('SonataUserBundle:Email:registered.txt.twig', array('user' => $user, 'password' => $tempPassword)));
                
                $result = $this->get('mailer')->send($email);
                
                if (false === $result) {
                    return $this->redirect($this->generateUrl('email_error', array(
                            'subject' => $subject,
                            'from' => $this->container->getParameter('mailer_user'),
                            'replyTo' => $this->container->getParameter('mailer_replyTo'),
                            'to' => $user->getEmail(),
                            'body' => $this->renderView('SonataUserBundle:Email:registered.txt.twig', array(
                                'user' => $user,
                                'password' => $tempPassword
                            ))
                    )));
                }

                // Create Flash Message for User registration success
                $message = $this->get('translator')->trans('registration.flash.user_created', array(), 'FOSUserBundle');
                $this->get('session')->getFlashBag()->add('success', $message);

                return $response;
            }
        }

        return $this->container->get('templating')->renderResponse('SonataUserBundle:User:register.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/confirmed", name="user_registration_confirmed")
     * @Method({"GET", "POST"})
     */
    public function confirmedAction() {
        $user = $this->container->get('security.context')->getToken()->getUser();

        return $this->container->get('templating')->renderResponse('SonataUserBundle:User:confirmed.html.twig', array(
                    'user' => $user,
        ));
    }
}
