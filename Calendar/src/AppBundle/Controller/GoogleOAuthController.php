<?php
/**
 * Created by PhpStorm.
 * User: therveux
 * Date: 06/06/17
 * Time: 13:25
 */

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;

class GoogleOAuthController extends Controller
{

    private $accessScope = [\Google_Service_Calendar::CALENDAR];

    /**
     * @Route("/oauth/google/auth", name = "auth")
     * @Method("GET")
     */
    public function getAuthenticationCodeAction()
    {
        $client = $this->container->get('happyr.google.api.client');
        $client->getGoogleClient()->setScopes($this->accessScope);
        $client->getGoogleClient()->setAccessType('offline');
        return $this->redirect($client->createAuthUrl());
    }

    /**
     * @Route("/oauth/google/redirect", name = "redirect")
     */
    public function getAccessCodeRedirectAction(Request $request)
    {
        try
        {
            if ($request->query->get('code')) {
                $code = $request->query->get('code');
                $client = $this->container->get('happyr.google.api.client');
                $client->getGoogleClient()->setScopes($this->accessScope);
                $client->authenticate($code);
                $accessToken = $client->getGoogleClient()->getAccessToken();

                $session = new Session();
                $session->set("token", $accessToken);

                return $this->render('AppBundle:welcome:index.html.twig', [
                    'token' => $accessToken,
                    'success' => true
                ]);

            } else {
                $error = $request->query->get('error');
                return $this->render('AppBundle:welcome:index.html.twig', [
                    'error' => $error,
                    'success' => false
                ]);
            }
        } catch (Exception $ex) {
            return $this->render('AppBundle:welcome:index.html.twig', [
                    'error' => "Exception raised",
                    'success' => false
            ]);
        }

    }

    /**
     * @Route("/add_event", name = "add_event")
     * @Method("POST")
     */
    public function createEvenAction(Request $request)
    {
        $response = new Response();
        try {
            $client = $this->container->get('happyr.google.api.client');
            $client->getGoogleClient()->setScopes($this->accessScope);
            $client->getGoogleClient()->setAccessType('offline');
            if($this->get('session')->get('token')) {
                $client->setAccessToken($this->get('session')->get('token'));
                $refreshToken = $client->getGoogleClient()->getRefreshToken();
                $cookie = new Cookie('COOKIE', $refreshToken, time() + (3600 * 24 * 7 * 4), '/');
                $response->headers->setCookie($cookie);
            }

            $cookies = $request->cookies;

            if ($cookies->has('COOKIE') && !$this->get('session')->has('token')) {
                $cookieValue = $cookies->get('COOKIE');
                $client->getGoogleClient()->refreshToken($cookieValue);
            }

            if($client->getAccessToken()) {
                $this->get('session')->set('token', $client->getAccessToken());
            } else {
                $this->get('session')->getFlashBag()->add('danger', $this->get('translator')->trans('Erreur de connexion, veuillez contacter l\'administrateur'));
                return $this->redirect($client->createAuthUrl());
            }

            $startDate = \DateTime::createFromFormat('d/m/Y H:i', $request->request->get("date_start"));
            $endDate = \DateTime::createFromFormat('d/m/Y H:i', $request->request->get("date_start"));
            $endDate->add(new \DateInterval('PT1H'));


            $service = new \Google_Service_Calendar($client->getGoogleClient());
            $event = new \Google_Service_Calendar_Event(array(
                'summary' => $request->get('titleReminder'),
                'location' => '25-27 Place de la Madeleine, 75008 Paris',
                'description' => $request->get('descriptionReminder'),
                'start' => array(
                    'dateTime' => $startDate->format('Y-m-d').'T'.$startDate->format('H:i:s'),
                    'timeZone' => $startDate->getTimezone()->getName(),
                ),
                'end' => array(
                    'dateTime' => $endDate->format('Y-m-d').'T'.$endDate->format('H:i:s'),
                    'timeZone' => $startDate->getTimezone()->getName(),
                ),
                'reminders' => array(
                    'useDefault' => FALSE,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 10),
                    ),
                ),
            ));

            $attendees = array();

            if ($request->get('emails')) {
                foreach ($request->get('emails') as $email) {
                    $attendee = new \Google_Service_Calendar_EventAttendee();
                    $attendee->setEmail($email);
                    $attendees[] = $attendee;
                }
            }

            $event->setAttendees($attendees);

            $optParams = array(
                'sendNotifications' => true,
            );

            $calendarId = 'primary';
            $event = $service->events->insert($calendarId, $event, $optParams);

            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Reminder correctly created'));
            $response = $this->render('AppBundle:welcome:index.html.twig');
            return $response;

        } catch (Exception $ex) {
            $this->get('session')->getFlashBag()->add('danger', $this->get('translator')->trans('Something went wrong'));
            return $this->render('AppBundle:welcome:index.html.twig', [
                'success' => false,
                'error' => $ex
            ]);
        }
    }


    /**
     * @Route("/session/clear", name = "session_clear")
     * @Method({"GET","POST"})
     */
    public function clearSessionAction()
    {
        $this->get('session')->clear();

        return $this->render('AppBundle:welcome:index.html.twig');
    }
}


