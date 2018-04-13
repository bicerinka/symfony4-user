<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use App\Service\TokenGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

/**
 * @Route("/facebook", name="facebook_")
 */
class FacebookController extends Controller
{
    /**
     * @Route("/connect", name="connect")
     */
    public function connect()
    {
        // will redirect to Facebook!
        return $this->get('oauth2.registry')
            ->getClient('facebook_main') // key used in config.yml
            ->redirect();
    }

    /**
     * @Route("/check", name="check")
     */
    public function check(Request $request, \KnpU\OAuth2ClientBundle\Client\ClientRegistry $clientRegistry, TokenGenerator $tokenGenerator)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        // записать данные в базу, затем авторизовать пользователя
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient $client */
        $query = $request->query->get('code', '');

        $client = $clientRegistry->getClient('facebook_main');

        try {
            // the exact class depends on which provider you're using
            /** @var \League\OAuth2\Client\Provider\FacebookUser $user */
            $user = $client->fetchUser();

            // do something with all this new power!
            $firstname = $user->getFirstName();
            $lastname = $user->getLastName();
            $facebook = $user->getId();
            $email = $user->getEmail();


            $repository = $this->getDoctrine()->getRepository(User::class);

            $newuser = $repository->findOneBy(['facebook' => $facebook]);

            $entityManager = $this->getDoctrine()->getManager();
            if($newuser){

                $newuser->setEmail($email);
                $newuser->setName($firstname);
                $newuser->setLastname($lastname);
                $newuser->setActivatedAt(new \DateTime());
                $token = $tokenGenerator->generateToken();
                $newuser->setToken($token);
                $entityManager->flush();
            }else{
                $newuser = new User();
                $newuser->setEmail($email);
                $newuser->setName($firstname);
                $newuser->setLastname($lastname);
                $newuser->setFacebook($facebook);
                $newuser->setPassword($facebook);
                $newuser->setIsActive(1);
                $newuser->setActivatedAt(new \DateTime());
                $token = $tokenGenerator->generateToken();
                $newuser->setToken($token);

                // tell Doctrine you want to (eventually) save the Product (no queries yet)
                $entityManager->persist($newuser);

                // actually executes the queries (i.e. the INSERT query)
                $entityManager->flush();
            }

            // automatic login
            return $this->redirect($this->generateUrl('facebook_activate', ['token' => $token]));

        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
        }
    }

    /**
     * @Route("/activate/{token}", name="activate")
     * @param $request Request
     * @param $user User
     * @param GuardAuthenticatorHandler $authenticatorHandler
     * @param LoginFormAuthenticator $loginFormAuthenticator
     * @return Response
     */
    public function activate(Request $request, User $user, GuardAuthenticatorHandler $authenticatorHandler, LoginFormAuthenticator $loginFormAuthenticator)
    {
        $user->setIsActive(true);
        $user->setToken(null);
        $user->setActivatedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'user.welcome');

        // automatic login
        return $authenticatorHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $loginFormAuthenticator,
            'main'
        );
    }

}