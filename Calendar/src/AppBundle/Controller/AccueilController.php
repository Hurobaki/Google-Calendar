<?php
/**
 * Created by PhpStorm.
 * User: therveux
 * Date: 06/06/17
 * Time: 10:18
 */



namespace AppBundle\Controller;

require_once '/home/therveux/public_html/Calendar/vendor/autoload.php';


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;



class AccueilController extends Controller
{
    /**
     * @Route("/accueil", name="index_accueil")
     */
    public function indexAction(Request $request) {
        return $this->render('AppBundle:welcome:index.html.twig');
    }

}