<?php
/**
 * Created by PhpStorm.
 * User: therveux
 * Date: 07/06/17
 * Time: 14:53
 */

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ReminderController extends Controller
{
    /**
     * @Route("/new_reminder", name = "new_reminder")
     * @Method({"GET","POST"})
     */
    public function newReminderAction(Request $request) {
        return $this->render('AppBundle:event:new.html.twig');
    }
}